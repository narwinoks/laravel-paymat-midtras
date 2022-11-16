<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::get('/', function () {
    return view('welcome');
});
Route::get('/', 'DonationController@index')->name('donation.index');
Route::get('/donation', 'DonationController@create')->name('donation.create');

Route::post('/donation', 'DonationController@store')->name('donation.post');
// Route
