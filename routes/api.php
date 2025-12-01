<?php

use App\Http\Controllers\Admin\categoryController;
use App\Http\Controllers\Admin\CategoryFieldsController;
use App\Http\Controllers\Admin\CategorySectionsController;
use App\Http\Controllers\Admin\PackagesController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CarController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\FavoriteController;
use App\Support\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\SystemSettingController;
use App\Http\Controllers\Admin\StatsController;
use App\Http\Controllers\BestAdvertiserController;
use App\Models\Listing as ListingModel;
use App\Http\Controllers\Admin\GovernorateController;
use App\Http\Controllers\Admin\MakeController;
use App\Http\Controllers\Api\NotificationController;

Route::post('/register', [AuthController::class, 'register']);
Route::get('v1/test', fn() => response()->json(['ok' => true]));

// Public Category Fields Route
Route::get('category-fields', [CategoryFieldsController::class, 'index']);
// Public Categories Route
Route::get('categories', [categoryController::class, 'index']);
//public governorates routes
Route::get('governorates', [GovernorateController::class, 'index']);
Route::get('governorates/{governorate}/cities', [GovernorateController::class, 'cities']);

//public makes routes
Route::get('makes', [MakeController::class, 'index']);

Route::get('makes/{make}/models', [MakeController::class, 'models']);

//public main category
Route::get('/main-sections', [CategorySectionsController::class, 'index']);
Route::get('/sub-sections/{mainSection}', [CategorySectionsController::class, 'subSections']);

Route::get('/system-settings', [SystemSettingController::class, 'index']);

//public listing for specific user
Route::get('users/{user}', [UserController::class, 'showUserWithListings']);
//get public  listing best
Route::get('/the-best/{section}', [BestAdvertiserController::class, 'index']);

Route::prefix('v1/{section}')->group(function () {
    Route::bind('listing', function ($value) {
        $section = request()->route('section');
        $sec = Section::fromSlug($section);
        return ListingModel::where('id', $value)->where('category_id', $sec->id())->firstOrFail();
    });

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
        Route::post('category-fields/{categoryField}', [CategoryFieldsController::class, 'update']);
        Route::delete('category-fields/{categoryField}', [CategoryFieldsController::class, 'destroy']);

        // Category Routes
        Route::post('categories', [CategoryController::class, 'store']);
        Route::put('categories/{category}', [CategoryController::class, 'update']);
        Route::delete('categories/{category}', [CategoryController::class, 'destroy']);
        //all categories for admin
        Route::get('categories', [categoryController::class, 'index']);

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

        //Best Advertiser
        Route::post('/featured', [BestAdvertiserController::class, 'store']);
        Route::put('/disable/{bestAdvertiser}', [BestAdvertiserController::class, 'disable']);
        //change  password
        Route::put('/change-password/{user}', [AuthController::class, 'changePass']);
        //create otp
        Route::post('/create-otp/{user}', [UserController::class, 'createOtp']);

        //system settings
        Route::post('/system-settings', [SystemSettingController::class, 'store']);
        //User packages
        Route::post('/user-packages', [PackagesController::class, 'storeOrUpdate']);


        Route::post('governorates', [GovernorateController::class, 'storeGov']);
        Route::post('/city/{governorate}', [GovernorateController::class, 'storCities']);
        Route::put('governorates/{governorate}', [GovernorateController::class, 'updateGov']);
        Route::delete('governorates/{governorate}', [GovernorateController::class, 'destroyGov']);
        // Route::post('governorates/{governorate}/cities', [GovernorateController::class, 'addCity']);
        Route::put('cities/{city}', [GovernorateController::class, 'updateCity']);
        Route::delete('cities/{city}', [GovernorateController::class, 'deleteCity']);


        Route::post('makes', [MakeController::class, 'addMake']);
        Route::put('makes/{make}', [MakeController::class, 'update']);
        Route::delete('makes/{make}', [MakeController::class, 'destroy']);

        Route::post('makes/{make}/models', [MakeController::class, 'addModel']);
        Route::put('models/{model}', [MakeController::class, 'updateModel']);
        Route::delete('models/{model}', [MakeController::class, 'deleteModel']);


        Route::post('/main-section/{categorySlug}', [CategorySectionsController::class, 'storeMain']);
        Route::post('/sub-section/{mainSection}', [CategorySectionsController::class, 'addSubSections']);
        Route::put('/main-section/{mainSection}', [CategorySectionsController::class, 'updateMain']);
        Route::put('/sub-section/{subSection}', [CategorySectionsController::class, 'updateSub']);
        Route::delete('/main-section/{mainSection}', [CategorySectionsController::class, 'destroyMain']);
        Route::delete('/sub-section/{subSection}', [CategorySectionsController::class, 'destroySub']);
    });

Route::get('/all-cars', [CarController::class, 'index']);
Route::middleware('auth:sanctum')->post('/add-car', [CarController::class, 'store']);

// Route::get('/values/{categorySlug?}', [CategoryFieldsController::class, 'index']);


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [UserController::class, 'logout']);
    //verify otp
    Route::post('/verify-otp', [UserController::class, 'verifyOtp']);
    Route::get('/get-profile', [UserController::class, 'getUserProfile']);
    Route::put('/edit-profile', [UserController::class, 'editProfile']);
    Route::get('/my-ads', [UserController::class, 'myAds']);
    Route::get('/my-packages', [UserController::class, 'myPackages']);
    Route::post('/create-agent-code', [UserController::class, 'storeAgent']);
    Route::get('/all-clients', [UserController::class, 'allClients']);
    Route::post('/set-rank-one', [UserController::class, 'SetRankOne']);
    Route::patch('/payment/{id}', [UserController::class, 'payment']);

    Route::get('/favorites', [FavoriteController::class, 'index']);
    Route::post('/favorite', [FavoriteController::class, 'toggle']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/status', [NotificationController::class, 'status']);
    Route::post('/notifications', [NotificationController::class, 'store'])->middleware('admin');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead']);
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::post('/notifications/read', [NotificationController::class, 'read']);
});
    Route::post('/settings/notifications', [UserController::class, 'updateNotificationSettings']);
