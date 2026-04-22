<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Teamleider\DashboardController as TeamleiderDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'store'])
    ->middleware('guest')
    ->name('login.store');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::middleware('zorgbegeleider')->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
    });

    Route::middleware('teamleider')->prefix('teamleider')->name('teamleider.')->group(function () {
        Route::get('/dashboard', TeamleiderDashboardController::class)->name('dashboard');
    });
});
