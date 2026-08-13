<?php

use App\Http\Controllers\RegistrationController;
use Illuminate\Support\Facades\Route;

Route::get('/', [RegistrationController::class, 'create'])->name('home');
Route::get('/register', [RegistrationController::class, 'create'])->name('register.create');
Route::post('/register', [RegistrationController::class, 'store'])
    ->middleware('throttle:6,1')
    ->name('register.store');
Route::get('/register/success', [RegistrationController::class, 'success'])->name('register.success');
