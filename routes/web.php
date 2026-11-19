<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DisciplinaryActionController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\OvertimeParameterController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegisteredHourController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Redirection accueil vers login
Route::get('/', function () {
    return redirect()->route('login');
});

// Routes protégées par authentification
Route::middleware(['auth', 'verified'])->group(function () {

    // Tableau de bord accessible à tous les utilisateurs connectés
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('role:admin,rh,manager')->group(function () {
        Route::get('/hours', [RegisteredHourController::class, 'index'])->name('hours.index');
        Route::post('/hours', [RegisteredHourController::class, 'store'])->name('hours.store');
    });

    Route::middleware('role:admin,rh,manager')->group(function () {
        Route::get('/hours/summary', [RegisteredHourController::class, 'summary'])->name('hours.summary');
    });

    Route::middleware('role:admin,rh')->group(function () {
        Route::get('/hours/settings', [OvertimeParameterController::class, 'index'])->name('hours.settings');
        Route::put('/hours/settings', [OvertimeParameterController::class, 'update'])->name('hours.settings.update');
    });

    Route::middleware('role:admin,rh,manager')->group(function () {
        Route::put('/hours/{registeredHour}', [RegisteredHourController::class, 'update'])->name('hours.update');
        Route::delete('/hours/{registeredHour}', [RegisteredHourController::class, 'destroy'])->name('hours.destroy');
    });

    // Profil Utilisateur
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware('role:admin,rh')->group(function () {
        Route::get('/employees/birthdays', [EmployeeController::class, 'birthdays'])->name('employees.birthdays');
    });

    Route::middleware('role:admin,rh,manager')->group(function () {
        Route::patch('/employees/{employee}/status', [EmployeeController::class, 'updateStatus'])->name('employees.status');
        Route::resource('employees', EmployeeController::class)->only(['index', 'store', 'update']);

        // Discipline & Sanctions
        Route::get('/discipline', [DisciplinaryActionController::class, 'index'])->name('discipline.index');
        Route::post('/discipline', [DisciplinaryActionController::class, 'store'])->name('discipline.store');
        Route::put('/discipline/{disciplinaryAction}', [DisciplinaryActionController::class, 'update'])->name('discipline.update');
        Route::delete('/discipline/{disciplinaryAction}', [DisciplinaryActionController::class, 'destroy'])->name('discipline.destroy');
        Route::get('/discipline/statistics', [DisciplinaryActionController::class, 'statistics'])->name('discipline.statistics');
    });

    Route::middleware('role:admin')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
    });
});

require __DIR__.'/auth.php';
