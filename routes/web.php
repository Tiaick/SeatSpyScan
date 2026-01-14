<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Seat\SpyDetection\Http\Controllers\SpyDetectionController;

Route::middleware(['web', 'auth', 'can:spy-detection.view'])
    ->prefix('spy-detection')
    ->group(function (): void {
        Route::get('/', [SpyDetectionController::class, 'index'])
            ->name('spy-detection.index');
        Route::post('/scan', [SpyDetectionController::class, 'scan'])
            ->name('spy-detection.scan');
        Route::get('/scan/{token}', [SpyDetectionController::class, 'status'])
            ->name('spy-detection.status');
    });
