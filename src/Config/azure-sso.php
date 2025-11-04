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

    // Standard-Tenant (kann 'common' sein)
    'tenant' => env('AZURE_AD_TENANT_ID', 'common'),

    // Für späteren Multi-Tenant-Support:
    'tenants' => [
        // 'default' => [
        //     'client_id'     => env('AZURE_AD_CLIENT_ID'),
        //     'client_secret' => env('AZURE_AD_CLIENT_SECRET'),
        //     'redirect'      => env('AZURE_AD_REDIRECT_URI'),
        //     'tenant_id'     => env('AZURE_AD_TENANT_ID'),
        // ],
        // 'partnerA' => [
        //     'client_id'     => env('AZURE_AD_PA_CLIENT_ID'),
        //     'client_secret' => env('AZURE_AD_PA_CLIENT_SECRET'),
        //     'redirect'      => env('AZURE_AD_PA_REDIRECT_URI'),
        //     'tenant_id'     => env('AZURE_AD_PA_TENANT_ID'),
        // ],
    ],

    // Eloquent User Model:
    'user_model' => env('AZURE_SSO_USER_MODEL', 'App\\Models\\User'),

    // Whitelist für Multi-Tenant (optional - leer = alle erlaubt)
    // WICHTIG: Nur eine der beiden Optionen darf gesetzt sein (XOR)
    // Email-Domains: Komma-getrennt (z.B. "firma.de,partner.com")
    'allowed_domains' => array_filter(
        array_map('trim', explode(',', env('AZURE_SSO_ALLOWED_DOMAINS', '')))
    ),

    // Tenant-IDs: Komma-getrennt (z.B. "aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa,bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb")
    'allowed_tenants' => array_filter(
        array_map('trim', explode(',', env('AZURE_SSO_ALLOWED_TENANTS', '')))
    ),

    // Fehlermeldung bei nicht autorisiertem Zugriff
    'unauthorized_message' => env('AZURE_SSO_UNAUTHORIZED_MESSAGE', 'Ihre Organisation ist nicht autorisiert.'),
];
