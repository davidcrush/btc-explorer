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
