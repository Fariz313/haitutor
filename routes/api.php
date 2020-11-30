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
Route::middleware(['cors'])->group(function(){

    Route::post('/order/verify/{id}', 'OrderController@verify');
    Route::post('register', 'UserController@register');
    Route::post('register_tutor', 'UserController@registerTutor');
    Route::post('login', 'UserController@login');
    Route::post('forgetpassword', 'UserController@forgetPassword');
    Route::get('cek', 'UserController@getAuthenticatedUser');
    Route::get('tes', 'UserController@tes');
    Route::put('balance', 'UserController@updateBalance');
    Route::put('firebase_token', 'UserController@updateFirebaseToken');
    Route::put('verification/request/', 'UserController@requestVerification');
    Route::get('restricted-status', 'UserController@checkUserIsRestricted');

    Route::get('faq','AskController@getAllFAQ');

    Route::get('appVersion','AppVersionController@getAll');
    Route::get('information','InformationController@getAll');
    Route::get('faq','FaqController@getAll');

    Route::get('get_tutor', 'TutorController@getTutor');
    Route::get('get_tutor/all', 'TutorController@getAllTutor');
    Route::get('get_tutor/{id}', 'TutorController@showTutor');

    Route::get('rating', 'RatingController@index');
    Route::get('rating/{id}', 'RatingController@show');
    Route::post('rating/{id}', 'RatingController@store');
    Route::delete('rating/{id}', 'RatingController@delete');
    Route::get('rating-by-user/{user_id}', 'RatingController@ratedByUser');
    Route::get('rating-user/{user_id}', 'RatingController@userRatingList');

    Route::get('get_student', 'UserController@getAllStudent');
    Route::get('get_student/{id}', 'UserController@getStudent');

    Route::get('/package', 'PackageController@index');

    Route::get('order', 'OrderController@index');
    Route::get('order/{id}', 'OrderController@showById');
    Route::get('order-user', 'OrderController@show');
    Route::post('order/{id}', 'OrderController@store');
    Route::post('callback', 'OrderController@callbackTransaction');
    Route::post('request/order', 'OrderController@requestTransaction');
    Route::get('payment/method/', 'OrderController@getAllPaymentMethod');

    Route::get('subject','SubjectController@index');
    Route::get('subject/{id}','SubjectController@show');
    Route::post('subject','SubjectController@store');
    Route::put('subject/{id}','SubjectController@update');
    Route::delete('subject/{id}','SubjectController@destroy');
    Route::get('get_subject','SubjectController@getSubject');
    Route::get('subject/unassigned/{tutor_id}', 'SubjectController@getUnassignedSubject');

    Route::get('tutor_subject', 'TutorSubjectController@index');
    Route::get('tutor_subject/{id}', 'TutorSubjectController@show');
    Route::post('tutor_subject', 'TutorSubjectController@store');
    Route::delete('tutor_subject/{id}', 'TutorSubjectController@destroy');

    Route::get('tutor_by_subject/{subject_id}', 'TutorController@getTutorBySubject');
    Route::get('subject_tutor/{tutor_id}', 'TutorSubjectController@getSubjectTutor');

    Route::get('/package', 'PackageController@index');

    Route::get('/otpView', 'OtpController@showOtp');

    Route::get('info', 'UserController@getInformation');
    Route::get('version/{versionCode}', 'UserController@checkUpdate');

    Route::post('pushNotification', 'NotificationController@pushNotification');

    Route::get('show-otp', 'OtpController@showOtp');
    Route::get('show-pass-otp', 'OtpController@showPasswordOtp');

    Route::prefix('/company')->group(function () {
        Route::get('/', 'CompanyController@index');
        Route::get('/{id}', 'CompanyController@show');
        Route::post('/', 'CompanyController@store');
    });

    Route::prefix('/notif')->group(function () {
        Route::get('/', 'NotificationController@index');
        Route::post('/', 'NotificationController@store');
        Route::get('/target/{targetId}', 'NotificationController@getNotifByTargetId');
        Route::put('/read/{id}','NotificationController@update');
        Route::put('/read','NotificationController@markAllAsRead');
    });

    Route::prefix('/report')->group(function()
    {
        Route::get('/', 'ReportController@index');
        Route::post('/', 'ReportController@store');
        Route::get('/{id}', 'ReportController@show');
        Route::delete('/{id}', 'ReportController@destroy');
    });

    Route::prefix('/reportIssue')->group(function()
    {
        Route::get('/', 'ReportController@getReportIssue');
        Route::post('/', 'ReportController@insertReportIssue');
        Route::get('/{id}', 'ReportController@getDetailReportIssue');
        Route::put('/{id}', 'ReportController@updateReportIssue');
        Route::delete('/{id}', 'ReportController@deleteReportIssue');
    });

    Route::prefix('/disbursement')->group(function()
    {
        Route::get('/', 'DisbursementController@index');
        Route::get('/{id}', 'DisbursementController@show');
        Route::post('/request', 'DisbursementController@store');
        Route::get('/user/{userId}', 'DisbursementController@getDisbursementByUserId');
        Route::put('/accept/{id}', 'DisbursementController@acceptDisbursement');
        Route::put('/reject/{id}', 'DisbursementController@rejectDisbursement');
        Route::get('/pending/latest', 'DisbursementController@getLatestPending');
        Route::put('/cancel/{id}', 'DisbursementController@cancelDisbursement');
        Route::put('/info/{userId}', 'TutorController@updateDisbursementDoc');
        Route::get('/request/check', 'DisbursementController@checkRequirements');
    });

    Route::prefix('/payment')->group(function () {

        Route::prefix('/method')->group(function () {
            Route::get('/list/all', 'PaymentMethodController@getAll');
            Route::get('/list/enable', 'PaymentMethodController@getAllEnabledPaymentMethod');
            Route::get('/{id}','PaymentMethodController@getOne');

            Route::post('/', 'PaymentMethodController@store');

            Route::put('/{id}','PaymentMethodController@update');
            Route::put('/enable/{id}', 'PaymentMethodController@EnablePaymentMethod');
            Route::put('/disable/{id}', 'PaymentMethodController@DisablePaymentMethod');

            Route::delete('/{id}','PaymentMethodController@destroy');
        });

        Route::prefix('/category')->group(function () {
            Route::get('/list/all', 'PaymentCategoryController@index');
            Route::get('/{id}','PaymentCategoryController@show');

            Route::post('/', 'PaymentCategoryController@store');

            Route::put('/{id}','PaymentCategoryController@update');
            Route::put('/enable/{id}', 'PaymentCategoryController@enablePaymentCategory');
            Route::put('/disable/{id}', 'PaymentCategoryController@disablePaymentCategory');

            Route::delete('/{id}','PaymentCategoryController@destroy');
        });

        Route::prefix('/provider')->group(function () {
            Route::get('/list/all', 'PaymentProviderController@index');
            Route::get('/{id}','PaymentProviderController@show');

            Route::post('/', 'PaymentProviderController@store');

            Route::put('/{id}','PaymentProviderController@update');
            Route::put('/enable/{id}', 'PaymentProviderController@enablePaymentProvider');
            Route::put('/disable/{id}', 'PaymentProviderController@disablePaymentProvider');
            Route::put('/include/{paymentMethodId}', 'PaymentProviderController@includePaymentMethod');
            Route::put('/exclude/{paymentMethodId}', 'PaymentProviderController@excludePaymentMethod');

            Route::delete('/{id}','PaymentProviderController@destroy');

            Route::prefix('/variable')->group(function () {
                Route::get('/list/all', 'PaymentProviderController@getAllPaymentProviderVariable');
                Route::get('/{id}','PaymentProviderController@getPaymentProviderVariableById');
                Route::post('/','PaymentProviderController@addPaymentProviderVariable');
                Route::put('/{id}','PaymentProviderController@updatePaymentProviderVariable');
                Route::delete('/{id}','PaymentProviderController@deletePaymentProviderVariable');
            });

            Route::prefix('/method/variable')->group(function () {
                Route::get('/list/all', 'PaymentProviderController@getAllPaymentMethodProviderVariable');
                Route::get('/{id}','PaymentProviderController@getPaymentMethodProviderVariableById');
                Route::post('/','PaymentProviderController@addPaymentMethodProviderVariable');
                Route::put('/{id}','PaymentProviderController@updatePaymentMethodProviderVariable');
                Route::delete('/{id}','PaymentProviderController@deletePaymentMethodProviderVariable');
            });
        });

    });

    Route::middleware(['user.tutor'])->group(function(){
        Route::post('tutordoc', 'TutorDocController@store');
        Route::delete('tutordoc/{id}', 'TutorDocController@destroy');
        Route::post('tutordoc/{id}', 'TutorDocController@update');
    });

    //--------------------------------------------------LOGGED IN USER MIDDLEWARE
    Route::middleware(['jwt.verify'])->group(function(){

        //authentication :)
        Route::put('edit', 'UserController@update');
        Route::get('logout', 'UserController@logout');
        Route::post('photo', 'UserController@uploadPhoto');



    });
    //--------------------------------------------------


    //--------------------------------------------------UNVERIFIED USER MIDDLEWARE
    Route::middleware(['user.verify'])->group(function(){

        Route::prefix('/verify')->group(function () {
            Route::post('/', 'OtpController@verifying');
            // Route::get('/', 'OtpController@createOtpVerification');
            Route::get('/{device_id}', 'OtpController@createOtpVerification');
            Route::get('/email', 'OtpController@sendByEmail');
            Route::get('/sms', 'OtpController@sendBySms');
        });

    });
    //--------------------------------------------------

    //--------------------------------------------------VERIFIED USER MIDDLEWARE
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
            Route::get('/cek', 'RoomController@checkRoom');
            Route::put('/status/{id}', 'RoomController@updateStatus');
        });

        Route::prefix('/room_vc')->group(function ()
        {
            Route::get('/', 'RoomVCController@showRoom');
            Route::post('/{tutor_id}', 'RoomVCController@createRoom');
            Route::get('/cek', 'RoomVCController@checkRoom');
            Route::put('/duration/{id}', 'RoomVCController@updateDuration');
            Route::post('/history/{tutor_id}', 'HistoryVCController@createHistory');
            Route::put('/history/{id}', 'HistoryVCController@updateHistory');
            Route::get('/history', 'HistoryVCController@showRoom');
            Route::put('/token', 'RoomVCController@updateToken');

            Route::post('request/{room_id}', 'RoomVCController@sendNotifRequestJoinRoom');
            Route::post('cancel/{room_id}', 'RoomVCController@cancelNotifRequestJoinRoom');
            Route::post('reject/{room_id}', 'RoomVCController@rejectNotifRequestJoinRoom');
        });

        Route::prefix('/token')->group(function()
        {
            Route::post('chat/{tutor_id}', 'TokenTransactionController@chat');
            Route::post('videocall/{tutor_id}', 'TokenTransactionController@videocall');
        });

        Route::middleware(['chat.room'])->group(function(){
            Route::prefix('/{roomkey}')->group(function () {
                Route::post('/','ChatController@store');
                Route::get('/','RoomController@getMyRoom');
                Route::delete('/{id}','ChatController@destroy');
                Route::put('/', 'ChatController@updateReadedMessage');
            });
        });


        Route::prefix('/rating')->group(function ()
        {
            Route::get('/check/{user_id}', 'RatingController@check');
        });

    });
    //--------------------------------------------------

    Route::prefix('/admin')->group(function () {

        Route::post('/login', 'AdminController@login');
        Route::post('/register', 'AdminController@register');

        Route::middleware(['admin.general'])->group(function(){
            Route::put('/verify_tutor/{id}', 'TutorController@verifyTutor');
            Route::put('/unverify_tutor/{id}', 'TutorController@unverifyTutor');
            Route::put('/verify_doc/{id}', 'TutorDocController@verifyingDoc');
            Route::put('/unverify_doc/{id}', 'TutorDocController@unverifyingDoc');
            Route::get('/get_tutor/unverified', 'TutorController@getUnverifiedTutor');
            Route::put('/suspend/{id}', 'UserController@suspendUser');
            Route::put('/unsuspend/{id}', 'UserController@unsuspendUser');
            Route::put('/verify_doc/all/{userId}', 'TutorDocController@verifyingAllDoc');
            Route::put('/unverify_doc/all/{userId}', 'TutorDocController@unverifyingAllDoc');

            Route::get('/package', 'PackageController@index');
            Route::post('/package', 'PackageController@store');
            Route::get('/package/{id}', 'PackageController@show');
            Route::put('/package/{id}', 'PackageController@update');
            Route::delete('/package/{id}', 'PackageController@destroy');

            Route::get('/room','RoomController@index');
            Route::get('/room/{id}', 'RoomController@showById');
            Route::put('/room/{id}', 'RoomController@updateStatusByAdmin');
            Route::delete('/room/{id}', 'RoomController@destroy');

            Route::get('/room_vc','RoomVCController@index');
            Route::get('/room_vc/{id}','RoomVCController@showById');
            Route::put('/room_vc/{id}', 'RoomVCController@updateStatusByAdmin');
            Route::delete('/room_vc/{id}', 'RoomVCController@destroy');

            Route::put('/user/{id}', 'UserController@updateById');
            Route::delete('/user/{id}', 'UserController@destroy');
            Route::put('/order/{id}', 'OrderController@manualVerifyOrder');
            Route::delete('/order/{id}', 'OrderController@destroy');

            Route::get('/order-token', 'OrderController@historyToken');
            Route::get('/order-token/{id}', 'OrderController@detailHistoryToken');

            Route::get('/article','ArticleController@getAll');
            Route::post('/article','ArticleController@store');
            Route::put('/article/{id}','ArticleController@update');
            Route::get('/article/{id}','ArticleController@getOne');
            Route::delete('/article/{id}','ArticleController@destroy');

            Route::get('/appVersion','AppVersionController@getAll');
            Route::post('/appVersion','AppVersionController@store');
            Route::put('/appVersion/{id}','AppVersionController@update');
            Route::get('/appVersion/{id}','AppVersionController@getOne');
            Route::delete('/appVersion/{id}','AppVersionController@destroy');

            Route::get('/information','InformationController@getAll');
            Route::post('/information','InformationController@store');
            Route::put('/information/{id}','InformationController@update');
            Route::get('/information/{id}','InformationController@getOne');
            Route::delete('/information/{id}','InformationController@destroy');

            Route::get('/payment_method','PaymentMethodController@getAll');
            Route::post('/payment_method','PaymentMethodController@store');
            Route::put('/payment_method/{id}','PaymentMethodController@update');
            Route::put('/payment_method/status/{id}','PaymentMethodController@updateStatus');
            Route::get('/payment_method/{id}','PaymentMethodController@getOne');
            Route::delete('/payment_method/{id}','PaymentMethodController@destroy');

            Route::get('/faq','FaqController@getAll');
            Route::post('/faq','FaqController@store');
            Route::put('/faq/{id}','FaqController@update');
            Route::get('/faq/{id}','FaqController@getOne');
            Route::delete('/faq/{id}','FaqController@destroy');

            Route::get('/user/admin/', 'AdminController@index');
            Route::get('/user/admin/{id}', 'AdminController@showAdmin');
            Route::put('/user/admin/{id}', 'AdminController@updateAdmin');
            Route::delete('/user/admin/{id}', 'AdminController@destroyAdmin');

            // Dashboard
            Route::get('/statistics', 'AdminController@dashboard');

            //Subject
            Route::get('subject','SubjectController@index');
            Route::get('subject/{id}','SubjectController@show');
            Route::post('subject','SubjectController@store');
            Route::put('subject/{id}','SubjectController@update');
            Route::post('subject/icon/{id}','SubjectController@updateIcon');
            Route::delete('subject/{id}','SubjectController@destroy');

        });

    });


});
