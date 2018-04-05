<?php

namespace App\Console\Commands;

use App\Http\Controllers\ImportController;
use Illuminate\Console\Command;

class GenerateHolidays extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:holidays';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate all holidays from the JSON Files';

    /**
     * The All holidays controller.
     *
     * @var HolidaysController
     */
    protected $holidays;

    /**
     * Create a new command instance.
     *
     * @param ImportController $holidays
     */
    public function __construct(ImportController $holidays)
    {
        parent::__construct();

        $this->holidays = $holidays;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $response = $this->holidays->generateHolidays();
        echo $response;
    }
}
