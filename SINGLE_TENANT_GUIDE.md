## Single‑Tenant je Organisation – Kurz‑Anleitung

Dieser Ansatz empfiehlt pro Organisation (Mandant) eine eigene Azure‑App‑Registrierung. In der Laravel‑App werden die Profile in `config/azure-sso.php` gepflegt und zur Laufzeit per `?tenant=` ausgewählt.

### 1) In Azure für jede Organisation eine App anlegen
1. Entra ID → App‑Registrierungen → Neue Registrierung
2. App‑Typ: „Single‑tenant“
3. Redirect‑URI: Typ „Web“, URL `https://DEINE-DOMAIN/sso/callback`
4. Geheimen Clientschlüssel erstellen und sicher notieren

Notiere je Organisation: `CLIENT_ID`, `CLIENT_SECRET`, `TENANT_ID` (Verzeichnis‑ID), `REDIRECT_URI`.

### 2) ENV‑Variablen je Organisation setzen
Beispiel: Organisation A und B
```env
# ORG A
AZURE_ORGA_CLIENT_ID=...
AZURE_ORGA_CLIENT_SECRET=...
AZURE_ORGA_TENANT_ID=aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa
AZURE_ORGA_REDIRECT_URI=https://deine-domain/sso/callback

# ORG B
AZURE_ORGB_CLIENT_ID=...
AZURE_ORGB_CLIENT_SECRET=...
AZURE_ORGB_TENANT_ID=bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb
AZURE_ORGB_REDIRECT_URI=https://deine-domain/sso/callback
```

Tipp: Die globale Default‑Konfiguration (`AZURE_AD_*`) kann auf ORG A zeigen, bleibt aber optional, wenn du ausschließlich Profile nutzt.

### 3) Profile in `config/azure-sso.php` ergänzen
```php
'tenants' => [
    'orga' => [
        'client_id'     => env('AZURE_ORGA_CLIENT_ID'),
        'client_secret' => env('AZURE_ORGA_CLIENT_SECRET'),
        'redirect'      => env('AZURE_ORGA_REDIRECT_URI'),
        'tenant_id'     => env('AZURE_ORGA_TENANT_ID'),
        'tenant'        => env('AZURE_ORGA_TENANT_ID', 'common'),
    ],
    'orgb' => [
        'client_id'     => env('AZURE_ORGB_CLIENT_ID'),
        'client_secret' => env('AZURE_ORGB_CLIENT_SECRET'),
        'redirect'      => env('AZURE_ORGB_REDIRECT_URI'),
        'tenant_id'     => env('AZURE_ORGB_TENANT_ID'),
        'tenant'        => env('AZURE_ORGB_TENANT_ID', 'common'),
    ],
],
```

Hinweis: Die Middleware `azure.tenant` wählt anhand des Query‑Parameters `tenant` das passende Profil und setzt die Socialite‑Konfiguration zur Laufzeit.

### 4) Login‑Links/Buttons pro Organisation
```text
/sso/login?tenant=orga
/sso/login?tenant=orgb
```

Optional kannst du statt eines Query‑Parameters auch Subdomains/Routes verwenden und in einer eigenen Middleware den `tenant`‑Key setzen, bevor `azure.tenant` läuft.

### 5) Caches leeren und testen
```bash
php artisan config:clear
php artisan cache:clear
```
Dann Login für jede Organisation separat testen.

### 6) Wichtige Hinweise
- Sicherheit: Nur vordefinierte Keys (`orga`, `orgb`, …) erlauben. Kein freier `tenant`‑Input.
- Redirect‑URIs: Müssen in jeder App‑Registrierung exakt hinterlegt sein.
- Policies/CA: Können je Organisation abweichen → Login pro Org testen.
- Aud/Iss: Token‑Issuer/Audience unterscheiden sich je Tenant. Der Provider nutzt automatisch `…/{tenant}/…` statt `…/common/…`.


