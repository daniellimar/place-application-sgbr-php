<?php

use App\Http\Controllers\Api\PlaceController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('places/search', [PlaceController::class, 'search']);
    Route::apiResource('places', PlaceController::class);
});
