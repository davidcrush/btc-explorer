<?php

use App\Http\Controllers\Api\V1\BtcBlockController;
use App\Http\Controllers\Api\V1\BtcTransactionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/btc/blocks', [BtcBlockController::class, 'index']);
    Route::get('/btc/blocks/{hash}', [BtcBlockController::class, 'show']);
    Route::get('/btc/transactions/{txid}', [BtcTransactionController::class, 'show']);
});
