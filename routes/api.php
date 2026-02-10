<?php
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\EvenementController;
use App\Http\Controllers\Api\AnnonceController;
use App\Http\Controllers\Api\RapportController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\CotisationController;
use App\Http\Controllers\Api\RoleController;

Route::post('/register', [AuthController::class,'register']);
Route::post('/login', [AuthController::class,'login']);

Route::middleware('auth:sanctum')->group(function () {
    });
    Route::put('/admin/validate-user/{id}', [AdminController::class,'validateUser']);
    Route::delete('/admin/reject-user/{id}', [AdminController::class,'rejectUser']);
    Route::get('/admin/pending-users', [AdminController::class,'pendingUsers']);

    Route::post('/logout', [AuthController::class,'logout']);

    Route::apiResource('evenements', EvenementController::class);
    Route::apiResource('annonces', AnnonceController::class);
    Route::apiResource('rapports', RapportController::class);
    Route::apiResource('cotisations', CotisationController::class);
    Route::apiResource('users', UserController::class);
    Route::get('/roles', [RoleController::class,'index']);
