<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MarketController;
use App\Http\Controllers\ProductController;
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

Route::prefix('markets')->middleware('auth:api')->group(function(){
    
    Route::get('',[MarketController::class,'index'])->name('markets');
    Route::get('/{market}',[MarketController::class,'show'])->name('markets.show');
    Route::post('',[MarketController::class,'store'])->name('markets.store');
    Route::put('/{market}',[MarketController::class,'update'])->name('markets.update');
    Route::delete('/{market}',[MarketController::class,'destroy'])->name('markets.delete');

});


// Product's Routes

Route::prefix('products')->middleware('auth:api')->group(function(){
    
    Route::get('/{product}',[ProductController::class,'show'])->name('products.show');
    Route::post('/{market}',[ProductController::class,'store'])->name('products.store');
    Route::put('/{product}',[ProductController::class,'update'])->name('products.update');
    Route::delete('/{product}',[ProductController::class,'destroy'])->name('products.delete');

});

