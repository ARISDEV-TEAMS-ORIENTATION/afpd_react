<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AnnonceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CotisationController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EvenementController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\RapportController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/me', [ProfileController::class, 'show']);
    Route::patch('/me', [ProfileController::class, 'update']);
    Route::patch('/me/password', [ProfileController::class, 'updatePassword']);
    Route::delete('/me/tokens/{tokenId}', [ProfileController::class, 'revokeToken']);
    Route::patch('/me/avatar', [ProfileController::class, 'updateAvatar']);
    Route::delete('/me/avatar', [ProfileController::class, 'deleteAvatar']);
    Route::get('/me/preferences', [ProfileController::class, 'preferences']);
    Route::patch('/me/preferences', [ProfileController::class, 'updatePreferences']);
    Route::patch('/me/privacy', [ProfileController::class, 'updatePrivacy']);
    Route::get('/me/cotisations', [CotisationController::class, 'myPayments']);

    Route::get('/dashboard/overview', [DashboardController::class, 'overview']);
    Route::get('/dashboard/cotisations/monthly', [DashboardController::class, 'cotisationsMonthly']);
    Route::get('/dashboard/evenements/upcoming', [DashboardController::class, 'upcomingEvents']);
    Route::get('/dashboard/alerts', [DashboardController::class, 'alerts']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications', [NotificationController::class, 'store'])
        ->middleware('role:Presidente,Secretaire,Tresoriere');
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markRead']);
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead']);

    Route::get('/users/{id}/cotisations', [UserController::class, 'cotisations'])
        ->middleware('role:Presidente,Secretaire,Tresoriere');
    Route::get('/users/{id}/participations', [UserController::class, 'participations'])
        ->middleware('role:Presidente,Secretaire,CommunityManager');
    Route::patch('/users/{id}/status', [UserController::class, 'updateStatus'])
        ->middleware('role:Presidente,Secretaire');
    Route::patch('/users/{id}/role', [UserController::class, 'updateRole'])
        ->middleware('role:Presidente');

    Route::get('/cotisations/summary', [CotisationController::class, 'summary'])
        ->middleware('role:Presidente,Tresoriere');
    Route::get('/cotisations/late', [CotisationController::class, 'late'])
        ->middleware('role:Presidente,Tresoriere');
    Route::post('/cotisations/{id}/receipt', [CotisationController::class, 'generateReceipt'])
        ->middleware('role:Presidente,Tresoriere');
    Route::get('/cotisations/export/csv', [CotisationController::class, 'exportCsv'])
        ->middleware('role:Presidente,Tresoriere');
    Route::get('/cotisations/export/pdf', [CotisationController::class, 'exportPdf'])
        ->middleware('role:Presidente,Tresoriere');

    Route::get('/evenements/upcoming', [EvenementController::class, 'upcoming']);
    Route::post('/evenements/{id}/inscriptions', [EvenementController::class, 'subscribe']);
    Route::delete('/evenements/{id}/inscriptions/{userId}', [EvenementController::class, 'unsubscribe']);
    Route::get('/evenements/{id}/participants', [EvenementController::class, 'participants']);
    Route::patch('/evenements/{id}/participants/{userId}/presence', [EvenementController::class, 'markPresence'])
        ->middleware('role:Presidente,CommunityManager,Responsable');
    Route::patch('/evenements/{id}/status', [EvenementController::class, 'updateStatus'])
        ->middleware('role:Presidente,CommunityManager');

    Route::post('/reports/monthly/generate', [RapportController::class, 'generateMonthly'])
        ->middleware('role:Presidente,Secretaire,Tresoriere');
    Route::get('/reports/{id}/download', [RapportController::class, 'download'])
        ->middleware('role:Presidente,Secretaire,Tresoriere');

    Route::get('/exports/users.csv', [ExportController::class, 'usersCsv'])
        ->middleware('role:Presidente,Secretaire');
    Route::get('/exports/cotisations.csv', [ExportController::class, 'cotisationsCsv'])
        ->middleware('role:Presidente,Tresoriere');
    Route::get('/exports/evenements.csv', [ExportController::class, 'evenementsCsv'])
        ->middleware('role:Presidente,CommunityManager');

    Route::prefix('admin')->middleware('role:Presidente')->group(function () {
        Route::put('/validate-user/{id}', [AdminController::class, 'validateUser']);
        Route::delete('/reject-user/{id}', [AdminController::class, 'rejectUser']);
        Route::get('/pending-users', [AdminController::class, 'pendingUsers']);

        Route::put('/validate-event/{id}', [AdminController::class, 'validateEvent']);
        Route::put('/reject-event/{id}', [AdminController::class, 'rejectEvent']);
        Route::get('/pending-events', [AdminController::class, 'pendingEvents']);
        Route::get('/evenements/pending', [EvenementController::class, 'pendingForAdmin']);
    });

    Route::get('/roles', [RoleController::class, 'index']);
    Route::post('/roles', [RoleController::class, 'store'])->middleware('role:Presidente');

    Route::apiResource('users', UserController::class)
        ->middleware('role:Presidente,Secretaire,CommunityManager,Tresoriere');

    Route::apiResource('cotisations', CotisationController::class)
        ->middleware('role:Presidente,Tresoriere');

    Route::apiResource('rapports', RapportController::class)
        ->middleware('role:Presidente,Secretaire,Tresoriere');

    Route::apiResource('evenements', EvenementController::class);
    Route::apiResource('annonces', AnnonceController::class);
});
