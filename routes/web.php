<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\RescueController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/ranking', [PersonController::class, 'ranking'])->name('ranking');
Route::get('/people/{user}', [PersonController::class, 'show'])->name('people.show');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'redirect'])->name('login');
    Route::get('/auth/callback', [AuthController::class, 'callback']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/people-search', [PersonController::class, 'search'])->name('people.search');
    Route::resource('rescues', RescueController::class)->except('index', 'show');
});

Route::resource('rescues', RescueController::class)->only('index', 'show');

// Local only (checked in the controller): log in as a seeded user without ISA.
Route::post('/dev/login/{user}', [AuthController::class, 'devLogin'])->name('dev.login');

// One route per Markdown file in resources/content (home.md is rendered on "/").
Route::get('/{slug}', [PageController::class, 'show'])
    ->whereIn('slug', array_map(fn ($f) => basename($f, '.md'), glob(resource_path('content/*.md'))))
    ->name('page');
