<?php

namespace App\Services;

use App\holidays;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

Class ImportService
{
    protected $holidays;

    public function __construct(Holidays $holidays)
    {
        $this->holidays = $holidays;
    }

    /**
     * @param $path
     * @return string
     */
    public function importHolidays($path)
    {
        $getCountry = array_diff(scandir(storage_path($path)), array('..', '.'));

        if ($getCountry) {
            foreach ($getCountry as $countryCode) {
                if ($countryCode) {
                    $country = str_replace('.json', '', $countryCode);
                    $json = json_decode(file_get_contents(storage_path($path) . '/' . $countryCode), true);
                    foreach ($json as $getHoliday) {
                        $storeHoliday[] = [
                            'country' => $country,
                            'name' => $getHoliday['name'],
                            'rule' => $getHoliday['rule']
                        ];
                    }
                }
            }
        } else {
            return 'No json files found, please add some to storage//holidays';
        }

        if ($storeHoliday) {
            try {
                $this->holidays->bulkInsertOrUpdate($storeHoliday);
                return 'All Holidays have been added to the table, Thank you.';
            } catch (\Illuminate\Database\QueryException $ex) {
                return 'No table found, Did you run migrations?';
            }
        } else {
            return 'Invalid Files, please Amend';
        }

    }
}