<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Vestra\Sep\Http\Controllers\SepCallbackController;

Route::match(['POST', 'GET'], config('sep.callback_route', 'payment/sep/callback'), SepCallbackController::class)
    ->middleware(config('sep.middleware', ['web']))
    ->name('sep.callback');
