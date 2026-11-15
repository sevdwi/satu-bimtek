<?php

use App\Http\Controllers\Api\DusArsipApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes for SATU Mobile
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // Endpoint Lacak / Scan Dus Arsip untuk mobile
    Route::get('/dus/{kode}', [DusArsipApiController::class, 'show'])->name('api.dus.show');
});
