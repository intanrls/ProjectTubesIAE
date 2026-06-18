<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\InventoryController;
use App\Http\Controllers\SsoController;
use App\Http\Controllers\MessageController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// 1. Public Routes
Route::post('/v1/sso/callback', [SsoController::class, 'handleCallback']);

// 2. Route untuk akses langsung (Public)
Route::post('/inventory', [InventoryController::class, 'sendAudit'])->name('inventory.audit.simple');

// 3. RUTE RABBITMQ (Public untuk keperluan testing)
Route::post('/v1/messages/publish', [MessageController::class, 'publish']);

// 4. Protected Routes (v1)
// Middleware 'auth:sanctum' dihapus sementara agar tidak redirect ke route login saat testing
Route::middleware('api.key')->prefix('v1')->group(function () {
    Route::get('/inventories', [InventoryController::class, 'index'])->name('inventories.index');
    Route::get('/inventories/{id}', [InventoryController::class, 'show'])->name('inventories.show');
    Route::post('/inventories/qc', [InventoryController::class, 'storeQC'])->name('inventories.storeQC');
    Route::post('/inventories/store', [InventoryController::class, 'store'])->name('inventories.store');
    Route::post('/inventory/audit', [InventoryController::class, 'sendAudit'])->name('inventory.audit');
});