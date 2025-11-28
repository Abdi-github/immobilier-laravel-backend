<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Agency\LeadController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Agency API Routes
|--------------------------------------------------------------------------
|
| These routes require auth:sanctum + agency.member middleware.
| Prefix: /api/v1/agency
|
*/

Route::get('/', fn () => response()->json([
    'success' => true,
    'message' => 'Welcome to Immobilier.ch Agency API',
]));

// Leads (agency-scoped)
Route::prefix('leads')->group(function (): void {
    Route::get('/statistics', [LeadController::class, 'statistics'])->name('agency.leads.statistics');
    Route::get('/follow-up', [LeadController::class, 'followUp'])->name('agency.leads.follow-up');
    Route::get('/property/{propertyId}', [LeadController::class, 'byProperty'])->name('agency.leads.by-property');
    Route::get('/', [LeadController::class, 'index'])->name('agency.leads.index');
    Route::get('/{id}', [LeadController::class, 'show'])->name('agency.leads.show');
    Route::patch('/{id}', [LeadController::class, 'update'])->name('agency.leads.update');
    Route::patch('/{id}/status', [LeadController::class, 'updateStatus'])->name('agency.leads.update-status');
    Route::post('/{id}/assign', [LeadController::class, 'assign'])->name('agency.leads.assign');
    Route::get('/{id}/notes', [LeadController::class, 'notes'])->name('agency.leads.notes');
    Route::post('/{id}/notes', [LeadController::class, 'addNote'])->name('agency.leads.add-note');
});
