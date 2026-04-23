<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TeamController;
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

    Route::middleware('teamleider')->group(function () {
        Route::get('/team', [TeamController::class, 'index'])->name('team.index');
        Route::get('/team/create', [TeamController::class, 'create'])->name('team.create');
        Route::post('/team', [TeamController::class, 'store'])->name('team.store');
        Route::get('/team/{user}/edit', [TeamController::class, 'edit'])->name('team.edit');
        Route::put('/team/{user}', [TeamController::class, 'update'])->name('team.update');
        Route::post('/team/{user}/deactivate', [TeamController::class, 'deactivate'])->name('team.deactivate');
        Route::post('/team/{user}/activate', [TeamController::class, 'activate'])->name('team.activate');
    });

    // Cliëntbeheer — autorisatie via ClientPolicy (US-02).
    // Beschikbaar voor beide rollen; scope wordt afgedwongen in service+policy.
    Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
    Route::get('/clients/create', [ClientController::class, 'create'])->name('clients.create');
    Route::post('/clients', [ClientController::class, 'store'])->name('clients.store');
    Route::get('/clients/{client}/caregivers', [ClientController::class, 'editCaregivers'])->name('clients.caregivers.edit');
    Route::put('/clients/{client}/caregivers', [ClientController::class, 'updateCaregivers'])->name('clients.caregivers.update');
    Route::get('/clients/{client}', [ClientController::class, 'show'])->name('clients.show');
});
