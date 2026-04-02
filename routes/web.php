<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Inertia::render('Home');
});

Route::get('/blocks', function () {
    return Inertia::render('Blocks/Index');
});

Route::get('/blocks/{hash}', function (string $hash) {
    return Inertia::render('Blocks/Show', [
        'hash' => $hash,
    ]);
});

Route::get('/transactions', function () {
    return Inertia::render('Transactions/Index');
});

Route::get('/transactions/{txid}', function (string $txid) {
    return Inertia::render('Transactions/Show', [
        'txid' => $txid,
    ]);
});

Route::get('/mempool', function () {
    return Inertia::render('Mempool/Index');
});
