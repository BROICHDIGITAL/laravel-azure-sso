<?php

use Illuminate\Support\Facades\Route;
use Broichdigital\AzureSso\Controllers\AzureSsoController;

// WICHTIG: 'web' rein, damit Sessions/Cookies funktionieren
Route::middleware(['web', 'azure.tenant'])->group(function () {
    Route::get('sso/login', [AzureSsoController::class, 'redirectToProvider'])
        ->name('azure-sso.login');

    // GET reicht für 'response_mode=query' bzw. 'form_post' NICHT.
    // Wenn du in Azure 'response_mode=form_post' nutzt, lass POST drin.
    Route::match(['get','post'], 'sso/callback', [AzureSsoController::class, 'handleProviderCallback'])
        ->name('azure-sso.callback');

    // Logout nur für eingeloggte User
    Route::post('sso/logout', [AzureSsoController::class, 'logout'])
        ->middleware('auth')
        ->name('azure-sso.logout');
});
