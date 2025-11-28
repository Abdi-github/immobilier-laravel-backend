<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PropertyController;
use App\Http\Controllers\Admin\AgencyController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\LeadController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\AmenityController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\TranslationController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\SettingsController;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Fortify handles: POST /login, POST /logout, POST /user/password,
| PUT /user/profile-information, POST /user/two-factor-authentication,
| etc. automatically via its registered routes.
|
| We only define the view routes and admin-panel routes here.
|
*/

// Root redirect — authenticated admins go to dashboard, others to login
Route::get('/', function () {
    if (auth()->check() && auth()->user()->user_type->isAdmin()) {
        return redirect()->route('admin.dashboard');
    }

    return redirect()->route('login');
});

// Login view (Fortify handles POST /login automatically)
Route::get('/login', function () {
    return Inertia::render('Auth/Login');
})->middleware('guest')->name('login');

// Admin panel routes — session auth + admin user type required
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Properties
    Route::get('/properties', [PropertyController::class, 'index'])->name('properties.index');
    Route::get('/properties/pending', [PropertyController::class, 'pending'])->name('properties.pending');
    Route::get('/properties/create', [PropertyController::class, 'create'])->name('properties.create');
    Route::post('/properties', [PropertyController::class, 'store'])->name('properties.store');
    Route::get('/properties/{property}', [PropertyController::class, 'show'])->name('properties.show');
    Route::get('/properties/{property}/edit', [PropertyController::class, 'edit'])->name('properties.edit');
    Route::put('/properties/{property}', [PropertyController::class, 'update'])->name('properties.update');
    Route::delete('/properties/{property}', [PropertyController::class, 'destroy'])->name('properties.destroy');

    // Property Status Workflow
    Route::post('/properties/{property}/submit', [PropertyController::class, 'submit'])->name('properties.submit');
    Route::post('/properties/{property}/approve', [PropertyController::class, 'approve'])->name('properties.approve');
    Route::post('/properties/{property}/reject', [PropertyController::class, 'reject'])->name('properties.reject');
    Route::post('/properties/{property}/publish', [PropertyController::class, 'publish'])->name('properties.publish');
    Route::post('/properties/{property}/archive', [PropertyController::class, 'archive'])->name('properties.archive');

    // Property Images
    Route::post('/properties/{property}/images', [PropertyController::class, 'uploadImage'])->name('properties.images.upload');
    Route::post('/properties/{property}/images/{imageId}/primary', [PropertyController::class, 'setPrimaryImage'])->name('properties.images.primary');
    Route::delete('/properties/{property}/images/{imageId}', [PropertyController::class, 'deleteImage'])->name('properties.images.destroy');
    Route::post('/properties/{property}/images/reorder', [PropertyController::class, 'reorderImages'])->name('properties.images.reorder');

    // Agencies
    Route::get('/agencies', [AgencyController::class, 'index'])->name('agencies.index');
    Route::get('/agencies/{agency}', [AgencyController::class, 'show'])->name('agencies.show');
    Route::post('/agencies', [AgencyController::class, 'store'])->name('agencies.store');
    Route::put('/agencies/{agency}', [AgencyController::class, 'update'])->name('agencies.update');
    Route::delete('/agencies/{agency}', [AgencyController::class, 'destroy'])->name('agencies.destroy');
    Route::post('/agencies/{agency}/verify', [AgencyController::class, 'verify'])->name('agencies.verify');
    Route::post('/agencies/{agency}/unverify', [AgencyController::class, 'unverify'])->name('agencies.unverify');
    Route::post('/agencies/{agency}/status', [AgencyController::class, 'updateStatus'])->name('agencies.status');

    // Users
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::post('/users/{user}/activate', [UserController::class, 'activate'])->name('users.activate');
    Route::post('/users/{user}/suspend', [UserController::class, 'suspend'])->name('users.suspend');

    // Leads
    Route::get('/leads', [LeadController::class, 'index'])->name('leads.index');
    Route::get('/leads/{lead}', [LeadController::class, 'show'])->name('leads.show');
    Route::patch('/leads/{lead}', [LeadController::class, 'update'])->name('leads.update');
    Route::delete('/leads/{lead}', [LeadController::class, 'destroy'])->name('leads.destroy');
    Route::post('/leads/{lead}/status', [LeadController::class, 'updateStatus'])->name('leads.status');
    Route::post('/leads/{lead}/assign', [LeadController::class, 'assign'])->name('leads.assign');
    Route::post('/leads/{lead}/notes', [LeadController::class, 'addNote'])->name('leads.notes.store');

    // Categories
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    // Amenities
    Route::get('/amenities', [AmenityController::class, 'index'])->name('amenities.index');
    Route::post('/amenities', [AmenityController::class, 'store'])->name('amenities.store');
    Route::put('/amenities/{amenity}', [AmenityController::class, 'update'])->name('amenities.update');
    Route::delete('/amenities/{amenity}', [AmenityController::class, 'destroy'])->name('amenities.destroy');

    // Locations
    Route::get('/locations', [LocationController::class, 'index'])->name('locations.index');
    Route::post('/locations/cantons', [LocationController::class, 'storeCanton'])->name('locations.cantons.store');
    Route::put('/locations/cantons/{canton}', [LocationController::class, 'updateCanton'])->name('locations.cantons.update');
    Route::delete('/locations/cantons/{canton}', [LocationController::class, 'destroyCanton'])->name('locations.cantons.destroy');
    Route::post('/locations/cities', [LocationController::class, 'storeCity'])->name('locations.cities.store');
    Route::put('/locations/cities/{city}', [LocationController::class, 'updateCity'])->name('locations.cities.update');
    Route::delete('/locations/cities/{city}', [LocationController::class, 'destroyCity'])->name('locations.cities.destroy');

    // Translations
    Route::get('/translations', [TranslationController::class, 'index'])->name('translations.index');
    Route::post('/translations/{translation}/approve', [TranslationController::class, 'approve'])->name('translations.approve');
    Route::post('/translations/{translation}/reject', [TranslationController::class, 'reject'])->name('translations.reject');
    Route::post('/translations/{translation}/reset', [TranslationController::class, 'reset'])->name('translations.reset');
    Route::post('/translations/bulk-approve', [TranslationController::class, 'bulkApprove'])->name('translations.bulk-approve');
    Route::delete('/translations/{translation}', [TranslationController::class, 'destroy'])->name('translations.destroy');

    // Roles & Permissions
    Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
    Route::get('/roles/{role}', [RoleController::class, 'show'])->name('roles.show');
    Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
    Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
    Route::put('/roles/{role}/permissions', [RoleController::class, 'syncPermissions'])->name('roles.permissions.sync');
    Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::patch('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/password', [SettingsController::class, 'changePassword'])->name('settings.password');
});
