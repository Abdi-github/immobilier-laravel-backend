<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| All API routes are prefixed with /api/v1 (configured in bootstrap/app.php).
| Routes are split by access level into separate files.
|
*/

// Public routes (no auth required for most, some routes require auth)
Route::prefix('public')->group(base_path('routes/api/public.php'));

// Admin routes (auth + admin middleware)
// Name prefix 'api.' avoids collision with Inertia web admin route names
Route::prefix('admin')
    ->middleware(['auth:sanctum', 'admin'])
    ->name('api.')
    ->group(base_path('routes/api/admin.php'));

// Agency routes (auth + agency membership middleware)
Route::prefix('agency')
    ->middleware(['auth:sanctum', 'agency.member'])
    ->group(base_path('routes/api/agency.php'));

// Agent routes (auth required)
Route::prefix('agent')
    ->middleware(['auth:sanctum'])
    ->group(base_path('routes/api/agent.php'));
