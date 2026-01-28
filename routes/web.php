<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;


// Redireciona / para login se não autenticado
Route::get('/', function () {
    return redirect()->route('login');
});

// Autenticação
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
Route::get('/reset-password/{token}', [AuthController::class, 'showResetForm'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');

// Rotas protegidas
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return view('home');
    })->name('dashboard');
    Route::get('/habits', function () {
        return view('habits');
    });
    Route::get('/tasks', function () {
        return view('tasks');
    });
    Route::get('/ranking', function () {
        return view('ranking');
    });
    Route::get('/achievements', function () {
        return view('achievements');
    });
    Route::get('/store', function () {
        return view('store');
    });
    Route::get('/goals', function () {
        return view('goals');
    });
    Route::get('/history', function () {
        return view('history');
    });
});
