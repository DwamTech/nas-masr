<?php

use App\Http\Controllers\Admin\categoryController;
use App\Http\Controllers\Admin\CategoryFieldsController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CarController;
use App\Http\Controllers\api\UserController;
use App\Support\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\SystemSettingController;
use App\Http\Controllers\Admin\StatsController;



Route::post('/register', [AuthController::class, 'register']);
Route::get('v1/test', fn() => response()->json(['ok' => true]));

// Public Category Fields Route
Route::get('category-fields', [CategoryFieldsController::class, 'index']);
// Public Categories Route
Route::get('categories', [categoryController::class, 'index']);

Route::get('/system-settings', [SystemSettingController::class, 'index']);

//public listing for specific user
Route::get('users/{user}', [UserController::class, 'showUserWithListings']);

Route::prefix('v1/{section}')->group(function () {

    Route::get('ping', function (string $section) {
        $sec = Section::fromSlug($section);
        return response()->json([
            'section' => $sec->slug,
            'category_id' => $sec->id(),
            'fields_count' => count($sec->fields),
        ]);
    });

    Route::post('validate-sample', function (App\Http\Requests\GenericListingRequest $req) {
        return response()->json(['ok' => true, 'data' => $req->validated()]);
    });


    // Route::apiResource('listings', ListingController::class)->only(['index', 'show']);

    Route::apiResource('listings', ListingController::class)->only(['index', 'show']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::apiResource('listings', ListingController::class)->only(['store', 'update', 'destroy']);
    });
});


Route::prefix('admin')
    ->middleware(['auth:sanctum', 'admin'])
    ->group(function () {
        // Category Fields Routes
        Route::post('category-fields', [CategoryFieldsController::class, 'store']);
        Route::put('category-fields/{categoryField}', [CategoryFieldsController::class, 'update']);
        Route::delete('category-fields/{categoryField}', [CategoryFieldsController::class, 'destroy']);

        // Category Routes
        Route::post('categories', [CategoryController::class, 'store']);
        Route::put('categories/{category}', [CategoryController::class, 'update']);
        Route::delete('categories/{category}', [CategoryController::class, 'destroy']);

        // System Settings Routes
        // Route::apiResource('system-settings', SystemSettingController::class);

        // Admin Stats Route
        Route::get('stats', [StatsController::class, 'index']);
        Route::get('recent-activities', [StatsController::class, 'recentActivities']);
        Route::get('users-summary', [StatsController::class, 'usersSummary']);

        // Admin Users management
    Route::get('users/{user}', [UserController::class, 'showUserWithListings']);
    Route::put('users/{user}', [UserController::class, 'updateUser']);
    Route::post('users', [UserController::class, 'storeUser']);
    Route::delete('users/{user}', [UserController::class, 'deleteUser']);
        Route::patch('users/{user}/block', [UserController::class, 'blockedUser']);
        // Route::get('users/{user}/listings', [UserController::class, 'userListings']);

    });

Route::get('/all-cars', [CarController::class, 'index']);
Route::middleware('auth:sanctum')->post('/add-car', [CarController::class, 'store']);

// Route::get('/values/{categorySlug?}', [CategoryFieldsController::class, 'index']);


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [UserController::class, 'logout']);
    Route::get('/get-profile', [UserController::class, 'getUserProfile']);
});

