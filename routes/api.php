<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\UserMiddleware;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
Route::post('/refresh', [AuthController::class, 'refresh']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {

    // Logout
    Route::post('/logout', [AuthController::class, 'logout']);


    // Profile management
    Route::prefix('profile')->group(function () {
        Route::get('/', [UserController::class, 'getProfile']);
        Route::put('/', [UserController::class, 'updateProfile']);
        Route::post('change-password', [UserController::class, 'changePassword']);
    });

Route::middleware([UserMiddleware::class])->group(function () {
    
  Route::get('users', [UserController::class, 'getUsers']);
        Route::post('add-users', [UserController::class, 'addUser']);
        Route::put('edit-users/{id}', [UserController::class, 'editUser']);
        Route::delete('delete-users/{id}', [UserController::class, 'removeUser']);
    });
   
   
});
