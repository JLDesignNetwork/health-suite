# Usage

## Installation (Packaged Desktop App)

1. Download the `Health-Suite-<version>-arm64.dmg` (Apple Silicon) or `Health-Suite-<version>-x64.dmg` (Intel) build.
2. Open the DMG, drag **Health Suite** to **Applications**.
3. **First launch:** right-click the app → **Open** to bypass macOS Gatekeeper (required for ad-hoc-signed apps without Apple notarisation — see `PROJ-TODO-02`). Subsequent launches work normally.

All data is stored locally in the OS app-data directory (see [`architecture.md`](architecture.md#nativephp-runtime)). Uninstall by deleting the app and, optionally, that directory.

## Local Development Setup

**Requirements:** PHP 8.5, Composer, Node.js, pnpm, [Laravel Herd](https://herd.laravel.com) (or any local server pointing to `public/`).

```bash
git clone <repo> health-suite && cd health-suite

composer install
pnpm install

cp .env.example .env
php artisan key:generate
php artisan migrate

# Optional: seed a demo account
php artisan db:seed

pnpm run build
```

Browse to the local Herd domain and register. The onboarding wizard runs automatically on first login.

### Running Tests

```bash
composer test
# or
./vendor/bin/pest
```

### Formatting

```bash
./vendor/bin/pint
```

## Building the Native App

```bash
php artisan native:build mac
```

Output lands in `nativephp/electron/dist/`. Builds both arm64 (Apple Silicon) and x64 (Intel) DMGs and ZIPs.

> **PHP binary note:** see [`architecture.md`](architecture.md#php-binary-compatibility-workarounds) for the PHP 8.5 development / 8.4 bundled-binary workaround.

### Running Unsigned Builds (Development)

When building without official Apple code signing, macOS Gatekeeper prevents the application from running. To bypass this for development after installing the `.app` bundle:

```bash
xattr -rd com.apple.quarantine /Applications/Health\ Suite.app
codesign --force --deep --sign - /Applications/Health\ Suite.app
```

## Demo Account

After seeding (`php artisan db:seed`), a demo account is created with a full male profile, 30 days of health records (gradual weight-loss trend), and 3 meals per day, so the dashboard charts and history table render immediately. See `database/seeders/DatabaseSeeder.php` for the exact credentials.
