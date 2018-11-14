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

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');

Route::get('/quickAuth', 'QuickBooksController@connect')->name('quickAuth');

Route::get('/quickCallback', 'QuickBooksController@callback')->name('quickCallback');

Route::post('/add-customer', 'CustomerController@add')->name('customer.add');
Route::get('/add-customer', 'CustomerController@addCustomer')->name('add.customer');

Route::get('/quick', 'QuickBooksController@createCustomer')->name('quicks');