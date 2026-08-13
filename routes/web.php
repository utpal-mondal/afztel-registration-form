<?php

use App\Http\Controllers\RegistrationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

Route::get('/', [RegistrationController::class, 'create'])->name('home');
Route::get('/register', [RegistrationController::class, 'create'])->name('register.create');
Route::post('/register', [RegistrationController::class, 'store'])
    ->middleware('throttle:6,1')
    ->name('register.store');
Route::get('/register/success', [RegistrationController::class, 'success'])->name('register.success');

Route::get('/testmail', function () {
    return view('testmail');
})->name('testmail');

Route::post('/testmail', function (Request $request) {
    $request->validate(['email' => 'required|email']);

    try {
        $company = config('registration.company.name');

        Mail::raw('This is a test mail from ' . $company . '.', function ($message) use ($request, $company) {
            $message->to($request->email)
                    ->subject($company . ' - Test Mail');
        });

        return back()->with('status', 'Test mail sent to ' . $request->email);
    } catch (\Throwable $e) {
        return back()
            ->with('error', 'Mail failed: ' . $e->getMessage())
            ->withInput();
    }
})->name('testmail.send');
