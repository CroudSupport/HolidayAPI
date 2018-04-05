<?php

use App\holidays;
use App\Services\HolidaysService;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class HolidaysServiceTest extends TestCase
{
    use DatabaseMigrations;

    public $service;
    public $holiday;

    function setUp()
    {
        parent::setUp();
        $this->holiday = new Holidays();
        $this->service = new HolidaysService($this->holiday);
    }

    public function testNoCountry()
    {
        $request = [];

        $checkResponse = [
            "status" => 400,
            "error" => "The country parameter is required."
        ];

        $this->assertEquals($checkResponse, $this->service->selectHolidays($request));
    }

    public function testNoYear()
    {
        $request = [
            'country' => 'US'
        ];

        $checkResponse = [
            "status" => 400,
            "error" => "The year parameter is required."
        ];

        $this->assertEquals($checkResponse, $this->service->selectHolidays($request));
    }

    public function testPreviousAndUpcoming()
    {
        $request = [
            'country' => 'US',
            'year' => '2018',
            'previous' => 'previous',
            'upcoming' => 'upcoming'
        ];

        $checkResponse = [
            "status" => 400,
            "error" => "You cannot request both previous and upcoming holidays."
        ];

        $this->assertEquals($checkResponse, $this->service->selectHolidays($request));
    }

    public function testMissingMonth()
    {
        $request = [
            'country' => 'US',
            'year' => '2018',
            'previous' => 'previous'
        ];

        $checkResponse = [
            "status" => 400,
            "error" => "The month parameter is required when requesting previous holidays."
        ];

        $this->assertEquals($checkResponse, $this->service->selectHolidays($request));
    }

    public function testInvalidDate()
    {
        $request = [
            'country' => 'US',
            'year' => '2018',
            'month' => '123',
            'day' => '1',
            'previous' => 'previous'
        ];

        $checkResponse = [
            "status" => 400,
            "error" => "The supplied date (2018-123-01) is invalid."
        ];

        $this->assertEquals($checkResponse, $this->service->selectHolidays($request));
    }

    public function testInvalidCountry()
    {
        $request = [
            'country' => 'US',
            'year' => '2018',
            'month' => '12',
            'day' => '1',
            'previous' => 'previous'
        ];

        $checkResponse = [
            "status" => 400,
            "error" => "The supplied country (US) is not supported at this time."
        ];

        $this->assertEquals($checkResponse, $this->service->selectHolidays($request));
    }

    public function testSelectElectionRule()
    {
        $request = [
            'country' => 'US',
            'year' => '2020',
            'month' => '11'
        ];

        $this->holiday = factory(\App\Holidays::class)->create([
            'country' => 'US',
            'name' => 'Super Tuesday',
            'rule' => 'First Monday of November %ELECTION +1 day',
            'official_holiday' => 0
        ]);


        $service = new HolidaysService($this->holiday);

        $getResponse = $service->selectHolidays($request);

        $this->assertEquals('2020-11-03', $getResponse['holidays'][0]['date']);
        $this->assertEquals('Super Tuesday', $getResponse['holidays'][0]['name']);
    }

    public function testReplaceYear()
    {
        $request = [
            'country' => 'GB',
            'year' => '2018',
            'month' => '12'
        ];

        $this->holiday = factory(\App\Holidays::class)->create([
            'country' => 'GB',
            'name' => 'Christmas (possibly in lieu)',
            'rule' => '24 December %Y +1 weekday',
            'official_holiday' => 1
        ]);

        $service = new HolidaysService($this->holiday);

        $getResponse = $service->selectHolidays($request);

        $this->assertEquals('2018-12-25', $getResponse['holidays'][0]['date']);
        $this->assertEquals('Christmas (possibly in lieu)', $getResponse['holidays'][0]['name']);
    }

    public function testGetEaster()
    {
        $request = [
            'country' => 'GB',
            'year' => '2018',
            'month' => '04'
        ];

        $this->holiday = factory(\App\Holidays::class)->create([
            'country' => 'GB',
            'name' => 'Easter Monday',
            'rule' => '%EASTER +1 day',
            'official_holiday' => 1
        ]);

        $service = new HolidaysService($this->holiday);

        $getResponse = $service->selectHolidays($request);

        $this->assertEquals('2018-04-02', $getResponse['holidays'][0]['date']);
        $this->assertEquals('Easter Monday', $getResponse['holidays'][0]['name']);
    }

    public function testOnlyOfficial()
    {
        $request = [
            'country' => 'US',
            'year' => '2018',
            'month' => '12',
            'official' => 'official'
        ];

        $this->holiday = factory(\App\Holidays::class)->create([
            'country' => 'US',
            'name' => 'Christmas Eve',
            'rule' => 'December 24th',
            'official_holiday' => 1
        ]);
        $this->holiday = factory(\App\Holidays::class)->create([
            'country' => 'US',
            'name' => 'Christmas',
            'rule' => 'December 25th',
            'official_holiday' => 1
        ]);
        $this->holiday = factory(\App\Holidays::class)->create([
            'country' => 'US',
            'name' => 'First Day of Kwanzaa',
            'rule' => 'December 26th',
            'official_holiday' => 0
        ]);

        $service = new HolidaysService($this->holiday);

        $getResponse = $service->selectHolidays($request);

        $this->assertEquals(2, count($getResponse['holidays']));
    }

    public function testPrevious()
    {
        $request = [
            'country' => 'US',
            'year' => '2017',
            'month' => '01',
            'day' => '01',
            'previous' => 'previous'
        ];

        $this->holiday = factory(\App\Holidays::class)->create([
            'country' => 'US',
            'name' => 'New Year\'s Eve',
            'rule' => 'December 31st',
            'official_holiday' => 1
        ]);
        $this->holiday = factory(\App\Holidays::class)->create([
            'country' => 'US',
            'name' => 'Sixth Day of Kwanzaa',
            'rule' => 'December 31st',
            'official_holiday' => 1
        ]);
        $this->holiday = factory(\App\Holidays::class)->create([
            'country' => 'US',
            'name' => 'First Day of Kwanzaa',
            'rule' => 'December 26th',
            'official_holiday' => 0
        ]);

        $service = new HolidaysService($this->holiday);

        $getResponse = $service->selectHolidays($request);

        $this->assertEquals(2, count($getResponse['holidays']));
        $this->assertEquals('New Year\'s Eve', $getResponse['holidays'][0]['name']);
    }

    public function testUpcoming()
    {
        $request = [
            'country' => 'US',
            'year' => '2017',
            'month' => '01',
            'day' => '01',
            'upcoming' => 'upcoming'
        ];

        $this->holiday = factory(\App\Holidays::class)->create([
            'country' => 'US',
            'name' => 'Epiphany',
            'rule' => 'January 6th',
            'official_holiday' => 1
        ]);
        $this->holiday = factory(\App\Holidays::class)->create([
            'country' => 'US',
            'name' => 'Orthodox Christmas',
            'rule' => 'January 7th',
            'official_holiday' => 1
        ]);
        $this->holiday = factory(\App\Holidays::class)->create([
            'country' => 'US',
            'name' => 'First Day of Kwanzaa',
            'rule' => 'December 26th',
            'official_holiday' => 0
        ]);

        $service = new HolidaysService($this->holiday);

        $getResponse = $service->selectHolidays($request);

        $this->assertEquals(1, count($getResponse['holidays']));
        $this->assertEquals('Epiphany', $getResponse['holidays'][0]['name']);
    }

}
