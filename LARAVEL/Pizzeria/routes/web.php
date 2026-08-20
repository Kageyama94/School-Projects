<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\ConnexionController;
use App\Http\Controllers\AdminController;
use App\Models\Pizza;

Route::redirect('/', '/pizzeria');

Route::prefix('pizzeria')->group(function() {
    Route::get('/', function () { return view('welcome', ['pizzas' => Pizza::all()]); });

    // Inscription
    Route::get('/register', [ConnexionController::class, 'register'])->name('register');
    Route::post('/register', [ConnexionController::class, 'store']);

    // Acces
    Route::get('/login', [ConnexionController::class, 'login'])->name('login');
    Route::post('/login', [ConnexionController::class, 'authenticate'])->middleware('throttle:5,1');
    Route::post('/logout', [ConnexionController::class, 'logout'])->name('logout');
    
    Route::middleware('auth')->group(function () {
        // Client
        Route::prefix('customer/{userId}')->middleware('customer')->group(function() {
            Route::get('/', [CustomerController::class, 'home'])->name('customer.home');
            Route::get('/order', [CustomerController::class, 'form'])->name('order.create');
            Route::post('/order', [CustomerController::class, 'reservation'])->name('order.store');
            Route::get('/basket', [CustomerController::class, 'basket'])->name('order.confirm');
            Route::get('/orders/{groupId}', [CustomerController::class, 'orderDetail'])->name('order.show');
            Route::delete('/orders/{groupId}', [CustomerController::class, 'cancelOrder'])->name('order.cancel');
            Route::get('/profile', [CustomerController::class, 'editProfile'])->name('customer.profile.edit');
            Route::put('/profile', [CustomerController::class, 'updateProfile'])->name('customer.profile.update');
        });

        // Livreur
        Route::prefix('driver')->middleware('driver')->group(function() {
            Route::get('/', [DriverController::class, 'home'])->name('driver.home');
            Route::post('/deliver/{groupId}', [DriverController::class, 'deliver'])->name('driver.deliver');
        });

        // Admin
        Route::prefix('admin')->middleware('admin')->group(function() {
            Route::get('/', [AdminController::class, 'admin'])->name('admin.home');
            Route::prefix('pizzas')->group(function() {
                Route::get('list', [AdminController::class, 'list'])->name('admin.pizza.index');
                Route::get('add', [AdminController::class, 'add'])->name('admin.pizza.create');
                Route::post('add', [AdminController::class, 'createPizza'])->name('admin.pizza.store');
                Route::get('edit/{id}', [AdminController::class, 'edit'])->name('admin.pizza.edit');
                Route::put('edit/{id}', [AdminController::class, 'update'])->name('admin.pizza.update');
                Route::delete('destroy/{id}', [AdminController::class, 'destroy'])->name('admin.pizza.destroy');
            });
            Route::get('/orders', [AdminController::class, 'order'])->name('admin.order.index');
            Route::get('/orders/{groupId}', [AdminController::class, 'orderDetailAdmin'])->name('admin.order.show');
            Route::patch('/orders/{groupId}/accept', [AdminController::class, 'acceptOrder'])->name('admin.order.accept');
            Route::patch('/orders/{groupId}/assign', [AdminController::class, 'assignDriver'])->name('admin.order.assign');
            Route::prefix('delivery_drivers')->group(function() {
                Route::get('/', [AdminController::class, 'delivery'])->name('admin.driver.index');
                Route::get('add', [AdminController::class, 'addDriver'])->name('admin.driver.create');
                Route::post('add', [AdminController::class, 'createDriver'])->name('admin.driver.store');
                Route::get('edit/{id}', [AdminController::class, 'editDriver'])->name('admin.driver.edit');
                Route::put('edit/{id}', [AdminController::class, 'updateDriver'])->name('admin.driver.update');
                Route::delete('destroy/{id}', [AdminController::class, 'destroyDriver'])->name('admin.driver.destroy');
            });
        });
    });
});