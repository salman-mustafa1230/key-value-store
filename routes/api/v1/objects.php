<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\GetObjectController;
use App\Http\Controllers\Api\V1\ListRecordsController;
use App\Http\Controllers\Api\V1\StoreObjectController;
use Illuminate\Support\Facades\Route;

Route::post('/object', StoreObjectController::class);
Route::get('/object/get_all_records', ListRecordsController::class);
Route::get('/object/{key}', GetObjectController::class)->where('key', '[A-Za-z0-9][A-Za-z0-9_-]{0,63}');
