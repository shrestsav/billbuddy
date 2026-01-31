<?php

use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\FriendController;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\SettlementController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// OPcache management - keep this for development
Route::get('clear-opcache', function () {
    if (function_exists('opcache_reset')) {
        opcache_reset();
    }
    return response()->json(['success' => true, 'version' => 'v7', 'pid' => getmypid()]);
});

// Public authentication routes
Route::prefix('auth')->group(function () {
    Route::post('register', function (\Illuminate\Http\Request $request) {
        // v7 - Debug
        return response()->json(['ver' => 'v7', 'all' => $request->all()]);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $user = \App\Models\User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => \Illuminate\Support\Facades\Hash::make($validated['password']),
            'currency_preference' => $request->currency_preference ?? 'USD',
            'timezone' => $request->timezone ?? 'UTC',
        ]);

        event(new \Illuminate\Auth\Events\Registered($user));

        $token = $user->createToken($request->device_name ?? 'api')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful',
            'user' => $user,
            'token' => $token,
        ], 201);
    });
    Route::post('login', [AuthController::class, 'login']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
    Route::get('verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])->name('verification.verify');
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('logout-all', [AuthController::class, 'logoutAll']);
        Route::get('user', [AuthController::class, 'user']);
        Route::post('resend-verification', [AuthController::class, 'resendVerification']);
    });

    // User
    Route::prefix('users')->group(function () {
        Route::get('search', [UserController::class, 'search']);
        Route::put('profile', [UserController::class, 'updateProfile']);
        Route::put('settings', [UserController::class, 'updateSettings']);
        Route::post('avatar', [UserController::class, 'uploadAvatar']);
    });

    // Friends
    Route::prefix('friends')->group(function () {
        Route::get('/', [FriendController::class, 'index']);
        Route::get('pending', [FriendController::class, 'pending']);
        Route::post('invite', [FriendController::class, 'invite']);
        Route::put('{id}/accept', [FriendController::class, 'accept']);
        Route::put('{id}/reject', [FriendController::class, 'reject']);
        Route::delete('{id}', [FriendController::class, 'destroy']);
    });

    // Groups
    Route::apiResource('groups', GroupController::class);
    Route::prefix('groups/{group}')->group(function () {
        Route::post('members', [GroupController::class, 'addMember']);
        Route::delete('members/{user}', [GroupController::class, 'removeMember']);
        Route::put('members/{user}/role', [GroupController::class, 'updateMemberRole']);
        Route::get('balances', [GroupController::class, 'balances']);
        Route::get('expenses', [GroupController::class, 'expenses']);
        Route::get('settlements', [GroupController::class, 'settlements']);
    });

    // Expenses
    Route::apiResource('expenses', ExpenseController::class);
    Route::post('expenses/{expense}/receipt', [ExpenseController::class, 'uploadReceipt']);

    // Settlements
    Route::apiResource('settlements', SettlementController::class)->only(['index', 'store', 'show']);
    Route::get('balances', [SettlementController::class, 'balances']);
    Route::get('balances/simplified', [SettlementController::class, 'simplifiedBalances']);

    // Categories
    Route::get('categories', [CategoryController::class, 'index']);

    // Analytics
    Route::prefix('analytics')->group(function () {
        Route::get('spending-by-category', [AnalyticsController::class, 'spendingByCategory']);
        Route::get('spending-over-time', [AnalyticsController::class, 'spendingOverTime']);
        Route::get('group-summary/{group}', [AnalyticsController::class, 'groupSummary']);
        Route::get('monthly-summary', [AnalyticsController::class, 'monthlySummary']);
    });

    // Activity
    Route::get('activity', [ActivityController::class, 'index']);
});
