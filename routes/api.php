<?php

use App\Http\Controllers\Api\V1\BtcBlockController;
use App\Http\Controllers\Api\V1\BtcMempoolController;
use App\Http\Controllers\Api\V1\BtcTransactionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/btc/blocks', [BtcBlockController::class, 'index']);
    Route::get('/btc/blocks/{hash}', [BtcBlockController::class, 'show']);
    Route::get('/btc/mempool/stats', [BtcMempoolController::class, 'stats']);
    Route::get('/btc/mempool/transactions', [BtcMempoolController::class, 'transactions']);
    Route::get('/btc/transactions/recent', [BtcTransactionController::class, 'recent']);
    Route::get('/btc/transactions/{txid}', [BtcTransactionController::class, 'show']);
});
