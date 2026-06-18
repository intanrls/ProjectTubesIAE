<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\ExpeditionController;

Route::middleware('api.key')->group(function () {
    Route::get('/v1/expeditions', [ExpeditionController::class, 'index']);
    Route::get('/v1/expeditions/{id}', [ExpeditionController::class, 'show']);
    Route::post('/v1/expeditions', [ExpeditionController::class, 'store']);
});