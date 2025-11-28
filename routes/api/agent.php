<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Agent\LeadController;
use App\Http\Controllers\Api\Agent\PropertyController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Agent API Routes
|--------------------------------------------------------------------------
|
| These routes require auth:sanctum middleware.
| Prefix: /api/v1/agent
|
*/

Route::get('/', fn () => response()->json([
    'success' => true,
    'message' => 'Welcome to Immobilier.ch Agent API',
]));

// ── Properties (ownership-scoped) ──
Route::prefix('properties')->group(function (): void {
    Route::get('/', [PropertyController::class, 'index'])->name('agent.properties.index');
    Route::post('/', [PropertyController::class, 'store'])->name('agent.properties.store');
    Route::get('/{id}', [PropertyController::class, 'show'])->name('agent.properties.show');
    Route::put('/{id}', [PropertyController::class, 'update'])->name('agent.properties.update');
    Route::delete('/{id}', [PropertyController::class, 'destroy'])->name('agent.properties.destroy');
    Route::post('/{id}/submit', [PropertyController::class, 'submit'])->name('agent.properties.submit');
    Route::get('/{id}/images', [PropertyController::class, 'images'])->name('agent.properties.images');
    Route::post('/{id}/images', [PropertyController::class, 'uploadImage'])->name('agent.properties.upload-image');
    Route::delete('/{id}/images/{imageId}', [PropertyController::class, 'deleteImage'])->name('agent.properties.delete-image');
});

// ── Leads (assigned only) ──
Route::prefix('leads')->group(function (): void {
    Route::get('/follow-up', [LeadController::class, 'followUp'])->name('agent.leads.follow-up');
    Route::get('/', [LeadController::class, 'index'])->name('agent.leads.index');
    Route::get('/{id}', [LeadController::class, 'show'])->name('agent.leads.show');
});
