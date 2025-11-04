## Laravel Azure‑SSO Package · Quick Guide (aktualisiert)

### 1 | Installation
```bash
# Package installieren (nutze ein Release‑Tag, falls verfügbar)
composer require broichdigital/laravel-azure-sso

# Migrationen ausführen
php artisan migrate            # im Deploy-Skript: php artisan migrate --force
```
Hinweis: Das Package bringt die passenden Abhängigkeiten mit (`laravel/socialite ^5`, `socialiteproviders/microsoft ^5.23`). Keine zusätzlichen Repositories oder Aliase nötig.

### 2 | ENV‑Werte
| Key | Pflicht | Beschreibung |
|-----|---------|--------------|
| `AZURE_AD_CLIENT_ID`     | ✅ | Application (client) ID aus Azure |
| `AZURE_AD_CLIENT_SECRET` | ✅ | Secret der App |
| `AZURE_AD_TENANT_ID`     | ✅/„common“ | Tenant‑ID (bei Multi‑Tenant oft `common`) |
| `AZURE_AD_REDIRECT_URI`  | ✅ | Callback‑URL, z. B. `https://…/sso/callback` |
| `AZURE_AD_LOGOUT_URL`    | ➖ | (Optional) Azure Logout‑Endpoint |
| `AZURE_SSO_POST_LOGIN_REDIRECT`  | ➖ | Ziel nach Login, Default `/home` |
| `AZURE_SSO_POST_LOGOUT_REDIRECT` | ➖ | Ziel nach Logout, Default `/` |
| `AZURE_SSO_ALLOWED_DOMAINS` | ➖ | Komma-getrennte Email-Domains für Multi-Tenant-Whitelist (z.B. `firma.de,partner.com`) |
| `AZURE_SSO_ALLOWED_TENANTS` | ➖ | Komma-getrennte Tenant-IDs für Multi-Tenant-Whitelist |
| `AZURE_SSO_UNAUTHORIZED_MESSAGE` | ➖ | Fehlermeldung bei nicht autorisiertem Zugriff, Default: `Ihre Organisation ist nicht autorisiert.` |

Beispiel Single‑Tenant:
```env
AZURE_AD_CLIENT_ID=00000000-1111-2222-3333-444444444444
AZURE_AD_CLIENT_SECRET=abcdefg1234567890abcdefg1234567890
AZURE_AD_TENANT_ID=55555555-6666-7777-8888-999999999999
AZURE_AD_REDIRECT_URI=https://example.com/sso/callback

# optional
AZURE_AD_LOGOUT_URL=https://login.microsoftonline.com/55555555-6666-7777-8888-999999999999/oauth2/v2.0/logout
AZURE_SSO_POST_LOGIN_REDIRECT=/home
AZURE_SSO_POST_LOGOUT_REDIRECT=/
```

Beispiel Multi‑Tenant:
```env
AZURE_AD_CLIENT_ID=11111111-2222-3333-4444-555555555555
AZURE_AD_CLIENT_SECRET=hijklmnop9876543210hijklmnop987654
AZURE_AD_TENANT_ID=common
AZURE_AD_REDIRECT_URI=https://example.com/sso/callback

# optional
AZURE_AD_LOGOUT_URL=https://login.microsoftonline.com/common/oauth2/v2.0/logout
AZURE_SSO_POST_LOGIN_REDIRECT=/dashboard
AZURE_SSO_POST_LOGOUT_REDIRECT=/

# Whitelist für Multi-Tenant (nur eine Option verwenden!)
AZURE_SSO_ALLOWED_DOMAINS=firma.de,partner.com
# ODER
# AZURE_SSO_ALLOWED_TENANTS=aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa,bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb
```

### 3 | Single‑Tenant vs. Multi‑Tenant
- **Single‑Tenant**
  - Azure App‑Typ: „Single‑tenant"
  - `AZURE_AD_TENANT_ID` ist konkrete Tenant‑ID
- **Multi‑Tenant**
  - Azure App‑Typ: „Any organizational directory"
  - `AZURE_AD_TENANT_ID` kann `common` sein
  - Optional mehrere Profile in `config/azure-sso.php` → `tenants` (für spätere Erweiterung)
  - **Whitelist-Funktionalität**: Für Multi‑Tenant können Sie den Zugriff mit `AZURE_SSO_ALLOWED_DOMAINS` (Email-Domains) oder `AZURE_SSO_ALLOWED_TENANTS` (Tenant-IDs) einschränken. **Wichtig:** Nur eine der beiden Optionen darf gesetzt sein.

Beispiel‑Config für mehrere Tenants:
```php
'tenants' => [
    'broich' => [
        'client_id'     => env('AZURE_BROICH_CLIENT_ID'),
        'client_secret' => env('AZURE_BROICH_CLIENT_SECRET'),
        'redirect'      => env('AZURE_BROICH_REDIRECT_URI'),
        'tenant_id'     => env('AZURE_BROICH_TENANT_ID'),
    ],
    // weitere Tenants …
],
```

### 4 | Routen
| Route | Methode | Zweck |
|-------|---------|-------|
| `/sso/login`    | GET | Redirect zu Azure |
| `/sso/callback` | GET/POST | Callback, Benutzer anlegen & einloggen |
| `/sso/logout`   | POST | Laravel‑Logout + optional Azure‑Logout |

Diese Routen werden automatisch geladen und laufen im `web`‑Middleware‑Stack plus `azure.tenant`.

### 5 | DB‑Schema
Die Package‑Migration erweitert `users` u. a. um:
- **azure_id**: Azure Object‑ID (unique)
- **avatar**: Profilbild‑URL (optional)
- **access_token**: OAuth‑Token (optional)
- **password**: wird auf nullable gestellt (SSO‑User ohne Passwort)

Migrationen werden automatisch geladen; nur publishen, wenn du sie anpassen willst.

### 6 | Konfiguration „publishen“
Zwingend nur die Config:
```bash
php artisan vendor:publish --provider="Broichdigital\AzureSso\Providers\AzureSsoServiceProvider" --tag=config
```
Danach Caches leeren:
```bash
php artisan config:clear
php artisan cache:clear
```

Optional (nur bei Anpassungen):
```bash
php artisan vendor:publish --provider="Broichdigital\AzureSso\Providers\AzureSsoServiceProvider" --tag=migrations
php artisan migrate
```

### 7 | Deploy‑Tipps
```bash
php artisan migrate --force
php artisan config:clear
php artisan cache:clear
# optional:
php artisan route:clear
php artisan view:clear
```

### 8 | Azure/Entra‑ID Checkliste
- App‑Registrierung anlegen (Single‑ oder Multi‑Tenant)
- Redirect‑URI exakt auf `…/sso/callback` setzen (wie in `AZURE_AD_REDIRECT_URI`)
- Client‑Secret erstellen und sicher hinterlegen
- Mindestens Graph‑Permission „User.Read“ (für reines SSO ausreichend)

Kurzfazit:
- Installation wie oben; keine dev‑Aliases/Repos nötig.
- ENV‑Werte aus Azure setzen, Config publishen, Caches leeren.
- `/sso/login` aufrufen → Weiterleitung zu Microsoft Login.


