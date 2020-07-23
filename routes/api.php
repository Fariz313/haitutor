<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::post('register', 'UserController@register');
Route::post('login', 'UserController@login');
Route::post('forgetPassword', 'UserController@forgetPassword');
Route::get('cek', 'UserController@getAuthenticatedUser');

Route::prefix('/company')->group(function () {
    Route::get('/', 'CompanyController@index');
    Route::get('/{id}', 'CompanyController@show');
    Route::post('/', 'CompanyController@store');
});

Route::middleware(['jwt.verify'])->group(function(){

    //authentication :)
    Route::put('edit', 'UserController@update');
    Route::get('logout', 'UserController@logout');
    Route::post('photo', 'UserController@uploadPhoto');

    Route::prefix('/company')->group(function () {
        Route::put('/{id}', 'CompanyController@update');
        Route::delete('/', 'CompanyController@destroy');
    });

});

Route::middleware(['user.verify'])->group(function(){

    Route::prefix('/verify')->group(function () {
        Route::post('/', 'OtpController@verifying');
        Route::get('/', 'OtpController@createOtpVerification');
        Route::get('/email', 'OtpController@sendByEmail');
        Route::get('/sms', 'OtpController@sendBySms');
    });

    
});