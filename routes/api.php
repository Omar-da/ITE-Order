<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::get('/welcome',function(){
    return response()->json('Welcome to ITE Order');
});


// Auth Routes

Route::prefix('auth')->group(function () {

    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware(['auth:api','refresh']);
    Route::get('/me', [AuthController::class, 'me'])->name('me')->middleware('auth:api');
    Route::post('/refresh', [AuthController::class, 'refresh'])->name('refresh')->middleware('refresh');

});




