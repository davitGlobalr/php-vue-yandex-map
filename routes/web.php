<?php

use App\Http\Controllers\Api\ParserController;
use App\Http\Controllers\ConnectController;
use App\Http\Controllers\ReviewsController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('reviews', [ReviewsController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('admin.reviews');

Route::get('reviews/{place}', [ReviewsController::class, 'show'])
    ->middleware(['auth', 'verified'])
    ->name('admin.place-reviews');

Route::get('connect', [ConnectController::class, 'show'])
    ->middleware(['auth', 'verified'])
    ->name('admin.connect');

Route::post('connect', [ConnectController::class, 'store'])
    ->middleware(['auth', 'verified'])
    ->name('admin.connect.store');

Route::prefix('api/parser')->group(function () {
    Route::post('/parse', [ParserController::class, 'parse'])
        ->middleware(['auth', 'verified'])
        ->name('api.parser.parse');

    Route::post('/import', [ParserController::class, 'import'])
        ->name('api.parser.import');

    Route::post('/needs-manual', [ParserController::class, 'needsManual'])
        ->name('api.parser.needs-manual');
});

require __DIR__.'/settings.php';
