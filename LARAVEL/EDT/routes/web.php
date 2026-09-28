<?php

use App\Http\Controllers\AdminGroupController;
use App\Http\Controllers\AdminLicenceController;
use App\Http\Controllers\AdminPasswordResetController;
use App\Http\Controllers\AdminRoomController;
use App\Http\Controllers\AdminStudentController;
use App\Http\Controllers\AdminSubjectController;
use App\Http\Controllers\AdminTeacherController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LessonController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware('auth')
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::view('/profile', 'profile.edit')->name('profile.edit');
    Route::get('/calendar.ics', CalendarController::class)->name('calendar');

    Route::post('/lessons', [LessonController::class, 'store'])->name('lessons.store');
    Route::delete('/lessons/{lesson}', [LessonController::class, 'destroy'])->name('lessons.destroy');

    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
        Route::resource('teachers', AdminTeacherController::class);
        Route::post('teachers/{teacher}/transfer', [AdminTeacherController::class, 'transferLessons'])->name('teachers.transfer');
        Route::resource('students', AdminStudentController::class)->except('show');
        Route::post('users/{user}/reset-password', AdminPasswordResetController::class)->name('users.password.reset');

        Route::resource('subjects', AdminSubjectController::class)->only(['create', 'store', 'destroy']);
        Route::resource('licences', AdminLicenceController::class)->except('show');
        Route::resource('groups', AdminGroupController::class)->except('index');
        Route::resource('rooms', AdminRoomController::class);
    });
});

require __DIR__.'/auth.php';
