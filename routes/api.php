<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\JobPostController;
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
    });

    Route::post('job-posts', [JobPostController::class, 'store']);
    Route::put('job-posts/{jobPost}', [JobPostController::class, 'update']);
    Route::delete('job-posts/{jobPost}', [JobPostController::class, 'destroy']);

    Route::post('applications', [ApplicationController::class, 'store']);
    Route::get('my-applications', [ApplicationController::class, 'myApplications']);
    Route::patch('applications/{application}/status', [ApplicationController::class, 'updateStatus']);

    Route::post('feedback', [FeedbackController::class, 'store']);
    Route::get('feedback/{userId}', [FeedbackController::class, 'indexForUser']);

    Route::get('notifications', [NotificationController::class, 'index']);
    Route::post('notifications', [NotificationController::class, 'store']);
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead']);
    
    //USER ACCOUNTS
    Route::put('/updateuser/{id}', [UserController::class, 'update']);
    Route::put('/changepassword/{id}', [UserController::class, 'changePassword']);
});

Route::post('/login', [AuthController::class, 'login']);
Route::post('register', [AuthController::class, 'register']);

Route::get('job-posts', [JobPostController::class, 'index']);
Route::get('job-posts/{jobPost}', [JobPostController::class, 'show']);

Route::get('/test', function(){
    return response()->json([
        "message" => "success"
    ], 200);
});