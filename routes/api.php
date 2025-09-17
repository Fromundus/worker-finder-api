<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\JobPostController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'active'])->group(function(){
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    
    Route::middleware('admin')->group(function(){

        Route::prefix('/users')->group(function(){
            Route::get('/', [UserController::class, 'index']);
            Route::post('/', [UserController::class, 'store']);
            Route::get('/{id}', [UserController::class, 'show']);
            Route::put('/{id}', [UserController::class, 'update']);
            Route::delete('/', [UserController::class, 'delete']);
        });

        Route::put('/update-status', [UserController::class, 'updateStatus']);

    });

    Route::get('/dashboard/worker', [DashboardController::class, 'workerOverview']);
    Route::get('/dashboard/employer', [DashboardController::class, 'employerOverview']);
    Route::get('/dashboard/admin', [DashboardController::class, 'adminOverview']);

    Route::get('/profile', [UserController::class, 'show']);
    Route::get('/user-profile/{id}', [UserController::class, 'showUserProfile']);
    Route::put('/profile', [UserController::class, 'update']);
    
    Route::post('job-posts', [JobPostController::class, 'store']);
    Route::put('job-posts/{jobPost}', [JobPostController::class, 'update']);
    Route::put('job-posts/{id}/status', [JobPostController::class, 'updateStatus']);
    Route::delete('job-posts/{jobPost}', [JobPostController::class, 'destroy']);

    Route::post('applications', [ApplicationController::class, 'store']);
    Route::get('my-applications', [ApplicationController::class, 'myApplications']);
    Route::put('applications/{id}/status', [ApplicationController::class, 'updateStatus']);
    Route::get('applications-employer', [ApplicationController::class, 'employerApplications']);

    Route::post('feedback', [FeedbackController::class, 'store']);
    Route::get('feedback/{userId}', [FeedbackController::class, 'indexForUser']);
    Route::get('feedbacks', [FeedbackController::class, 'index']);

    Route::post('/feedbacks/{id}', [FeedbackController::class, 'store']);
    Route::post('/system/feedbacks', [FeedbackController::class, 'storeSystem']);
    Route::post('/feedbacks/bookings/{id}', [FeedbackController::class, 'storeBooking']);

    Route::get('notifications', [NotificationController::class, 'index']);
    Route::get('notifications-count', [NotificationController::class, 'count']);
    Route::post('notifications', [NotificationController::class, 'store']);
    // Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead']);
    Route::post('notifications/{id}', [NotificationController::class, 'markAsRead']);
    
    Route::get('job-posts', [JobPostController::class, 'index']);
    Route::get('job-posts-employer', [JobPostController::class, 'indexEmployer']);
    Route::get('job-posts/{jobPost}', [JobPostController::class, 'show']);

    //MAP
    Route::get('/map/jobs', [LocationController::class, 'jobsNearby']);
    Route::get('/map/workers', [LocationController::class, 'workersNearby']);

    //BOOKING
    Route::get('/my-bookings', [BookingController::class, 'index']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::put('/bookings/{id}', [BookingController::class, 'updateStatus']);

    // Conversations
    Route::get('/conversations', [ConversationController::class, 'index']);
    Route::post('/conversations/start', [ConversationController::class, 'start']);
    Route::get('/conversations/{conversation}', [ConversationController::class, 'show']);

    // Messages
    Route::get('/conversations/{conversation}/messages', [MessageController::class, 'index']);
    Route::post('/conversations/{conversation}/messages', [MessageController::class, 'store']);

    //USER ACCOUNTS
    Route::put('/updateuser/{id}', [UserController::class, 'update']);
    Route::put('/changepassword/{id}', [UserController::class, 'changePassword']);
});

Route::post('/login', [AuthController::class, 'login']);
Route::post('register', [AuthController::class, 'register']);

Route::get('job-posts-public', [JobPostController::class, 'indexPublic']);


Route::get('/test', function(){
    return response()->json([
        "message" => "success"
    ], 200);
});