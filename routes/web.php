<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\Manage\OrderController as ManageOrderController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Каталог товаров (доступен неавторизованному пользователю)
|--------------------------------------------------------------------------
*/
Route::get('/', [CatalogController::class, 'index'])->name('home');
Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/catalog/{product}', [CatalogController::class, 'show'])->name('catalog.show');

/*
|--------------------------------------------------------------------------
| Аутентификация
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('login.store');

    Route::get('/password/forgot', [PasswordResetController::class, 'requestForm'])->name('password.request');
    Route::post('/password/forgot', [PasswordResetController::class, 'sendResetLink'])
        ->middleware('throttle:5,1')
        ->name('password.email');
    Route::get('/password/reset/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/password/reset', [PasswordResetController::class, 'reset'])->name('password.store');
});

Route::post('/logout', [AuthController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Корзина и оформление заказов авторизованным пользователем
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/{product}', [CartController::class, 'add'])->name('cart.add');
    Route::patch('/cart/{product}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/{product}', [CartController::class, 'remove'])->name('cart.remove');
    Route::post('/cart/clear', [CartController::class, 'clear'])->name('cart.clear');

    Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
    Route::get('/profile/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/profile/orders/{order}', [OrderController::class, 'show'])->name('orders.show');

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/profile/password', [ProfileController::class, 'passwordForm'])->name('profile.password');
    Route::patch('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
});

/*
|--------------------------------------------------------------------------
| Управление заказами (менеджер, администратор)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:manager,admin'])
    ->prefix('manage')
    ->name('manage.')
    ->group(function () {
        Route::get('/orders', [ManageOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/create', [ManageOrderController::class, 'create'])->name('orders.create');
        Route::post('/orders', [ManageOrderController::class, 'store'])->name('orders.store');
        Route::get('/orders/{order}', [ManageOrderController::class, 'show'])->name('orders.show');
        Route::delete('/orders/{order}', [ManageOrderController::class, 'destroy'])->name('orders.destroy');
    });

/*
|--------------------------------------------------------------------------
| Редактирование заказа (только администратор)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin'])
    ->prefix('manage')
    ->name('manage.')
    ->group(function () {
        Route::get('/orders/{order}/edit', [ManageOrderController::class, 'edit'])->name('orders.edit');
        Route::put('/orders/{order}', [ManageOrderController::class, 'update'])->name('orders.update');
    });
