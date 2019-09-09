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
Route::get('tasks', 'TaskController@index');
Route::get('tasks/{id}', 'TaskController@show');
Route::post('tasks', 'TaskController@store');
Route::put('tasks/{id}', 'TaskController@update');
Route::delete('tasks/{id}', 'TaskController@delete');


Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('cors')->group(function(){
    //your_routes
    Route::post('register', 'UserController@register');
    Route::post('login', 'UserController@login');
    Route::get('profile', 'UserController@getAuthenticatedUser');
    Route::get('state', 'StateCityController@getStates');
    Route::get('city/{stateId}', 'StateCityController@getCities');
    Route::post('social', 'SocialController@socialogin');
    Route::post('forgetpw', 'UserController@forgetpw');
    Route::post('verify_otp', 'UserController@verify_otp');
    Route::post('reset_password', 'UserController@reset_password');
 });
