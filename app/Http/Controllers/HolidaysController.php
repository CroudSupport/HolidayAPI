<?php

namespace App\Http\Controllers;

use App\Services\HolidaysService;
use Illuminate\Http\Request;

class HolidaysController extends Controller
{
    protected $holidayService;

    public function __construct(HolidaysService $holidayService)
    {
        $this->holidayService = $holidayService;
    }

    public function generateHolidays()
    {
        $this->holidayService->getHolidays();
    }

    public function getAllHolidays(Request $request)
    {
        return $this->holidayService->selectHolidays($request->all());
    }
}
