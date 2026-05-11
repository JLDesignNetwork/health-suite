# iHealth Implementation Checklist

Tracks all work required to reach a complete first release of the iHealth application as defined in [`README.md`](README.md) (V4.0 Master Specification). Update as items are completed and mirror milestone-level changes into [`CHANGELOG.md`](CHANGELOG.md).

Legend: `[ ]` pending · `[x]` done · `[~]` in progress

---

## Phase 0 — Housekeeping & Toolchain
- [x] Set PhpStorm project PHP language level to **8.5** (`.idea/php.xml`).
- [x] Bump `composer.json` PHP requirement to `^8.5`.
- [x] Initialise `CHANGELOG.md` (Keep a Changelog 1.1.0 + SemVer).
- [x] Confirm Laravel Herd (or local) is serving PHP 8.5 for this site. (`herd isolate 8.5` set; CLI invoked via `php85` until Herd default is bumped — noted below.)
- [x] **UI stack decided: Livewire (with Volt where it tightens code).** Overrides the README's "Filament" mention. Tailwind is already installed.
- [x] **Auth scaffolding decided: Strategy B — manual minimal install** (livewire/livewire + livewire/volt), with email verification and password reset deliberately omitted (offline-first; see "NativePHP discipline" below).
- [x] **Distribution target decided: NativePHP at v1.0** — single-tenant per household, no internet required.
- [x] Initialise git repository and commit baseline (commit `5664883`).
- [x] Install Livewire + Volt and scaffold minimal auth (login, register, logout, dashboard placeholder) with `guest` and `app` layouts.
- [x] Publish translation files (`php artisan lang:publish`) so `auth.failed` / `auth.throttle` resolve to friendly English.

### Known toolchain quirk
- Herd's global CLI symlink (`php`) still points to `php84`. We invoke composer/artisan via `/Users/.../Herd/bin/php85` for now. Long-term fix: bump Herd's global default to 8.5 *or* add a shell alias / `direnv` hook in this directory. Not blocking.

### NativePHP discipline (applies to every phase from now on)
- [ ] Never hardcode filesystem paths. Always use `storage_path()`, `database_path()`, `config_path()`, `resource_path()`, `public_path()`.
- [ ] No external API calls, no queue/Redis dependencies, no SMTP dependency in the runtime path.
- [ ] Disable `MustVerifyEmail` on the `User` model.
- [ ] Stub out / remove password-reset routes and views from the starter kit (offline = no email to send).
- [ ] Keep all assets pre-built via `npm run build` — no reliance on the Vite dev server at runtime.

## Phase 1 — Database Schema
- [x] PHP enums: `App\Enums\Gender` (male/female) and `App\Enums\MealType` (breakfast/lunch/dinner/snack), each with a `label()` helper.
- [x] Migration: `profiles` table
    - `user_id` (FK, unique, cascade onDelete), `gender` (enum), `dob` (date), `height_cm` (decimal 5,2).
    - Baselines: `baseline_weight` (6,2), `baseline_neck` (5,2), `baseline_waist` (5,2), `baseline_hip` (5,2, nullable), `baseline_pulse`, `baseline_systolic`, `baseline_diastolic` (all unsigned smallint).
    - Goals: `target_weight` (6,2), `daily_calorie_goal` (uint), `daily_water_goal` (4,2), `weekly_exercise_goal` (usmallint) — all nullable.
    - `timestamps`.
- [x] Migration: `health_records` table
    - `user_id` (FK, cascade onDelete), `date` (date, indexed via composite).
    - Body: `weight` (6,2), `neck` / `waist` / `hip` (5,2) — all nullable.
    - Vitals: `systolic`, `diastolic`, `pulse` (unsigned smallint, nullable).
    - Activity: `water_intake_l` (4,2), `exercise_minutes` (usmallint) — nullable.
    - `timestamps`. Composite index on (`user_id`, `date`).
- [x] Migration: `meals` table
    - `user_id` (FK, cascade onDelete), `date` (date), `meal_type` (enum).
    - `description` (string), `calories` (unsigned int).
    - `timestamps`. Composite index on (`user_id`, `date`).
- [x] Ran `php artisan migrate` and verified schema in SQLite (via Boost `database-schema`).

## Phase 2 — Models & Scoping
- [ ] `App\Models\Profile` with `$fillable`, casts (enum, date, decimal), `belongsTo(User::class)`.
- [ ] `App\Models\HealthRecord` with casts (`date` → date) and `belongsTo(User::class)`.
- [ ] `App\Models\Meal` with enum cast for `meal_type` and `belongsTo(User::class)`.
- [ ] `App\Models\User` — add `hasOne(Profile::class)`, `hasMany(HealthRecord::class)`, `hasMany(Meal::class)`.
- [ ] **Global Scope** `App\Models\Scopes\OwnedByAuthUser` applied to `Profile`, `HealthRecord`, `Meal` — filters by `auth()->id()` automatically.
- [ ] Booted `creating` hook on each scoped model to auto-fill `user_id = auth()->id()`.
- [ ] Factories + seeders (for tests only; not for production data).

## Phase 3 — Onboarding (Mandatory Baseline)
- [ ] Middleware `App\Http\Middleware\EnsureProfileComplete` — redirects to onboarding wizard if `auth()->user()->profile` is missing.
- [ ] Register middleware in `bootstrap/app.php` under the `web` group, after `auth`.
- [ ] Multi-step onboarding wizard:
    - Step 1: Biometrics (gender, DOB, height).
    - Step 2: Starting Measurements (weight, neck, waist, hip).
    - Step 3: Physiological Norms (resting pulse, systolic, diastolic baselines).
    - Step 4: Goals (target weight, calorie / water / exercise targets).
    - Step 5: Review & confirm.
- [ ] Form requests with strict validation (positive decimals, sane DOB range, enum constraints).

## Phase 4 — HealthService (Core Calculations)
- [ ] Class `App\Services\HealthService` — single dependency-injected service for all derived metrics.
- [ ] `bmi(float $weightKg, float $heightCm): float`.
- [ ] `bodyFatPercent(string $gender, float $waist, float $neck, ?float $hip, float $height): float` — U.S. Navy formula (male / female branches).
- [ ] `pulseDeviation(int $current, int $baseline): float` — returns signed percentage.
- [ ] `bloodPressureVariance(int $systolic, int $diastolic, array $baseline): array` — flags readings >15% from baseline.
- [ ] `weightProgress(float $current, float $baseline): float` — `current - baseline` (negative = loss).
- [ ] Unit tests with Pest covering each formula (use known reference values from the README).

## Phase 5 — Daily Entry UI
- [ ] HealthRecord create/edit form (date defaults to today; one entry per day per user — soft constraint).
- [ ] Meal entry form (multiple meals per day, grouped by `meal_type`).
- [ ] Inline edit / delete for today’s entries.

## Phase 6 — Dashboard ("Clean Look")
- [ ] Daily Goal Rings: Calories (remaining/over), Water (L), Exercise (min) — circular progress components.
- [ ] Charts (line + scatter): Weight, BFP, BMI, Pulse, BP — each rendered with a horizontal **Goal Line** (e.g. target weight).
- [ ] "Today" summary card: current weight, BFP, BP, pulse, plus deviation badges from `HealthService`.

## Phase 7 — History Table ("Clean Look" Grouping)
- [ ] Server returns a date on **every** row (no nulls — preserves chart integrity).
- [ ] Blade/Livewire view applies `class="hidden"` (visually) to the date cell when `$row->date === $previous->date`.
- [ ] Pagination + per-page selector.
- [ ] CSV export (per-user).

## Phase 8 — Privacy & Access Control
- [ ] Verify global scopes are active by writing a multi-user Pest test:
    - Create two users with profiles + records.
    - Authenticate as user A; assert `HealthRecord::all()` returns only A’s rows.
- [ ] Policy classes for `Profile`, `HealthRecord`, `Meal` (defence in depth — even if scopes are bypassed).
- [ ] Route-model binding uses scoped resolution (`->withoutGlobalScopes()` is forbidden outside admin contexts).

## Phase 9 — Tests
- [ ] Feature: onboarding wizard redirects unprofiled users.
- [ ] Feature: completed-profile user can reach dashboard.
- [ ] Feature: multi-tenancy isolation (cross-user data leakage prevented).
- [ ] Unit: every `HealthService` formula.
- [ ] Feature: history table renders correct duplicate-date hiding.
- [ ] Run `composer test` green.

## Phase 10 — Polish & Launch (Web Build)
- [ ] Run `vendor/bin/pint` and commit any style fixes.
- [ ] Seed a demo user for `php artisan tinker` walkthroughs.
- [ ] Update `README.md` with run/setup instructions for new contributors and note the Livewire-over-Filament deviation.
- [ ] Tag `v0.1.0` (web build) and move CHANGELOG `[Unreleased]` entries under that version.

## Phase 11 — Native Packaging (NativePHP)
- [ ] `composer require nativephp/electron` (or `nativephp/laravel` per current docs at install time).
- [ ] `php artisan native:install` and review generated `config/nativephp.php` + `app/Providers/NativeAppServiceProvider.php`.
- [ ] Move all SQLite + storage paths to OS user-data directories via NativePHP's path helpers.
- [ ] Configure window: size, title, icon, single-instance lock.
- [ ] Build pipeline: `php artisan native:build` for macOS (`.app` / `.dmg`), Windows (`.exe`), Linux (`.AppImage`).
- [ ] Application icon assets (1024×1024 master + downscales).
- [ ] macOS code signing + notarisation (requires Apple Developer account — defer if personal-only).
- [ ] Smoke-test all flows in the packaged binary (onboarding, daily entry, charts, multi-user switching).
- [ ] Tag `v1.0.0` once the native build is signed and verified on at least one target OS.
