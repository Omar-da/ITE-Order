<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\MarketController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;

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
    Route::get('/admin/{user}',[AuthController::class, 'to_admin'])->name('to_admin')->middleware('role:owner');
    Route::get('/user/{user}',[AuthController::class, 'to_user'])->name('to_user')->middleware('role:owner');
    Route::put('/update_profile',[AuthController::class, 'updateProfile'])->name('update_profile');
    
});


// Market's Routes

Route::prefix('markets')->middleware('auth:api')->group(function(){
    
    Route::get('',[MarketController::class,'index'])->name('markets');
    Route::get('/search',[MarketController::class,'search'])->name('markets.search');
    Route::get('/{market}',[MarketController::class,'show'])->name('markets.show');
    Route::post('',[MarketController::class,'store'])->name('markets.store')->middleware('role:admin');
    Route::put('/{market}',[MarketController::class,'update'])->name('markets.update')->middleware('role:admin');
    Route::delete('/{market}',[MarketController::class,'destroy'])->name('markets.delete')->middleware('role:admin');

});


// Product's Routes

Route::prefix('products')->middleware('auth:api')->group(function(){
    
    Route::get('/favorites',[ProductController::class,'index_favorites'])->name('products.favorites')->middleware('role:user');
    Route::get('/search',[ProductController::class,'search'])->name('products.search');
    Route::get('/{product}',[ProductController::class,'show'])->name('products.show');
    Route::post('/{market}',[ProductController::class,'store'])->name('products.store')->middleware('role:admin');
    Route::put('/{product}',[ProductController::class,'update'])->name('products.update')->middleware('role:admin');
    Route::delete('/{product}',[ProductController::class,'destroy'])->name('products.delete')->middleware('role:admin');
    Route::get('/add_to_favorites/{product}',[ProductController::class,'add_to_favorites'])->name('products.add_to_favorites')->middleware('role:user');

});


// Cart's Routes

Route::prefix('cart')->middleware('role:user')->group(function(){
    
    Route::get('/add_to_cart/{product}',[CartController::class,'add'])->name('cart.add');
    Route::get('/remove_from_cart/{product}',[CartController::class,'remove'])->name('cart.remove');
    Route::get('/products',[CartController::class,'index'])->name('cart.index');
    Route::post('/order',[CartController::class,'order'])->name('cart.order');
    Route::delete('/delete',[CartController::class,'destroy'])->name('cart.destroy');
});


// Order's Routes

Route::prefix('orders')->middleware('role:user')->group(function(){

    Route::get('/',[OrderController::class,'index'])->name('orders.index');
    Route::put('/update/{order}',[OrderController::class,'update'])->name('orders.update');
    Route::delete('/delete/{order}',[OrderController::class,'destroy'])->name('orders.destroy');
    Route::get('/period_of_editing_has_finished/{order}',[OrderController::class,'period_of_editing_has_finished'])->name('orders.order_confirmation');
    Route::get('/restore/{order}',[OrderController::class,'restore'])->name('orders.restore');
    
});

Route::prefix('orders')->middleware('role:admin')->group(function(){

    Route::get('/admin_response/{order}',[OrderController::class,'admin_response'])->name('orders.response');
    Route::get('/delivered/{order}',[OrderController::class,'delivered'])->name('orders.delivered');

});