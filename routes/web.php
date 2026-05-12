<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');

    // Tickets routes
    Volt::route('tickets', 'ticket-list')->name('tickets');
    Volt::route('tickets/create', 'ticket-create')->name('tickets.create');
    Volt::route('tickets/{id}', 'ticket-detail')->name('tickets.detail');
});

require __DIR__ . '/auth.php';
