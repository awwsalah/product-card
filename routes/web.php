<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Products
    Route::resource('products', ProductController::class);
    Route::get('/products/{product}/adjust-stock', [ProductController::class, 'adjustStock'])
        ->name('products.adjust-stock')
        ->middleware('can:adjust-stock');
    
    // Stock Movements
    Route::get('/stock-movements', [StockMovementController::class, 'index'])
        ->name('stock-movements.index')
        ->middleware('can:adjust-stock');
    
    // Categories
    Route::resource('categories', CategoryController::class)
        ->middleware('can:manage-categories');
    
    // Users (Admin only)
    Route::resource('users', UserController::class)
        ->middleware('can:manage-users');
    
    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';