<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::post('/v1/webhook', 'App\Http\Controllers\Controller@handle');

Route::post('/v1/transaction', 'App\Http\Controllers\Controller@transaction');

Route::post('/v1/webhook2' , 'App\Http\Controllers\Controller@handle2')->name('handle2');
