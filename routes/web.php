<?php

use Illuminate\Support\Facades\Route;

// Public door display
Route::get('/screen/{slug}', fn (string $slug) => view('screen', compact('slug')))->name('screen');

// Auth
Route::get('/login', fn () => view('auth.login'))->name('login')->middleware('guest');

// Admin (protected)
Route::middleware('auth')->group(function () {
    Route::get('/admin', fn () => view('admin.dashboard'))->name('admin.dashboard');
});

Route::view('/', 'welcome')->name('home');
