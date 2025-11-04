<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Temporary editor route without DB dependency
Route::get('editor/{id}', function (string $id) {
    return Inertia::render('editor/[id]', [
        'id' => $id,
    ]);
})->name('editor.show');

require __DIR__.'/settings.php';
require __DIR__.'/organizations-global.php';
