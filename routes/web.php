<?php

use Illuminate\Support\Facades\Route;
use broichdigital\AzureSso\Controllers\AzureSsoController;

Route::middleware('azure.tenant')->group(function () {
    Route::get('sso/login',    [AzureSsoController::class, 'redirectToProvider'])
         ->name('azure-sso.login');

    Route::get('sso/callback', [AzureSsoController::class, 'handleProviderCallback'])
         ->name('azure-sso.callback');

    Route::post('sso/logout',  [AzureSsoController::class, 'logout'])
         ->name('azure-sso.logout');
});
