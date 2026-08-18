<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'service' => 'versioned-key-store',
        'docs' => [
            'POST /api/v1/object',
            'GET /api/v1/object/{key}',
            'GET /api/v1/object/{key}?timestamp=',
            'GET /api/v1/object/get_all_records',
            'GET /swagger',
        ],
    ]);
});

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});
