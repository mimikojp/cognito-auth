<?php

use Adcate\CognitoAuth\Http\Controllers\AuthenticateController;
use Adcate\CognitoAuth\Http\Middleware\RenewToken;
use Illuminate\Support\Facades\Route;

if ($routeConfig = config('cognito.route')) {
    Route::group([
        'prefix' => $routeConfig['prefix'] ?? 'auth',
    ], function () {
        Route::get('signIn', AuthenticateController::class . '@signIn');
        Route::get('callback', AuthenticateController::class . '@callback');
        Route::get('renew', AuthenticateController::class . '@renew')->middleware([RenewToken::class]);
        Route::get('signOut', AuthenticateController::class . '@signOut');
    });
}
