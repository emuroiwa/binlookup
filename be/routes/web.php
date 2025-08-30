<?php

use App\Http\Controllers\BinDataController;
use App\Http\Controllers\BinImportController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Redirect root to bin-imports
Route::get('/', function () {
    return redirect()->route('bin-imports.index');
});

// BIN Import routes (Inertia.js frontend)
Route::get('/bin-imports', [BinImportController::class, 'index'])->name('bin-imports.index');
Route::post('/bin-imports', [BinImportController::class, 'store'])->name('bin-imports.store');
Route::get('/bin-imports/{binImport}', [BinImportController::class, 'show'])->name('bin-imports.show');
Route::delete('/bin-imports/{binImport}', [BinImportController::class, 'destroy'])->name('bin-imports.destroy');

// Health check endpoint
Route::get('/health', function () {
    return response()->json(['status' => 'ok'], 200);
});

// BIN Data routes (Inertia.js frontend)
Route::get('/bin-data', [BinDataController::class, 'index'])->name('bin-data.index');
Route::get('/bin-data/export', [BinDataController::class, 'export'])->name('bin-data.export');
Route::get('/bin-data/filter-options', [BinDataController::class, 'filterOptions'])->name('bin-data.filter-options');
