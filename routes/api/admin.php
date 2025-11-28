<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Admin\AgencyController;
use App\Http\Controllers\Api\Admin\AmenityController;
use App\Http\Controllers\Api\Admin\CategoryController;
use App\Http\Controllers\Api\Admin\LeadController;
use App\Http\Controllers\Api\Admin\LocationController;
use App\Http\Controllers\Api\Admin\PermissionController;
use App\Http\Controllers\Api\Admin\PropertyController;
use App\Http\Controllers\Api\Admin\RoleController;
use App\Http\Controllers\Api\Admin\TranslationController;
use App\Http\Controllers\Api\Admin\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin API Routes
|--------------------------------------------------------------------------
|
| These routes require auth:sanctum + admin middleware.
| Prefix: /api/v1/admin
|
*/

Route::get('/', fn () => response()->json([
    'success' => true,
    'message' => 'Welcome to Immobilier.ch Admin API',
]));

// ── Permissions ──────────────────────────────────────────
Route::prefix('permissions')->group(function (): void {
    Route::get('/resources', [PermissionController::class, 'resources'])->name('admin.permissions.resources');
    Route::get('/grouped', [PermissionController::class, 'grouped'])->name('admin.permissions.grouped');
    Route::get('/active', [PermissionController::class, 'active'])->name('admin.permissions.active');
    Route::get('/name/{name}', [PermissionController::class, 'showByName'])->name('admin.permissions.by-name');
    Route::get('/resource/{resource}', [PermissionController::class, 'byResource'])->name('admin.permissions.by-resource');
    Route::get('/', [PermissionController::class, 'index'])->name('admin.permissions.index');
    Route::post('/', [PermissionController::class, 'store'])->name('admin.permissions.store');
    Route::get('/{id}', [PermissionController::class, 'show'])->name('admin.permissions.show');
    Route::patch('/{id}', [PermissionController::class, 'update'])->name('admin.permissions.update');
    Route::delete('/{id}', [PermissionController::class, 'softDelete'])->name('admin.permissions.soft-delete');
    Route::delete('/{id}/force', [PermissionController::class, 'destroy'])->name('admin.permissions.destroy');
});

// ── Roles ────────────────────────────────────────────────
Route::prefix('roles')->group(function (): void {
    Route::get('/name/{name}', [RoleController::class, 'showByName'])->name('admin.roles.by-name');
    Route::get('/', [RoleController::class, 'index'])->name('admin.roles.index');
    Route::post('/', [RoleController::class, 'store'])->name('admin.roles.store');
    Route::get('/{id}', [RoleController::class, 'show'])->name('admin.roles.show');
    Route::patch('/{id}', [RoleController::class, 'update'])->name('admin.roles.update');
    Route::delete('/{id}', [RoleController::class, 'destroy'])->name('admin.roles.destroy');
    Route::get('/{id}/permissions', [RoleController::class, 'permissions'])->name('admin.roles.permissions');
    Route::put('/{id}/permissions', [RoleController::class, 'setPermissions'])->name('admin.roles.set-permissions');
    Route::post('/{id}/permissions/assign', [RoleController::class, 'assignPermissions'])->name('admin.roles.assign-permissions');
    Route::post('/{id}/permissions/revoke', [RoleController::class, 'revokePermissions'])->name('admin.roles.revoke-permissions');
});

// ── Users ────────────────────────────────────────────────
Route::prefix('users')->group(function (): void {
    Route::get('/statistics', [UserController::class, 'statistics'])->name('admin.users.statistics');
    Route::get('/', [UserController::class, 'index'])->name('admin.users.index');
    Route::post('/', [UserController::class, 'store'])->name('admin.users.store');
    Route::get('/{id}', [UserController::class, 'show'])->name('admin.users.show');
    Route::patch('/{id}', [UserController::class, 'update'])->name('admin.users.update');
    Route::delete('/{id}', [UserController::class, 'destroy'])->name('admin.users.destroy');
    Route::patch('/{id}/status', [UserController::class, 'updateStatus'])->name('admin.users.update-status');
    Route::post('/{id}/suspend', [UserController::class, 'suspend'])->name('admin.users.suspend');
    Route::post('/{id}/activate', [UserController::class, 'activate'])->name('admin.users.activate');
});

// ── Agencies ─────────────────────────────────────────────
Route::prefix('agencies')->group(function (): void {
    Route::get('/statistics', [AgencyController::class, 'statistics'])->name('admin.agencies.statistics');
    Route::get('/', [AgencyController::class, 'index'])->name('admin.agencies.index');
    Route::post('/', [AgencyController::class, 'store'])->name('admin.agencies.store');
    Route::get('/{id}', [AgencyController::class, 'show'])->name('admin.agencies.show');
    Route::patch('/{id}', [AgencyController::class, 'update'])->name('admin.agencies.update');
    Route::delete('/{id}', [AgencyController::class, 'destroy'])->name('admin.agencies.destroy');
    Route::post('/{id}/verify', [AgencyController::class, 'verify'])->name('admin.agencies.verify');
    Route::post('/{id}/unverify', [AgencyController::class, 'unverify'])->name('admin.agencies.unverify');
    Route::patch('/{id}/status', [AgencyController::class, 'updateStatus'])->name('admin.agencies.update-status');
});

// ── Categories ───────────────────────────────────────────
Route::prefix('categories')->group(function (): void {
    Route::post('/', [CategoryController::class, 'store'])->name('admin.categories.store');
    Route::patch('/{id}', [CategoryController::class, 'update'])->name('admin.categories.update');
    Route::delete('/{id}', [CategoryController::class, 'destroy'])->name('admin.categories.destroy');
});

// ── Amenities ────────────────────────────────────────────
Route::prefix('amenities')->group(function (): void {
    Route::get('/', [AmenityController::class, 'index'])->name('admin.amenities.index');
    Route::post('/', [AmenityController::class, 'store'])->name('admin.amenities.store');
    Route::patch('/{id}', [AmenityController::class, 'update'])->name('admin.amenities.update');
    Route::delete('/{id}', [AmenityController::class, 'destroy'])->name('admin.amenities.destroy');
});

// ── Locations ────────────────────────────────────────────
Route::prefix('locations')->group(function (): void {
    Route::post('/cantons', [LocationController::class, 'storeCanton'])->name('admin.locations.cantons.store');
    Route::patch('/cantons/{id}', [LocationController::class, 'updateCanton'])->name('admin.locations.cantons.update');
    Route::delete('/cantons/{id}', [LocationController::class, 'destroyCanton'])->name('admin.locations.cantons.destroy');
    Route::post('/cities', [LocationController::class, 'storeCity'])->name('admin.locations.cities.store');
    Route::patch('/cities/{id}', [LocationController::class, 'updateCity'])->name('admin.locations.cities.update');
    Route::delete('/cities/{id}', [LocationController::class, 'destroyCity'])->name('admin.locations.cities.destroy');
});

// ── Properties ───────────────────────────────────────────
Route::prefix('properties')->group(function (): void {
    Route::get('/statistics', [PropertyController::class, 'statistics'])->name('admin.properties.statistics');
    Route::get('/', [PropertyController::class, 'index'])->name('admin.properties.index');
    Route::post('/', [PropertyController::class, 'store'])->name('admin.properties.store');
    Route::get('/{id}', [PropertyController::class, 'show'])->name('admin.properties.show');
    Route::patch('/{id}', [PropertyController::class, 'update'])->name('admin.properties.update');
    Route::delete('/{id}', [PropertyController::class, 'destroy'])->name('admin.properties.destroy');
    Route::post('/{id}/submit', [PropertyController::class, 'submit'])->name('admin.properties.submit');
    Route::post('/{id}/approve', [PropertyController::class, 'approve'])->name('admin.properties.approve');
    Route::post('/{id}/reject', [PropertyController::class, 'reject'])->name('admin.properties.reject');
    Route::post('/{id}/publish', [PropertyController::class, 'publish'])->name('admin.properties.publish');
    Route::post('/{id}/archive', [PropertyController::class, 'archive'])->name('admin.properties.archive');
    Route::patch('/{id}/status', [PropertyController::class, 'updateStatus'])->name('admin.properties.update-status');
    Route::get('/{id}/images', [PropertyController::class, 'images'])->name('admin.properties.images');
    Route::post('/{id}/images', [PropertyController::class, 'uploadImage'])->name('admin.properties.upload-image');
    Route::post('/{id}/images/batch', [PropertyController::class, 'uploadImages'])->name('admin.properties.upload-images');
    Route::put('/{id}/images/reorder', [PropertyController::class, 'reorderImages'])->name('admin.properties.reorder-images');
    Route::patch('/{id}/images/{imageId}/primary', [PropertyController::class, 'setPrimaryImage'])->name('admin.properties.set-primary');
    Route::delete('/{id}/images/{imageId}', [PropertyController::class, 'deleteImage'])->name('admin.properties.delete-image');
});

// ── Translations ─────────────────────────────────────────
Route::prefix('translations')->group(function (): void {
    Route::get('/pending', [TranslationController::class, 'pending'])->name('admin.translations.pending');
    Route::get('/statistics', [TranslationController::class, 'statistics'])->name('admin.translations.statistics');
    Route::post('/bulk-approve', [TranslationController::class, 'bulkApprove'])->name('admin.translations.bulk-approve');
    Route::get('/', [TranslationController::class, 'index'])->name('admin.translations.index');
    Route::post('/', [TranslationController::class, 'store'])->name('admin.translations.store');
    Route::get('/{id}', [TranslationController::class, 'show'])->name('admin.translations.show');
    Route::patch('/{id}', [TranslationController::class, 'update'])->name('admin.translations.update');
    Route::delete('/{id}', [TranslationController::class, 'destroy'])->name('admin.translations.destroy');
    Route::post('/{id}/approve', [TranslationController::class, 'approve'])->name('admin.translations.approve');
    Route::post('/{id}/reject', [TranslationController::class, 'reject'])->name('admin.translations.reject');
    Route::post('/{id}/reset', [TranslationController::class, 'reset'])->name('admin.translations.reset');
});
Route::get('properties/{propertyId}/translations', [TranslationController::class, 'byProperty'])->name('admin.translations.by-property');

// ── Leads ────────────────────────────────────────────────
Route::prefix('leads')->group(function (): void {
    Route::get('/statistics', [LeadController::class, 'statistics'])->name('admin.leads.statistics');
    Route::get('/follow-up', [LeadController::class, 'followUp'])->name('admin.leads.follow-up');
    Route::get('/property/{propertyId}', [LeadController::class, 'byProperty'])->name('admin.leads.by-property');
    Route::get('/', [LeadController::class, 'index'])->name('admin.leads.index');
    Route::get('/{id}', [LeadController::class, 'show'])->name('admin.leads.show');
    Route::patch('/{id}', [LeadController::class, 'update'])->name('admin.leads.update');
    Route::delete('/{id}', [LeadController::class, 'destroy'])->name('admin.leads.destroy');
    Route::patch('/{id}/status', [LeadController::class, 'updateStatus'])->name('admin.leads.update-status');
    Route::post('/{id}/assign', [LeadController::class, 'assign'])->name('admin.leads.assign');
    Route::get('/{id}/notes', [LeadController::class, 'notes'])->name('admin.leads.notes');
    Route::post('/{id}/notes', [LeadController::class, 'addNote'])->name('admin.leads.add-note');
});

// ── Search ───────────────────────────────────────────────
// Route::post('search/cache/invalidate', [SearchController::class, 'invalidateCache'])->name('admin.search.invalidate');
