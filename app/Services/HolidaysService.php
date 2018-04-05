<?php

namespace App\Services;

use App\holidays;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

Class HolidaysService
{
    protected $holidays;

    public function __construct(Holidays $holidays)
    {
        $this->holidays = $holidays;
    }

    /**
     * @param $request
     * @return array
     */
    public function selectHolidays($request)
    {
        if (ini_get('date.timezone') == '') {
            date_default_timezone_set('UTC');
        }

        $payload = ['status' => 200];
        $holidays = [];

        try {
            if (!isset($request['country']) || trim($request['country']) == '') {
                throw new \Exception('The country parameter is required.');
            } elseif (!isset($request['year']) || trim($request['year']) == '') {
                throw new \Exception('The year parameter is required.');
            }

            $year = $request['year'];
            $month = isset($request['month']) ? str_pad($request['month'], 2, '0', STR_PAD_LEFT) : '';
            $day = isset($request['day']) ? str_pad($request['day'], 2, '0', STR_PAD_LEFT) : '';
            $country = isset($request['country']) ? strtoupper($request['country']) : '';
            $previous = isset($request['previous']);
            $upcoming = isset($request['upcoming']);
            $date = $year . '-' . $month . '-' . $day;
            $onlyOfficial = isset($request['official']);

            if ($previous && $upcoming) {
                throw new \Exception('You cannot request both previous and upcoming holidays.');
            } elseif (($previous || $upcoming) && (!$month || !$day)) {
                $request = $previous ? 'previous' : 'upcoming';
                $missing = !$month ? 'month' : 'day';

                throw new \Exception('The ' . $missing . ' parameter is required when requesting ' . $request . ' holidays.');
            }

            if ($month && $day) {
                if (strtotime($date) === false) {
                    throw new \Exception('The supplied date (' . $date . ') is invalid.');
                }
            }

            $country_holidays = $this->calculateHolidays($country, $year, $previous || $upcoming, $onlyOfficial);

        } catch (\Exception $e) {
            if ($e->getMessage() == 'The API is currently unavailable') {
                $payload['status'] = 500;
                $payload['error'] = $e->getMessage();
            } else {
                $payload['status'] = 400;
                $payload['error'] = $e->getMessage();
            }
        }

        if ($payload['status'] == 200) {
            $payload['holidays'] = [];

            if ($month && $day) {
                if ($previous) {
                    $country_holidays = $this->flatten($date, $country_holidays[$year - 1], $country_holidays[$year]);
                    prev($country_holidays);
                    $payload['holidays'] = current($country_holidays);
                } elseif ($upcoming) {
                    $country_holidays = $this->flatten($date, $country_holidays[$year], $country_holidays[$year + 1]);
                    next($country_holidays);
                    $payload['holidays'] = current($country_holidays);
                } elseif (isset($country_holidays[$year][$date])) {
                    $payload['holidays'] = $country_holidays[$year][$date];
                }
            } elseif ($month) {
                foreach ($country_holidays[$year] as $date => $country_holiday) {
                    if (substr($date, 0, 7) == $year . '-' . $month) {
                        $payload['holidays'] = array_merge($payload['holidays'], $country_holiday);
                    }
                }
            } else {
                $payload['holidays'] = $country_holidays[$year];
            }
        }

        return $payload;
    }

    /**
     * Method to find the holidays based on the parameters
     * passed into the method
     *
     * @param $country
     * @param $year
     * @param bool $range
     * @param bool $onlyOfficial
     * @return array
     * @throws \Exception
     */
    private function calculateHolidays($country, $year, $range = false, $onlyOfficial = false)
    {
        try {
            $country_holidays = $this->holidays->select()->where('country', $country)->get();
        } catch (\Illuminate\Database\QueryException $ex) {
            throw new \Exception('The API is currently unavailable');
        }

        if ($country_holidays->isEmpty()) {
            throw new \Exception('The supplied country (' . $country . ') is not supported at this time.');
        }

        $return = [];

        if ($range) {
            $years = [$year - 1, $year, $year + 1];
        } else {
            $years = [$year];
        }

        $getHolidays = $country_holidays->toArray();

        foreach ($years as $year) {
            $calculated_holidays = [];

            foreach ($getHolidays as $country_holiday) {
                if (strstr($country_holiday['rule'], '%Y')) {
                    $rule = str_replace('%Y', $year, $country_holiday['rule']);
                } elseif (strstr($country_holiday['rule'], '%EASTER')) {
                    $rule = str_replace('%EASTER', date('Y-m-d', strtotime($year . '-03-21 +' . easter_days($year) . ' days')), $country_holiday['rule']);
                } elseif (in_array($country, ['BR', 'US']) && strstr($country_holiday['rule'], '%ELECTION')) {
                    switch ($country) {
                        case 'BR':
                            $years = range(2014, $year, 2);
                            break;
                        case 'US':
                            $years = range(1788, $year, 4);
                            break;
                    }

                    if (in_array($year, $years)) {
                        $rule = str_replace('%ELECTION', $year, $country_holiday['rule']);
                    } else {
                        $rule = false;
                    }
                } else {
                    $rule = $country_holiday['rule'] . ' ' . $year;
                }

                if ($rule) {
                    $calculated_date = date('Y-m-d', strtotime($rule));

                    if (!isset($calculated_holidays[$calculated_date])) {
                        $calculated_holidays[$calculated_date] = [];
                    }

                    if ($onlyOfficial && $country_holiday['official_holiday']) {
                        $calculated_holidays[$calculated_date][] = [
                            'name' => $country_holiday['name'],
                            'country' => $country,
                            'date' => $calculated_date
                        ];
                    } else if (!$onlyOfficial) {
                        $calculated_holidays[$calculated_date][] = [
                            'name' => $country_holiday['name'],
                            'country' => $country,
                            'date' => $calculated_date
                        ];
                    }
                }
            }

            $country_holidays = $calculated_holidays;

            ksort($country_holidays);

            foreach ($country_holidays as $date_key => $date_holidays) {
                usort($date_holidays, function ($a, $b) {
                    $a = $a['name'];
                    $b = $b['name'];

                    if ($a == $b) {
                        return 0;
                    }

                    return $a < $b ? -1 : 1;
                });

                $country_holidays[$date_key] = $date_holidays;
            }

            $return[$year] = $country_holidays;
        }

        return $return;
    }

    private function flatten($date, $array1, $array2)
    {
        $holidays = array_merge($array1, $array2);

        // Injects the current date as a placeholder
        if (!isset($holidays[$date])) {
            $holidays[$date] = false;
            ksort($holidays);
        }

        // Sets the internal pointer to today
        while (key($holidays) !== $date) {
            next($holidays);
        }

        return $holidays;
    }
}