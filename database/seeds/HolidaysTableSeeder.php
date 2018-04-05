<?php

use Illuminate\Database\Seeder;

class HolidaysTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(\App\Holidays::class)->create([
            'country' => 'US',
            'name' => 'New Year\'s Eve',
            'rule' => 'December 31st',
            'official_holiday' => 1
        ]);
        factory(\App\Holidays::class)->create([
            'country' => 'US',
            'name' => 'Sixth Day of Kwanzaa',
            'rule' => 'December 31st',
            'official_holiday' => 1
        ]);
        factory(\App\Holidays::class)->create([
            'country' => 'US',
            'name' => 'First Day of Kwanzaa',
            'rule' => 'December 26th',
            'official_holiday' => 0
        ]);
    }
}
