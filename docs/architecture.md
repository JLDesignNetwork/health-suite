# Architecture

## Stack

| Layer | Choice |
|---|---|
| Runtime | NativePHP (Electron + bundled PHP 8.4) |
| Framework | Laravel 13 |
| Language | PHP 8.5 (development), PHP 8.4 (bundled binary) |
| UI | Livewire 4 + Volt (class-based components), Tailwind v4 |
| Database | SQLite (per-household, stored in OS app-data directory) |
| Charts | Chart.js 4 |
| Tests | Pest 4 |
| AI | Anthropic Claude / Google Gemini / OpenAI-compatible (configurable) |

## Multi-tenancy & Privacy

`App\Models\Concerns\BelongsToAuthUser` is a single-source trait used by `Profile`, `HealthRecord`, `Meal`, and all PHR models (`PersonalInfo`, `Allergy`, `Condition`, `Surgery`, `FamilyHistory`, `Screening`, `Medication`, `LifestyleProfile`). It:
- Registers `App\Models\Scopes\OwnedByAuthUser` as a global Eloquent scope (no-op when unauthenticated, so artisan/tinker/factories work normally).
- Auto-fills `user_id = auth()->id()` on `creating` (when not explicitly provided — passing an explicit `user_id` bypasses the hook, enabling household meal creation for other members).
- Declares the `user()` BelongsTo relation.

Policy classes (`ProfilePolicy`, `HealthRecordPolicy`, `MealPolicy`) provide an additional defence-in-depth layer, each gating `view`, `update`, and `delete` to `$user->id === $model->user_id`.

## Household Mode

A global `Setting` model (`app/Models/Setting.php`) stores app-level key/value pairs in SQLite, independent of any user. The single `auth_mode` setting (`'login'` | `'household'`) controls:
- Where unauthenticated users are redirected (`redirectGuestsTo` in `bootstrap/app.php`)
- Whether the `/household` picker is accessible
- The logout redirect target
- Household-specific UI elements (meal member picker, "Add New Member" button, register back-link)

The household picker runs as a guest route, so the `OwnedByAuthUser` scope is inactive — all profiles are visible without scope interference.

## Onboarding

`App\Http\Middleware\EnsureProfileComplete` redirects authenticated users without a `Profile` to `/onboarding` before they can reach any other route. The 5-step Volt wizard (biometrics, starting measurements, physiological norms, goals, review) saves the profile and redirects to `/dashboard` (or `/household` in household mode) on confirmation.

## HealthService (Core Calculations)

`App\Services\HealthService` is a `final` stateless class, container-resolvable, implementing every derived metric:

| Method | Formula |
|---|---|
| `bmi` | `weight / (height_m)²` |
| `bodyFatPercent` | US Navy metric formula, branched by `Gender` enum; throws `ValueError` if hip is missing for `Gender::Female` |
| `pulseDeviation` | signed `((current - baseline) / baseline) * 100` |
| `bloodPressureVariance` | per-reading signed %; returns `BloodPressureVariance` DTO with threshold flags at ±15% (`BP_THRESHOLD` const) |
| `weightProgress` | `current - baseline` |

## SQLite Date Quirk

Eloquent's `date` cast stores values as `Y-m-d H:i:s` in SQLite. All date-column queries use `whereDate()` / `orWhereDate()` rather than bare string `whereIn` — the latter silently returns zero rows when the stored value has a time suffix.

## NativePHP Runtime

Electron starts the app, extracts the bundled PHP binary, runs `php artisan optimize` and `php artisan migrate --force` (via `shouldOptimize()` / `shouldMigrateDatabase()`), then starts a PHP built-in server on a random port (`8100–9000`). Once the server prints "Development Server started" to stderr, Electron POSTs to `/_native/api/booted`, which calls `NativeAppServiceProvider::boot()`. That method calls `Window::open()`, which sends a message back to Electron to create the `BrowserWindow` and load the app URL.

All paths (`storage_path()`, `database_path()`) are remapped by NativePHP's `NativeServiceProvider::rewriteDatabase()` to the OS app-data directory at runtime (`~/Library/Application Support/<NATIVEPHP_APP_ID slug>/` on macOS).

> **Note:** `NATIVEPHP_APP_ID` (currently `com.ihealth.app` in `.env`, not tracked in git) still reflects the pre-rebrand identity. Changing it moves where the app looks for its SQLite database on disk. See `PROJ-TODO-03` in `.dev/2606/backlog.json` — this requires an explicit data-migration step before it can be changed safely, and has been deliberately left alone in the Health Suite rebrand pass.

## PHP Binary Compatibility Workarounds

`nativephp/php-bin` ships PHP 8.3 and 8.4 binaries only (PHP 8.5 is pre-release). Two workarounds are in place to develop on PHP 8.5 while bundling 8.4:
- `vendor/nativephp/php-bin/bin/mac/{arm64,x64}/php-8.5.zip` are symlinks to `php-8.4.zip`.
- `"platform-check": false` in `composer.json` suppresses Composer's PHP version guard at bundle runtime.

Both are safe to remove once `nativephp/php-bin` ships an official 8.5 binary.

---

## Implementation History (Phased Build Log)

Migrated from the legacy `CHECKLIST.md` implementation tracker (all phases complete as of `v1.6.1`).

- **Phase 0 — Housekeeping & Toolchain:** PHP 8.5 language level, `composer.json` bump, `CHANGELOG.md` init (Keep a Changelog), Livewire+Volt UI decision (overriding the original Filament mention), manual auth scaffolding (no email verification/password reset — offline-first), NativePHP as the v1.0 distribution target.
- **Phase 1 — Database Schema:** `Gender`/`MealType` enums, `profiles`/`health_records`/`meals` migrations with baseline and goal columns.
- **Phase 2 — Models & Scoping:** `OwnedByAuthUser` global scope + `BelongsToAuthUser` trait, `Profile`/`HealthRecord`/`Meal` models, factories, tinker-verified per-user isolation.
- **Phase 3 — Onboarding:** `EnsureProfileComplete` middleware, 5-step Volt wizard with per-step validation.
- **Phase 4 — HealthService:** all derived-metric formulas, 16 unit tests / 28 assertions.
- **Phase 5–7 — Daily Entry, Dashboard, History Table:** entry forms, goal rings, trend charts (Chart.js), paginated grouped history table.
- **Phase 8 — Privacy & Access Control:** Policy classes for defence-in-depth beyond the global scope.
- **Phase 9 — Tests:** onboarding, multi-tenancy isolation, HealthService, history table — all green.
- **Phase 10 — Polish & Launch (Web Build):** Pint pass, demo seeder, README rewrite, `v0.1.0` tagged as web-build complete.
- **Phase 11 — Native Packaging (NativePHP):** `nativephp/desktop:dev-main` install, `native:install`, window config, icon assets, `native:build mac` producing arm64/x64 DMG+ZIP, PHP 8.4 binary compatibility patches, smoke test, `v1.0.0` tagged. macOS code signing/notarisation deferred (`PROJ-TODO-02`) — requires an Apple Developer account.

### Perpetual NativePHP Discipline Guardrails

These apply to every change in this codebase, not just a single phase:
- Never hardcode filesystem paths — always use `storage_path()`, `database_path()`, `config_path()`, `resource_path()`, `public_path()`.
- No external API calls, no queue/Redis dependency, no SMTP dependency in the runtime path.
- `MustVerifyEmail` stays disabled on the `User` model.
- Password-reset routes/views stay stubbed out (offline = no email to send).
- All assets are pre-built via `pnpm run build` — never rely on the Vite dev server at native runtime.
