<?php

use Illuminate\Support\Facades\Route;
use Broichdigital\AzureSso\Controllers\AzureSsoController;

Route::middleware('azure.tenant')->group(function () {
    Route::get('sso/login',    [AzureSsoController::class, 'redirectToProvider'])
         ->name('azure-sso.login');

    // ← hier anpassen:
    Route::match(['get', 'post'], 'sso/callback', [AzureSsoController::class, 'handleProviderCallback'])
         ->name('azure-sso.callback');

    Route::post('sso/logout',  [AzureSsoController::class, 'logout'])
         ->name('azure-sso.logout');
});
