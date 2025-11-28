<?php

declare(strict_types=1);

use App\Http\Controllers\Api\PublicApi\AgencyController;
use App\Http\Controllers\Api\PublicApi\AmenityController;
use App\Http\Controllers\Api\PublicApi\AuthController;
use App\Http\Controllers\Api\PublicApi\CantonController;
use App\Http\Controllers\Api\PublicApi\CategoryController;
use App\Http\Controllers\Api\PublicApi\CityController;
use App\Http\Controllers\Api\PublicApi\LeadController;
use App\Http\Controllers\Api\PublicApi\PropertyController;
use App\Http\Controllers\Api\PublicApi\PropertyTranslationController;
use App\Http\Controllers\Api\PublicApi\SearchController;
use App\Http\Controllers\Api\PublicApi\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public API Routes
|--------------------------------------------------------------------------
|
| These routes are accessible without authentication (except where noted).
| Prefix: /api/v1/public
|
*/

// Health & welcome
Route::get('/', fn () => response()->json([
    'success' => true,
    'message' => __('common.welcome'),
    'data' => [
        'version' => 'v1',
        'status' => 'active',
    ],
]));

// Auth routes
Route::prefix('auth')->group(function (): void {
    // Public auth (no auth required)
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('/refresh', [AuthController::class, 'refresh'])->name('auth.refresh');
    Route::post('/verify-email', [AuthController::class, 'verifyEmail'])->name('auth.verify-email');
    Route::post('/resend-verification', [AuthController::class, 'resendVerification'])->name('auth.resend-verification');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('auth.forgot-password');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('auth.reset-password');

    // Auth-required routes
    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/me', [AuthController::class, 'me'])->name('auth.me');
        Route::patch('/me', [AuthController::class, 'updateMe'])->name('auth.update-profile');
        Route::post('/change-password', [AuthController::class, 'changePassword'])->name('auth.change-password');
    });
});

// Public resources (read-only)
// Agencies
Route::get('agencies', [AgencyController::class, 'index'])->name('agencies.index');
Route::get('agencies/slug/{slug}', [AgencyController::class, 'showBySlug'])->name('agencies.by-slug');
Route::get('agencies/canton/{cantonId}', [AgencyController::class, 'byCanton'])->name('agencies.by-canton');
Route::get('agencies/city/{cityId}', [AgencyController::class, 'byCity'])->name('agencies.by-city');
Route::get('agencies/{id}', [AgencyController::class, 'show'])->name('agencies.show');

// Amenities
Route::get('amenities', [AmenityController::class, 'index'])->name('amenities.index');
Route::get('amenities/group/{group}', [AmenityController::class, 'byGroup'])->name('amenities.by-group');
Route::get('amenities/{id}', [AmenityController::class, 'show'])->name('amenities.show');

// Categories
Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
Route::get('categories/slug/{slug}', [CategoryController::class, 'showBySlug'])->name('categories.by-slug');
Route::get('categories/section/{section}', [CategoryController::class, 'bySection'])->name('categories.by-section');
Route::get('categories/{id}', [CategoryController::class, 'show'])->name('categories.show');

// Locations
Route::prefix('locations')->group(function (): void {
    Route::get('cantons', [CantonController::class, 'index'])->name('cantons.index');
    Route::get('cantons/code/{code}', [CantonController::class, 'showByCode'])->name('cantons.by-code');
    Route::get('cantons/{id}/cities', [CantonController::class, 'cities'])->name('cantons.cities');
    Route::get('cantons/{id}', [CantonController::class, 'show'])->name('cantons.show');
    Route::get('cities', [CityController::class, 'index'])->name('cities.index');
    Route::get('cities/popular', [CityController::class, 'popular'])->name('cities.popular');
    Route::get('cities/search', [CityController::class, 'search'])->name('cities.search');
    Route::get('cities/postal/{postalCode}', [CityController::class, 'byPostalCode'])->name('cities.by-postal');
    Route::get('cities/{id}', [CityController::class, 'show'])->name('cities.show');
});

// Properties
Route::get('properties', [PropertyController::class, 'index'])->name('properties.index');
Route::get('properties/cursor', [PropertyController::class, 'cursor'])->name('properties.cursor');
Route::get('properties/external/{externalId}', [PropertyController::class, 'showByExternalId'])->name('properties.by-external');
Route::get('properties/canton/{cantonId}', [PropertyController::class, 'byCanton'])->name('properties.by-canton');
Route::get('properties/city/{cityId}', [PropertyController::class, 'byCity'])->name('properties.by-city');
Route::get('properties/agency/{agencyId}', [PropertyController::class, 'byAgency'])->name('properties.by-agency');
Route::get('properties/category/{categoryId}', [PropertyController::class, 'byCategory'])->name('properties.by-category');
Route::get('properties/{id}/images', [PropertyController::class, 'images'])->name('properties.images');
Route::get('properties/{id}', [PropertyController::class, 'show'])->name('properties.show');

// Property Translations
Route::get('properties/{id}/translations', [PropertyTranslationController::class, 'index'])->name('translations.index');
Route::get('properties/{id}/translations/{language}', [PropertyTranslationController::class, 'show'])->name('translations.show');

// Search
Route::prefix('search')->group(function (): void {
    Route::get('/', [SearchController::class, 'search'])->name('search.index');
    Route::get('/properties', [SearchController::class, 'properties'])->name('search.properties');
    Route::get('/properties/cursor', [SearchController::class, 'propertiesCursor'])->name('search.properties.cursor');
    Route::get('/locations', [SearchController::class, 'locations'])->name('search.locations');
    Route::get('/suggestions', [SearchController::class, 'suggestions'])->name('search.suggestions');
    Route::get('/facets', [SearchController::class, 'facets'])->name('search.facets');
});

// Leads (public submission)
Route::post('leads', [LeadController::class, 'store'])->name('leads.store');

// Authenticated leads & user features
Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('leads/authenticated', [LeadController::class, 'storeAuthenticated'])->name('leads.store-authenticated');
    Route::get('leads/my-inquiries', [LeadController::class, 'myInquiries'])->name('leads.my-inquiries');

    // User features
    Route::prefix('users')->group(function (): void {
        Route::get('dashboard/stats', [UserController::class, 'dashboardStats'])->name('users.dashboard-stats');
        Route::delete('account', [UserController::class, 'deactivate'])->name('users.deactivate');
        Route::post('avatar', [UserController::class, 'uploadAvatar'])->name('users.upload-avatar');
        Route::delete('avatar', [UserController::class, 'deleteAvatar'])->name('users.delete-avatar');
        Route::get('settings', [UserController::class, 'getSettings'])->name('users.get-settings');
        Route::patch('settings', [UserController::class, 'updateSettings'])->name('users.update-settings');
        Route::get('profile', [UserController::class, 'getProfile'])->name('users.get-profile');
        Route::put('profile', [UserController::class, 'updateProfile'])->name('users.update-profile');
        Route::get('favorites', [UserController::class, 'listFavorites'])->name('users.favorites.index');
        Route::post('favorites', [UserController::class, 'addFavorite'])->name('users.favorites.store');
        Route::get('favorites/ids', [UserController::class, 'favoriteIds'])->name('users.favorites.ids');
        Route::get('favorites/{propertyId}', [UserController::class, 'checkFavorite'])->name('users.favorites.check');
        Route::delete('favorites/{propertyId}', [UserController::class, 'removeFavorite'])->name('users.favorites.destroy');
        Route::get('alerts', [UserController::class, 'listAlerts'])->name('users.alerts.index');
        Route::post('alerts', [UserController::class, 'createAlert'])->name('users.alerts.store');
        Route::get('alerts/{id}', [UserController::class, 'showAlert'])->name('users.alerts.show');
        Route::put('alerts/{id}', [UserController::class, 'updateAlert'])->name('users.alerts.update');
        Route::delete('alerts/{id}', [UserController::class, 'deleteAlert'])->name('users.alerts.destroy');
        Route::patch('alerts/{id}/toggle', [UserController::class, 'toggleAlert'])->name('users.alerts.toggle');
    });
});
