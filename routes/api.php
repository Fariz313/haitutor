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
Route::post('register_tutor', 'UserController@registerTutor');
Route::post('login', 'UserController@login');
Route::post('forgetpassword', 'UserController@forgetPassword');
Route::get('cek', 'UserController@getAuthenticatedUser');
Route::get('tes', 'UserController@tes');

Route::get('get_tutor', 'UserController@getTutor');

Route::get('subject','SubjectController@index');
Route::get('subject/{id}','SubjectController@show');
Route::post('subject','SubjectController@store');
Route::put('subject/{id}','SubjectController@update');
Route::delete('subject/{id}','SubjectController@destroy');

//

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
    
    
    
});

Route::middleware(['user.verify'])->group(function(){
    
    Route::prefix('/verify')->group(function () {
        Route::post('/', 'OtpController@verifying');
        Route::get('/', 'OtpController@createOtpVerification');
        Route::get('/email', 'OtpController@sendByEmail');
        Route::get('/sms', 'OtpController@sendBySms');
    });
    
});

Route::middleware(['user.verified'])->group(function(){

    Route::prefix('/asking')->group(function () {
        Route::get('/', 'AskController@index');
        Route::post('/{tutor_id}', 'AskController@store');
    });
    Route::prefix('/answering')->group(function () {
        Route::get('/', 'AnswerController@index ');
        Route::post('/{ask_id}', 'AnswerController@store');
    });
    
    Route::prefix('/company')->group(function () {
        Route::put('/{id}', 'CompanyController@update');
        Route::delete('/', 'CompanyController@destroy');
    });

    
    Route::prefix('/room')->group(function () {
        Route::post('/{id}','RoomController@createRoom');
        Route::get('/','RoomController@showRoom');
    });

    Route::middleware(['chat.room'])->group(function(){
        Route::prefix('/{roomkey}')->group(function () {
            Route::post('/','ChatController@store');
            Route::get('/','RoomController@getMyRoom');
            Route::delete('/{id}','ChatController@destroy');
        });
    });


});
