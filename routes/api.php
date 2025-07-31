<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AuthController,
    TourController,
    TourCategoryController,
    AlbumController,
    AlbumImageController,
    DestinationCategoryController,
    BookingController,
    DestinationController,
    OtpController,
    UserController,
    PromotionController,
    SiteSettingController,
    ReviewController,
    FavoriteController,
    GuideController,
    HotelController,
    BusRouteController,
    MotorbikeController,
    TourDestinationController,
    TourScheduleController
};

// ================= PUBLIC ROUTES =================
Route::post('/register', [AuthController::class, 'register']);
Route::post('/otp/send', [OtpController::class, 'sendOtp']);
Route::post('/otp/verify', [OtpController::class, 'verifyOtp']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/tours', [TourController::class, 'index']);
Route::get('/tours/{id}', [TourController::class, 'show']);

Route::get('/tour-categories', [TourCategoryController::class, 'index']);
Route::get('/tour-categories/{id}', [TourCategoryController::class, 'show']);
Route::get('/tours/slug/{slug}', [TourController::class, 'getBySlug']);

Route::get('/destinations', [DestinationController::class, 'index']);
Route::get('/destinations/{id}', [DestinationController::class, 'show']);
Route::get('/destinations/highlights', [DestinationController::class, 'highlights']);
Route::get('/destinations/slug/{slug}', [DestinationController::class, 'showBySlug']);

Route::get('/destination-categories', [DestinationCategoryController::class, 'index']);

Route::apiResource('tour-destinations', TourDestinationController::class);

Route::get('/reviews/tour/{tourId}', [ReviewController::class, 'getByTour']);

Route::apiResource('tour-schedules', TourScheduleController::class);

Route::get('/hotels', [HotelController::class, 'index']);
Route::get('/bus-routes', [BusRouteController::class, 'index']);
Route::get('/motorbikes', [MotorbikeController::class, 'index']);
Route::get('/guides', [GuideController::class, 'index']);

// VNPay callback (không cần auth vì VNPay gọi trực tiếp)
Route::get('vnpay/return', [BookingController::class, 'vnpayReturn']);



// ================= AUTHENTICATED USER ROUTES =================
Route::middleware('auth:sanctum')->group(function () {
    // Authenticated info
    Route::get('/me', fn(Request $request) => response()->json(['user' => $request->user()]));
    Route::post('/logout', [AuthController::class, 'logout']);

    // Profile
    Route::get('/profile', [UserController::class, 'profile']);
    Route::put('/profile/update', [UserController::class, 'updateProfile']);

    // Bookings
    Route::apiResource('bookings', BookingController::class)->only(['index', 'store', 'show']);
    

    // Favorites
    Route::prefix('favorites')->group(function () {
        Route::get('/my-favorites', [FavoriteController::class, 'myFavorites']);
        Route::post('/', [FavoriteController::class, 'store']);
        Route::delete('/{id}', [FavoriteController::class, 'destroy']);
    });

    // Reviews
    Route::prefix('reviews')->group(function () {
        Route::post('/', [ReviewController::class, 'store']);
        Route::put('/{id}', [ReviewController::class, 'update']);
    });

});

// ================= ADMIN ROUTES =================
Route::middleware('auth:sanctum')->group(function () {
    // Users
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/user', [UserController::class, 'store']);
    Route::get('/user/{id}', [UserController::class, 'show']);
    Route::put('/user/{id}', [UserController::class, 'update']);
    Route::delete('/user/{id}', [UserController::class, 'destroy']);
    Route::put('/user/{id}/soft-delete', [UserController::class, 'softDelete']);
    Route::get('/users/trashed', [UserController::class, 'trashed']);
    Route::put('/users/{id}/restore', [UserController::class, 'restore']);

    // Tours
    Route::apiResource('tours', TourController::class)->only(['store', 'update', 'destroy']);

    // Tour Categories
    Route::apiResource('tour-categories', TourCategoryController::class)->only(['store', 'update', 'destroy']);
    Route::post('/tour-categories/{id}/soft-delete', [TourCategoryController::class, 'softDelete']);

    // Albums
    Route::apiResource('albums', AlbumController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::get('/albums/trashed', [AlbumController::class, 'trashed']);
    Route::post('/albums/{id}/soft-delete', [AlbumController::class, 'softDelete']);

    // Album Images
    Route::prefix('albums/{albumId}/images')->group(function () {
        Route::get('/', [AlbumImageController::class, 'index']);
        Route::post('/', [AlbumImageController::class, 'store']);
        Route::get('/{imageId}', [AlbumImageController::class, 'show']);
        Route::post('/{imageId}', [AlbumImageController::class, 'update']);
        Route::post('/{imageId}/soft-delete', [AlbumImageController::class, 'softDelete']);
        Route::delete('/{imageId}', [AlbumImageController::class, 'destroy']);
        Route::get('/trashed', [AlbumImageController::class, 'trashed']);
        Route::get('/statistics', [AlbumImageController::class, 'statistics']);
    });

    // Destination Categories
    Route::apiResource('destination-categories', DestinationCategoryController::class)->only(['store', 'update', 'destroy']);
    Route::post('/destination-categories/{id}/soft-delete', [DestinationCategoryController::class, 'softDelete']);

    // Destinations
    Route::apiResource('destinations', DestinationController::class)->only(['store', 'update', 'destroy']);
    Route::post('/destinations/{id}/toggle', [DestinationController::class, 'softDelete']);
    Route::post('/destinations/{id}/highlight', [DestinationController::class, 'toggleHighlight']);
    Route::get('/destinations/trashed', [DestinationController::class, 'trashed']);
    Route::get('/destinations/statistics', [DestinationController::class, 'statistics']);

    // Bookings
    Route::apiResource('bookings', BookingController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::post('/bookings/{id}/soft-delete', [BookingController::class, 'softDelete']);
    Route::get('/bookings/trashed', [BookingController::class, 'trashed']);

    // Promotions
    Route::apiResource('promotions', PromotionController::class)->only(['store', 'update', 'destroy']);
    Route::post('/promotions/{id}/soft-delete', [PromotionController::class, 'softDelete']);
    Route::get('/promotions/trashed', [PromotionController::class, 'trashed']);
    Route::get('/promotions/statistics', [PromotionController::class, 'statistics']);

    // Site Settings
    Route::apiResource('settings', SiteSettingController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::post('/settings/{id}/soft-delete', [SiteSettingController::class, 'softDelete']);
    Route::post('/settings/bulk-update', [SiteSettingController::class, 'bulkUpdate']);
    Route::get('/settings/key/{keyName}', [SiteSettingController::class, 'getByKey']);
    Route::get('/settings/trashed', [SiteSettingController::class, 'trashed']);
    Route::get('/settings/statistics', [SiteSettingController::class, 'statistics']);

    // Reviews
    Route::prefix('reviews')->group(function () {
        Route::get('/', [ReviewController::class, 'index']);
        Route::get('/trashed', [ReviewController::class, 'trashed']);
        Route::get('/statistics', [ReviewController::class, 'statistics']);
        Route::get('/{id}', [ReviewController::class, 'show']);
        Route::post('/{id}/soft-delete', [ReviewController::class, 'softDelete']);
        Route::post('/{id}/restore', [ReviewController::class, 'restore']);
        Route::delete('/{id}', [ReviewController::class, 'destroy']);
    });

    // Favorites
    Route::prefix('favorites')->group(function () {
        Route::get('/', [FavoriteController::class, 'index']);
        Route::get('/trashed', [FavoriteController::class, 'trashed']);
        Route::get('/statistics', [FavoriteController::class, 'statistics']);
        Route::get('/{id}', [FavoriteController::class, 'show']);
        Route::put('/{id}', [FavoriteController::class, 'update']);
        Route::post('/{id}/soft-delete', [FavoriteController::class, 'softDelete']);
        Route::post('/{id}/restore', [FavoriteController::class, 'restore']);
    });

    // Guides
    Route::apiResource('guides', GuideController::class)->only(['store', 'update', 'destroy']);
    Route::post('/guides/{id}/soft-delete', [GuideController::class, 'softDelete']);
    Route::get('/guides/trashed', [GuideController::class, 'trashed']);

    // Hotels
    Route::apiResource('hotels', HotelController::class)->only(['store', 'update', 'destroy']);
    Route::post('/hotels/{id}/soft-delete', [HotelController::class, 'softDelete']);
    Route::get('/hotels/trashed', [HotelController::class, 'trashed']);

    // Bus Routes
    Route::apiResource('bus-routes', BusRouteController::class)->only(['store', 'update', 'destroy']);
    Route::post('/bus-routes/{id}/soft-delete', [BusRouteController::class, 'softDelete']);
    Route::get('/bus-routes/trashed', [BusRouteController::class, 'trashed']);

    // Motorbikes
    Route::apiResource('motorbikes', MotorbikeController::class)->only(['store', 'update', 'destroy']);
    Route::post('/motorbikes/{id}/soft-delete', [MotorbikeController::class, 'softDelete']);
    Route::get('/motorbikes/trashed', [MotorbikeController::class, 'trashed']);
});