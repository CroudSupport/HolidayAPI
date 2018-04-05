<?php

namespace App\Http\Controllers;

use App\Services\ImportService;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    protected $importService;

    public function __construct(ImportService $importService)
    {
        $this->importService = $importService;
    }

    public function generateHolidays()
    {
        $path = '/holidays';
        return $this->importService->importHolidays($path);
    }
}
