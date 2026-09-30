<?php

use App\Http\Controllers\Admin\AthleteController as AdminAthleteController;
use App\Http\Controllers\Admin\CountryController as AdminCountryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EventController as AdminEventController;
use App\Http\Controllers\Admin\SportController as AdminSportController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\VenueController as AdminVenueController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MedalController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SportController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\VenueController;
use Illuminate\Support\Facades\Route;

// Pages publiques
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/sports', [SportController::class, 'index'])->name('sports.index');
Route::get('/sports/{sport}', [SportController::class, 'show'])->name('sports.show');
Route::get('/epreuves', [EventController::class, 'index'])->name('events.index');
Route::get('/epreuves/{event}', [EventController::class, 'show'])->name('events.show');
Route::get('/medailles', [MedalController::class, 'index'])->name('medals.index');
Route::get('/pays/{country}', [MedalController::class, 'country'])->name('countries.show');
Route::get('/sites', [VenueController::class, 'index'])->name('venues.index');

// Authentification (limites « forms » et « login » définies dans AppServiceProvider)
Route::middleware('guest')->group(function () {
    Route::get('/inscription', [AuthController::class, 'register'])->name('register');
    Route::post('/inscription', [AuthController::class, 'store'])->middleware('throttle:forms');
    Route::get('/connexion', [AuthController::class, 'login'])->name('login');
    Route::post('/connexion', [AuthController::class, 'authenticate'])->middleware('throttle:login');

    Route::get('/mot-de-passe-oublie', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/mot-de-passe-oublie', [PasswordResetController::class, 'email'])->middleware('throttle:forms')->name('password.email');
    Route::get('/reinitialiser-mot-de-passe/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reinitialiser-mot-de-passe', [PasswordResetController::class, 'update'])->middleware('throttle:forms')->name('password.update');
});
Route::post('/deconnexion', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Espace spectateur
Route::middleware('auth')->group(function () {
    Route::get('/mes-billets', [TicketController::class, 'index'])->name('tickets.index');
    Route::post('/epreuves/{event}/billets', [TicketController::class, 'store'])->name('tickets.store');
    Route::delete('/billets/{ticket}', [TicketController::class, 'destroy'])->name('tickets.destroy');

    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profil/mot-de-passe', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::delete('/profil', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Administration
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::resource('epreuves', AdminEventController::class)->except('show')->parameters(['epreuves' => 'event'])->names('events');
    Route::get('/epreuves/{event}/resultats', [AdminEventController::class, 'editResults'])->name('events.results');
    Route::put('/epreuves/{event}/resultats', [AdminEventController::class, 'updateResults'])->name('events.results.update');
    Route::post('/epreuves/{event}/annuler', [AdminEventController::class, 'cancel'])->name('events.cancel');

    Route::resource('athletes', AdminAthleteController::class)->except('show')->names('athletes');
    Route::resource('sports', AdminSportController::class)->except('show')->names('sports');
    Route::resource('sites', AdminVenueController::class)->except('show')->parameters(['sites' => 'venue'])->names('venues');
    Route::resource('pays', AdminCountryController::class)->except('show')->parameters(['pays' => 'country'])->names('countries');
    Route::resource('utilisateurs', AdminUserController::class)->only(['index', 'update', 'destroy'])->parameters(['utilisateurs' => 'user'])->names('users');
});
