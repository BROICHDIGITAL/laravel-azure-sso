# Laravel Azure SSO Package

Ein Laravel-Package für die Integration von Azure AD (Entra ID) Single Sign-On (SSO) mit Unterstützung für Single- und Multi-Tenant-Szenarien.

## Features

- Single- und Multi-Tenant-Unterstützung
- Whitelist-Funktionalität für Multi-Tenant (Email-Domain oder Tenant-ID)
- Automatische Benutzer-Synchronisation
- Stateless OAuth-Flow
- Optional: Azure Single Logout

## Installation

```bash
composer require broichdigital/laravel-azure-sso
php artisan migrate
```

## Konfiguration

### Basis-Konfiguration

```env
AZURE_AD_CLIENT_ID=your-client-id
AZURE_AD_CLIENT_SECRET=your-client-secret
AZURE_AD_TENANT_ID=common  # oder spezifische Tenant-ID für Single-Tenant
AZURE_AD_REDIRECT_URI=https://your-domain.com/sso/callback
```

### Multi-Tenant mit Whitelist

Für Multi-Tenant-Szenarien können Sie eine Whitelist konfigurieren, um den Zugriff zu beschränken:

#### Option 1: Email-Domain-Whitelist

Nur Benutzer mit bestimmten Email-Domains dürfen sich anmelden:

```env
AZURE_AD_TENANT_ID=common
AZURE_SSO_ALLOWED_DOMAINS=firma.de,partner.com,tochter.de
```

#### Option 2: Tenant-ID-Whitelist

Nur Benutzer aus bestimmten Azure AD Tenants dürfen sich anmelden:

```env
AZURE_AD_TENANT_ID=common
AZURE_SSO_ALLOWED_TENANTS=aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa,bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb
```

**Wichtig:** Es darf nur eine der beiden Whitelist-Optionen gesetzt sein (XOR-Logik). Wenn beide gesetzt sind, wird ein Konfigurationsfehler geloggt und der Login verweigert.

#### Optionale Konfiguration

```env
# Eigene Fehlermeldung bei nicht autorisiertem Zugriff
AZURE_SSO_UNAUTHORIZED_MESSAGE=Zugriff verweigert. Bitte kontaktieren Sie Ihren Administrator.

# Redirect nach Login
AZURE_SSO_POST_LOGIN_REDIRECT=/dashboard

# Redirect nach Logout
AZURE_SSO_POST_LOGOUT_REDIRECT=/

# Azure Logout URL (optional)
AZURE_AD_LOGOUT_URL=https://login.microsoftonline.com/common/oauth2/v2.0/logout
```

## Routen

Das Package registriert automatisch folgende Routen:

- `GET /sso/login` - Startet den Azure-Login-Flow
- `GET/POST /sso/callback` - Callback-Handler für Azure
- `POST /sso/logout` - Logout (lokal + optional Azure-Logout)

## Whitelist-Funktionalität

Die Whitelist-Funktionalität wird automatisch aktiviert, wenn eine der beiden ENV-Variablen gesetzt ist:

- `AZURE_SSO_ALLOWED_DOMAINS`: Komma-getrennte Liste von Email-Domains
- `AZURE_SSO_ALLOWED_TENANTS`: Komma-getrennte Liste von Tenant-IDs

### Verhalten

- **Beide leer**: Alle Benutzer können sich anmelden (keine Filterung)
- **Nur `allowed_domains` gesetzt**: Nur Benutzer mit Email-Adressen aus den erlaubten Domains können sich anmelden
- **Nur `allowed_tenants` gesetzt**: Nur Benutzer aus den erlaubten Azure AD Tenants können sich anmelden
- **Beide gesetzt**: Konfigurationsfehler (nur eine Option erlaubt)

### Sicherheitshinweise

- Die Whitelist-Prüfung erfolgt nach erfolgreichem Azure-Login, aber vor dem lokalen Login
- Fehlgeschlagene Authentifizierungsversuche werden geloggt
- Empfohlen: Kombinieren Sie die Whitelist mit Azure Conditional Access Policies für zusätzliche Sicherheit

## Azure-Konfiguration

### Multi-Tenant App einrichten

1. In Azure Entra ID → App-Registrierungen → Ihre App
2. Unter "Authentication" → "Supported account types"
3. Wählen Sie: "Accounts in any organizational directory (Multi-tenant)" oder "Accounts in any organizational directory and personal Microsoft accounts"
4. Redirect-URI: `https://your-domain.com/sso/callback`
5. Optional: "Admin consent required" aktivieren (verhindert, dass einzelne Benutzer zustimmen können)

### Single-Tenant App

1. In Azure Entra ID → App-Registrierungen → Ihre App
2. "Supported account types": "Accounts in this organizational directory only (Single tenant)"
3. `AZURE_AD_TENANT_ID` auf Ihre spezifische Tenant-ID setzen

## Weitere Dokumentation

- [QUICK_GUIDE.md](QUICK_GUIDE.md) - Schnellstart-Anleitung
- [SINGLE_TENANT_GUIDE.md](SINGLE_TENANT_GUIDE.md) - Detaillierte Anleitung für Single-Tenant-Szenarien

