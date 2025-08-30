<?php

declare(strict_types=1);

use App\Http\Controllers\BinDataController;
use App\Http\Controllers\BinImportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// BIN Import routes
Route::apiResource('bin-imports', BinImportController::class);

// BIN Data routes
Route::get('bin-data', [BinDataController::class, 'index']);
Route::get('bin-data/export', [BinDataController::class, 'export']);
Route::get('bin-data/filter-options', [BinDataController::class, 'filterOptions']);
