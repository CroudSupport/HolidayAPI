<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/v1/holidays', 'HolidaysController@getAllHolidays', function() {
    return response('Status',200)
        ->header('Content-Type', 'text/plain');
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');
