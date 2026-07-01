<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\ConnexionController;
use App\Http\Controllers\AdminController;
use App\Models\Pizza;

Route::redirect('/', '/pizza');

Route::prefix('pizza')->group(function() {
    Route::get('/', function () {
        return view('welcome', ['pizzas' => Pizza::all()]);
    });

    // Inscription
    Route::get('/register', [ConnexionController::class, 'register'])->name('register');
    Route::post('/register', [ConnexionController::class, 'store']);

    // Acces
    Route::get('/login', [ConnexionController::class, 'login'])->name('login');
    Route::post('/login', [ConnexionController::class, 'authenticate']);
    Route::post('/logout', [ConnexionController::class, 'logout'])->name('logout');
    
    Route::middleware('auth')->group(function () {
        // Client
        Route::prefix('customer/{userId}')->middleware('customer')->group(function() {
            Route::get('/', [CustomerController::class, 'home'])->name('customer.home');
            Route::get('/order', [CustomerController::class, 'form'])->name('order.form');
            Route::post('/order', [CustomerController::class, 'reservation'])->name('basket');
            Route::get('/basket', [CustomerController::class, 'basket'])->name('basket.confirm');
        });

        // Livreur
        Route::prefix('driver')->middleware('driver')->group(function() {
            Route::get('/', [DriverController::class, 'home'])->name('driver.home');
            Route::post('/deliver/{orderId}', [DriverController::class, 'deliver'])->name('driver.deliver');
        });

        // Admin
        Route::prefix('admin')->middleware('admin')->group(function() {
            Route::get('/', [AdminController::class, 'admin'])->name('admin');
            Route::prefix('pizzas')->group(function() {
                Route::get('list',[AdminController::class, 'list'])->name('list');
                Route::get('add',[AdminController::class, 'add'])->name('add');
                Route::post('add', [AdminController::class, 'createPizza'])->name('addpizza');
                Route::get('edit/{id}', [AdminController::class, 'edit'])->name('edit');
                Route::put('edit/{id}', [AdminController::class, 'update'])->name('update');
                Route::delete('destroy/{id}', [AdminController::class, 'destroy'])->name('destroy');
            });
            Route::get('/orders', [AdminController::class, 'order'])->name('order');
            Route::patch('/orders/{orderId}/assign', [AdminController::class, 'assignDriver'])->name('order.assign');
            Route::prefix('delivery_drivers')->group(function() {
                Route::get('/', [AdminController::class, 'delivery'])->name('delivery');
                Route::get('add', [AdminController::class, 'addDriver'])->name('driver.add');
                Route::post('add', [AdminController::class, 'createDriver'])->name('driver.create');
                Route::get('edit/{id}', [AdminController::class, 'editDriver'])->name('driver.edit');
                Route::put('edit/{id}', [AdminController::class, 'updateDriver'])->name('driver.update');
                Route::delete('destroy/{id}', [AdminController::class, 'destroyDriver'])->name('driver.destroy');
            });
        });
    });
});