<?php

use App\Http\Controllers\ApplicationEmailController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\LevelController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\WeatherController;
use App\Http\Middleware\EnsureMembershipAdmin;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/about', [PageController::class, 'about']);

Route::get('/tasks/{id}', [PageController::class, 'task']);

Route::get('/tasks', [TaskController::class, 'index'])
    ->name('tasks.index');

Route::post('/tasks', [TaskController::class, 'store'])
    ->name('tasks.store');

Route::get('/login', [LoginController::class, 'create'])->middleware('guest')->name('login');
Route::post('/login', [LoginController::class, 'store'])->middleware('guest')->name('login.store');
Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');
Route::middleware(['auth', EnsureMembershipAdmin::class])->group(function () {
    Route::get('/weather', [WeatherController::class, 'show'])->middleware('throttle:weather')->name('weather.show');
    Route::post('transactions/{transaction}/application-email', [ApplicationEmailController::class, 'store'])->middleware('throttle:application-email')->name('transactions.application-email');
    Route::patch('members/{member}/deactivate', [MemberController::class, 'deactivate'])->name('members.deactivate');
    Route::resource('members', MemberController::class);
    Route::resource('categories', CategoryController::class);
    Route::resource('levels', LevelController::class);
    Route::resource('transactions', TransactionController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('transactions/{transaction}/approve', [TransactionController::class, 'approve'])->name('transactions.approve');
    Route::post('transactions/{transaction}/cancel', [TransactionController::class, 'cancel'])->name('transactions.cancel');
    Route::post('transactions/{transaction}/payments', [PaymentController::class, 'store'])->name('payments.store');
    Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
});
