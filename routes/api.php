<?php

use App\Http\Controllers\Api\StoreConfigController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:api')->group(function () {
    Route::get('/store/support-contacts', [StoreConfigController::class, 'supportContacts'])
        ->name('api.store.support-contacts');
});
