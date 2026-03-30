<?php

use App\Http\Controllers\Api\V1\BtcBlockController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/btc/blocks', [BtcBlockController::class, 'index']);
});
