<?php

use App\Http\Controllers\AgentController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/token', function (Request $request) {
    $user = User::where('email', $request->email)->firstOrFail();
    $token = $user->createToken('api')->plainTextToken;

    return response()->json(['token' => $token]);
});

Route::prefix('agent')->group(function () {
    Route::post('/prompt', [AgentController::class, 'prompt']);
    Route::post('/tools', [AgentController::class, 'tools']);
    Route::get('/stream', [AgentController::class, 'stream']);
    Route::post('/queue', [AgentController::class, 'queue']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/conversation', [AgentController::class, 'conversation']);
        Route::post('/structured', [AgentController::class, 'structured']);
    });
});
