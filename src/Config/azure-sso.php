<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Azure-SSO Konfiguration
    |--------------------------------------------------------------------------
    | Der Kunde legt in seiner .env diese ENV-Werte an.
    */
    'client_id'     => env('AZURE_AD_CLIENT_ID'),
    'client_secret' => env('AZURE_AD_CLIENT_SECRET'),
    'redirect'      => env('AZURE_AD_REDIRECT_URI'),
    'tenant_id'     => env('AZURE_AD_TENANT_ID'),

    // Ziel nach erfolgreichem Login
    'post_login_redirect'  => env('AZURE_SSO_POST_LOGIN_REDIRECT', '/home'),

    // Azure Single Logout URL (optional)
    'logout_url'           => env('AZURE_AD_LOGOUT_URL'),

    // Ziel nach lokalem Logout
    'post_logout_redirect' => env('AZURE_SSO_POST_LOGOUT_REDIRECT', '/'),

    'authority' => env('AZURE_AD_AUTHORITY', env('AZURE_AD_TENANT_ID')),
    
     // Für spätere Multi-Tenant-Support:
    'tenants' => [
        // 'default' => [ ... ],
        // 'partnerA' => [
        //     'client_id'     => env('AZURE_AD_PA_CLIENT_ID'),
        //     'client_secret' => env('AZURE_AD_PA_CLIENT_SECRET'),
        //     'redirect'      => env('AZURE_AD_PA_REDIRECT_URI'),
        //     'tenant_id'     => env('AZURE_AD_PA_TENANT_ID'),
        // ],
    ],
];
