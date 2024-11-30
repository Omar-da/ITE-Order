<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MarketController;
use App\Http\Middleware\RefreshMiddleware;

Route::get('/welcome',function(){
    return response()->json('Welcome to ITE Order');
});


// Auth Routes

Route::prefix('auth')->group(function () {

    Route::post('/register', [AuthController::class, 'register'])->name('register')->middleware('guest');
    Route::post('/login', [AuthController::class, 'login'])->name('login')->middleware('guest');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware(['auth:api','refresh']);
    Route::get('/me', [AuthController::class, 'me'])->name('me')->middleware('auth:api');
    Route::post('/refresh', [AuthController::class, 'refresh'])->name('refresh')->middleware('refresh');

});


// Market's Routes

Route::middleware('auth:api')->group(function(){
    
    Route::get('/markets',[MarketController::class,'index'])->name('markets');
    Route::get('/markets/{market}',[MarketController::class,'show'])->name('markets.show');
    Route::post('/markets',[MarketController::class,'store'])->name('markets.store');
    Route::put('/markets/{market}',[MarketController::class,'update'])->name('markets.update');
    Route::delete('/markets/{market}',[MarketController::class,'destroy'])->name('markets.delete');

});



