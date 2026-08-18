# Changelog

All notable changes to **Health Suite** (formerly **iHealth**) will be documented in this file.

The format is based on [Keep a Changelog 1.1.0](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning 2.0.0](https://semver.org/spec/v2.0.0.html) for releases through `v1.6.1`.

> **Versioning transition:** starting with `2606.2.0-s`, this project adopts the JLDN Generational Versioning Schema (GVS, `[YYMM].[SUBVERSION].[REVISION]-[TAG]`) in place of SemVer. Pre-transition SemVer releases (`v0.1.0`–`v1.6.1`) remain intact below as immutable historical bookmarks and are not renumbered.

## [Unreleased]

## [2606.2.0-s] — 2026-08-18

### Changed
- Project renamed from **iHealth** to **Health Suite**; adopted the JLDN Generational Hub architecture (`.dev/`), GVS versioning, and the JLDN Gold Standard modernization baseline (Orange Team pass): in-repo `docs/` wiki, `.agents/`/`CLAUDE.md` governance, `.aiexclude`/`.aiignore` security/token boundaries, and a `.github/` CI/CD + CodeQL governance suite.
- `composer.json` / `package.json` package identity updated (`jl-design-network/health-suite`, `@jldn/health-suite`); `npx` invocations in Composer scripts replaced with `pnpm dlx` per the PNPM Exclusive policy.
- Legacy `BUGS.md`, `CHECKLIST.md`, and `IDEAS.md` retired; open items migrated to `.dev/2606/backlog.json` (`PROJ-ISSUE-01`, `PROJ-TODO-02`) and `.dev/2606/ideas.json`; historical phase log preserved in `docs/architecture.md`.

### Fixed
- `app/Models/Meal.php` — added `'calories' => 'integer'` to `casts()`. Without it SQLite returns the column as a string, causing `Collection::sum('calories')` to throw `TypeError: Unsupported operand types: int + string` and crashing the meal-log view (the production 500 error).
- `resources/views/livewire/meal-log.blade.php` — `number_format($meal->calories ?? 0)` guards against null calories on the per-row display.
- `app/Services/AiService.php` — `buildSystemPrompt()` now calls `interpretMealPattern()` to detect OMAD / 2MAD / 3MAD and IF window patterns (16:8, 18:6, 20:4, 23:1) in the dietary regimen string and injects an explicit `IMPORTANT — MEAL PATTERN` line into the system prompt. The line states the exact meal count, the IF protocol name, the per-meal calorie target (derived from the user's daily calorie goal ÷ meal count), and explicitly tells the AI that light/token meals are incorrect for this pattern. Fixes the AI generating sub-300 kcal "breakfast" recipes for a user on 2MAD (16:8).
- Pint style violations in `app/Models/Meal.php`, `app/Http/Controllers/BackupController.php`, `app/Services/AiService.php`.

### Known Issues
- `PROJ-ISSUE-01` — Kitchen → Recipes → Generate Recipe can return a 500 (`Maximum execution time of 30 seconds exceeded`) when the AI provider is slow. Tracked in `.dev/2606/backlog.json`; not yet fixed in this release.

## [1.6.1] — 2026-05-22

### Fixed
- `app/Http/Controllers/BackupController.php` — 1:1 relation imports (`Profile`, `PersonalInfo`, `LifestyleProfile`) switched from `updateOrCreate` to delete-then-`forceFill`-create (same pattern as many-relations). Fixes `UNIQUE constraint failed: personal_info.user_id` when importing a backup while a record already exists for the auth user — root cause was `user_id` not being in `$fillable`, so the `creating` hook always wrote `auth()->id()` regardless of which user was being imported.

### Added
- `resources/views/livewire/kitchen.blade.php` — **Multi-select mode**: "Select" button enters selection mode; own ingredients show checkboxes. Header gains "Select All" (toggles all visible own items; re-clicking deselects all) and a count. A persistent action toolbar shows immediately on entering select mode with "Delete", and in household mode "Share" / "Unshare" — buttons are disabled until at least one item is checked, then enable with item count. "Cancel" exits and clears selection.
- `resources/views/livewire/kitchen.blade.php` — **Inline edit form**: the ingredient edit form now renders directly below the row being edited (with a subtle indigo highlight on the row) and auto-scrolls into view via Alpine `x-init`. The add form continues to appear at the top of the section.
- `resources/views/livewire/partials/ingredient-form-fields.blade.php` — shared partial for ingredient form fields used by both the top add form and the inline edit form.

## [1.6.0] — 2026-05-21

> Automated ingredient reduction, recipe-to-meal picker, and # of people for recipe generation.

### Added
- `database/migrations/…add_quantity_on_hand_to_ingredients_table.php` — `quantity_on_hand` decimal(8,2) nullable on `ingredients`.
- `database/migrations/…create_meal_ingredients_table.php` + `app/Models/MealIngredient.php` — `meal_ingredients` pivot (meal_id, ingredient_id, amount_used decimal nullable); cascades on both FKs.
- `app/Models/Ingredient.php` — `quantity_on_hand` added to fillable; `casts()` added for decimal and boolean `shared`.
- `app/Models/Meal.php` — `mealIngredients()` HasMany relationship added.
- `resources/views/livewire/kitchen.blade.php` — **Qty on Hand**: ingredient form gains numeric `Qty on hand` field; list rows show stock badges (grey "N in stock" / amber "Low" / red "Depleted"). **# of People**: recipe generator adds a 1–12 people selector; prompt and SERVINGS format line locked to the chosen count.
- `resources/views/livewire/meal-log.blade.php` — **Automated Ingredient Reduction**: "Ingredients Used" checklist in the personal meal form; checking an item shows a qty input; on save `MealIngredient` records are created and `quantity_on_hand` decremented; on edit previous reductions are reversed then reapplied; on delete all reductions restored. **Recipe Picker**: both personal and household meal forms now open with a "From a saved recipe" dropdown; selecting a recipe auto-fills description and calories (both remain editable); household mode includes shared recipes from all members.
- `app/Services/AiService.php` — pantry entries now include `quantity_on_hand` in the system prompt (e.g. `[6 units remaining]` or `[depleted]`) so the AI factors stock levels into recipe suggestions.

## [1.5.0] — 2026-05-21

> Household Kitchen features, ingredient search/filter, calorie estimation, and shared ingredient/recipe visibility.

### Added
- `database/migrations/2026_05_21_…_add_shared_to_ingredients_table.php` — `shared` boolean column (default false) on `ingredients` table; `app/Models/Ingredient.php` fillable updated.
- `resources/views/livewire/kitchen.blade.php` — **Ingredient Search & Filter**: live text search (matches name and notes) and category dropdown filter above the ingredient list.
- `resources/views/livewire/kitchen.blade.php` — **Shared Ingredients (Household Mode)**: ingredients marked `shared = true` are visible to all household members (purple "Shared" badge, "From: {owner}" label, no Edit/Delete for other users' items). Ingredient form gains a "Share with household members" checkbox in household mode.
- `resources/views/livewire/kitchen.blade.php` — **Shared Recipes (Household Mode)**: all members' recipes visible; "by {owner}" shown for non-owned recipes; Delete hidden and server-guarded for non-owned recipes.
- `resources/views/livewire/meal-log.blade.php` — **Calorie Estimation**: calories field is now optional; a `✦ Estimate` link beside the label calls `AiService::chat()` with a targeted prompt returning only an integer. Auto-populates the calories field. Works for both personal and (manually) household meal forms. Estimation error displayed inline.

## [1.4.0] — 2026-05-21

> Quality-of-life pass: AI model picker, recipe variety engine, real-time dashboard goals, expanded ingredient categories, and full backup/restore system.

### Added
- `app/Http/Controllers/BackupController.php` + `routes/web.php` — **Backup & Restore** system. `GET /backup/export` streams a dated JSON file containing every user's full dataset (profile, personal info, lifestyle, health records, meals, allergies, conditions, surgeries, family history, screenings, medications, ingredients, recipes) plus app settings. `POST /backup/import` restores from the file: settings merged by key; users matched by email (password hash preserved); 1:1 relations updated; many-to-many relations wiped and re-imported for a clean restore. Browser confirm dialog guards the restore action.
- `resources/views/livewire/settings.blade.php` — **Backup & Restore** card added (Export button + Restore file-upload form with amber warning). **AI Model Picker** — "↻ Fetch available models" link calls `AiService::fetchAvailableModels()` which queries the provider's live models API; model text input replaced with a dropdown when models are loaded. `fetchGoogleModels()` and `fetchAnthropicModels()` added to `AiService`.
- `resources/views/livewire/kitchen.blade.php` — **Kind of Meal selector** (Any / Breakfast / Brunch / Lunch / Dinner / Snack / Dessert / Appetizer / Side Dish) on the Generate Recipe button row. **Recipe variety engine**: counts ingredient appearances across last 6 saved recipes (parentheticals stripped for accurate matching), physically removes overused ingredients from the AI's pantry context via `AiService::chatForRecipe()`, and steers the AI toward underused ingredients using a `focusClause`.
- `app/Services/AiService.php` — `chatForRecipe(User, history, excludeIngredientNames[])` builds a system prompt with overused ingredients filtered out of the pantry list. `buildSystemPrompt()` accepts an optional `$excludeIngredientNames` array. `fetchAvailableModels()`, `fetchGoogleModels()`, `fetchAnthropicModels()` for live model listing.

### Fixed
- `resources/views/livewire/dashboard.blade.php` + `meal-log.blade.php` + `daily-record.blade.php` — **Real-Time Dashboard Goals**: goal rings and today's stats now update immediately when a meal or health record is saved or deleted. Child components dispatch `dashboard-refresh`; parent listens with `#[On('dashboard-refresh')]`.
- `resources/views/livewire/kitchen.blade.php` — Recipe ingredient matching now strips parentheticals before comparing (`Chickpeas (Ceci)` → `Chickpeas`) so overuse is correctly detected.
- `resources/views/livewire/settings.blade.php` — AI provider switch now clears the fetched models list and resets the model field to the new provider's default.

### Changed
- `resources/views/livewire/kitchen.blade.php` — Ingredient categories expanded from 7 to 16: Meat, Seafood, Poultry, Dairy, Eggs, Vegetable, Fruit, Grain/Pasta, Legumes/Beans, Nuts/Seeds, Herbs/Spices, Sauces/Condiments, Oils/Fats, Frozen, Canned/Packaged, Other.

## [1.3.0] — 2026-05-19

> Kitchen module — ingredient pantry and AI-powered recipe generation. AI error handling improved. Dietary regimen confirmed as active context for all recipe generation.

### Added
- `database/migrations/…create_ingredients_table.php` + `app/Models/Ingredient.php` — `ingredients` table (user-scoped, `BelongsToAuthUser`). Fields: `name`, `quantity` (free-text amount, e.g. "6 cans (240g each)"), `category` (Protein / Dairy / Vegetable / Fruit / Grain / Spice / Other), `notes`. `unit` column exists in DB but removed from UI in favour of free-text Amount.
- `database/migrations/…create_recipes_table.php` + `app/Models/Recipe.php` — `recipes` table (user-scoped). Fields: `name`, `servings`, `estimated_calories_per_serving`, `instructions` (full AI response).
- `resources/views/livewire/kitchen.blade.php` — `/kitchen` page with two tabs:
  - **Ingredients** — add/edit/delete pantry items grouped by category; Amount is free-text.
  - **Recipes** — "Generate Recipe" calls AI with pantry ingredients + full health profile; AI instructed to use *only* listed ingredients; response parsed and saved; recipes expandable in-place.
- `app/Models/User.php` — `ingredients()` and `recipes()` HasMany relationships added.
- `app/Services/AiService.php` — pantry/ingredient list appended to system prompt with explicit `RECIPE CONSTRAINT` instruction; `friendlyError()` helper replaces raw API JSON in error messages (handles 401/403, 429, 503/529, 404).
- `routes/web.php` — `/kitchen` route added.
- `resources/views/components/layouts/app.blade.php` — "Kitchen" nav link added.

## [1.2.1] — 2026-05-19

> Medication Lookup shipped via the AI layer — all IDEAS.md items are now complete.

### Added
- `resources/views/livewire/health-record.blade.php` — **Medication Lookup**: teal "Look Up" button on every medication row in the Medications tab. Calls `AiService::chat()` with a targeted prompt (drug class, side effects, interactions with current medications, timing, availability). Result renders in a collapsible teal panel below the list with a disclaimer and close button. `lookupMedication(int $id)` + `closeLookup()` methods; lookup state resets with `resetForm()`.

## [1.2.0] — 2026-05-19

> AI Assistant layer. Claude 3.7 Sonnet (recommended) and Google Gemini 2.5 Flash supported out of the box; any OpenAI-compatible endpoint can be added. The full health profile is injected as background context on every query.

### Added
- `app/Services/AiService.php` — multi-provider AI service. Builds a comprehensive system prompt from the user's full profile (biometrics, latest record, baselines, goals, active conditions, medications, allergies, family history, lifestyle, last 7 health records). Supports Anthropic (default: `claude-3-7-sonnet-20250219`), Google Gemini (default: `gemini-2.5-flash`), and any OpenAI-compatible custom endpoint. API key stored encrypted via Laravel's `encrypt()`/`decrypt()`. `testConnection()` method for live validation.
- `resources/views/livewire/settings.blade.php` — new **AI Assistant** settings card: enable/disable toggle, provider selector, model field (auto-filled per provider, fully editable), masked API key field, custom base URL field (Custom provider only), "Test Connection" button with inline pass/fail result. `saveAi()` and `testAiConnection()` methods; switching provider auto-fills the correct default model.
- `resources/views/livewire/ai.blade.php` — `/ai` chat page: conversation history (session-scoped), animated typing indicator, quick-start suggestion buttons (nutrition, medication interactions, exercise, BP trend), "Clear conversation" button, disabled state with Settings link when AI is not configured. Disclaimer footer on every response.
- `routes/web.php` — `/ai` route added to auth group.
- `resources/views/components/layouts/app.blade.php` — "AI Assistant" link added to main nav.

### Fixed
- `app/Services/AiService.php` — Gemini default model updated from deprecated `gemini-1.5-flash` to `gemini-2.5-flash`. Output token limit raised from 1024 to 4096 (Anthropic) / 8192 (Gemini) to prevent truncated health advice responses.

## [1.1.0] — 2026-05-18

> Personal Health Record (PHR) module. All PHR ideas from `IDEAS.md` implemented except Medication Lookup and AI (both deferred pending internet-access strategy).

### Added — Personal Health Record module
- **8 new database tables:** `personal_info`, `allergies`, `conditions`, `surgeries`, `family_history`, `screenings`, `medications`, `lifestyle_profiles` — all user-scoped with `BelongsToAuthUser` trait and `cascadeOnDelete`.
- **8 new Eloquent models:** `PersonalInfo` (explicit `$table = 'personal_info'`), `Allergy`, `Condition`, `Surgery`, `FamilyHistory` (explicit `$table = 'family_history'`), `Screening`, `Medication`, `LifestyleProfile`. `Screening` and `Medication` include date casts; `LifestyleProfile` casts `sleep_hours` to decimal.
- `app/Models/User.php` — added `personalInfo()`, `lifestyleProfile()`, `allergies()`, `conditions()`, `surgeries()`, `familyHistory()`, `screenings()`, `medications()` relationships.
- `resources/views/livewire/profile.blade.php` — two new editable sections appended: **Personal & Emergency Information** (blood type, pronouns, two emergency contacts, PCP, health insurance, patient notes) and **Lifestyle Profile** (dietary regimen, food restrictions, caffeine, physical activity, sleep, tobacco/alcohol use, wellness goals). Each has its own save button with "Saved." feedback. Both use `updateOrCreate` via the respective relationship.
- `resources/views/livewire/health-record.blade.php` — new `/health-record` page: single Volt component with pill-style tab navigation across six sections: **Allergies** (severity colour-coded badges), **Conditions** (status badges), **Surgeries**, **Family History**, **Screenings** (next-due date highlighted red when overdue/within 30 days), **Medications** (grouped Active → Paused → Discontinued, category + status badges). Every section: add / edit / delete CRUD with inline forms and `wire:confirm` on delete.
- `routes/web.php` — `/health-record` route added to auth group.
- `resources/views/components/layouts/app.blade.php` — "Health Record" link added to main nav.

### Fixed
- `app/Models/PersonalInfo.php`, `app/Models/FamilyHistory.php` — added explicit `$table` property to prevent Laravel's auto-pluralisation resolving to `personal_infos` / `family_histories`.

## [1.0.0] — 2026-05-18

> Official v1.0.0 release. All core health-tracking features complete, plus Household Mode, per-user profile editing, and chart baseline/goal overlays. 57 tests / 106 assertions green.

### Fixed
- `resources/views/livewire/dashboard.blade.php` — charts were blank on first load after login because chart init was bound to `DOMContentLoaded`, which does not fire on Livewire `wire:navigate` transitions. Switched to `livewire:navigated` with `{ once: true }`.

### Added — Profile & Chart Refactor
- `resources/views/livewire/profile.blade.php` — new `/profile` page: displays physiological baselines (pulse, systolic, diastolic) as read-only, and exposes editable forms for personal biometrics, starting measurements, and all goal fields. Saves with live "Saved." feedback. Profile link added to main nav.
- `resources/views/livewire/dashboard.blade.php` — weight chart now shows a grey dashed "Starting" line at `baseline_weight` alongside the existing dotted "Target" line. `goalLine()` JS helper accepts an optional `dash` parameter to distinguish line styles.

### Added — Household Mode (Settings + Picker + User Management + Shared Meals)
- `database/migrations/2026_05_18_153203_create_settings_table.php` + `app/Models/Setting.php` — app-global key/value settings store with `Setting::get()` / `Setting::set()` statics.
- `resources/views/livewire/settings.blade.php` — `/settings` page with `auth_mode` toggle (`login` | `household`).
- `resources/views/livewire/household.blade.php` — `/household` guest page: profile-card picker for passwordless `Auth::loginUsingId()` login; "Add New Member" button leads to `/register`; trash-icon delete per card with `wire:confirm`; only shows users with complete profiles.
- `resources/views/livewire/meal-log.blade.php` — "+ Household Meal" button (household mode only): shared meal form with "Who ate this?" member checkboxes; creates one `Meal` record per selected member.
- `resources/views/livewire/onboarding.blade.php` — after completing onboarding in household mode, redirects to `/household` instead of `/dashboard`.
- `resources/views/livewire/auth/register.blade.php` — shows "← Back to household" instead of "Sign in" link when in household mode.
- `routes/web.php`, `bootstrap/app.php` — root `/` and logout redirect, and guest unauthenticated redirect, all branch on `auth_mode`.
- `resources/views/components/layouts/app.blade.php` — Profile and Settings links added to main nav.
- `tests/Feature/SettingsTest.php` + `tests/Feature/HouseholdTest.php` — 15 new tests.

### Added — Tracking Infrastructure
- `BUGS.md` + `IDEAS.md` — bug tracker and feature-idea backlog. `AGENTS.md` updated with a Tracking Files section.

## [0.9.0] — 2026-05-12

> First native desktop release. All Phase 0–11 checklist items complete. The app runs fully stand-alone as a macOS desktop application via NativePHP/Electron with SQLite and no external dependencies.

### Added — Phase 10 (Polish & Launch)
- `database/seeders/DatabaseSeeder.php` — demo account `demo@ihealth.test` / `password` seeded with a full male profile, 30 days of health records (gradual weight loss trend), and 3 realistic meals per day.
- `README.md` rewritten — local setup instructions, stack table, demo credentials, architecture notes (multi-tenancy, onboarding middleware, HealthService, SQLite date quirk, NativePHP readiness), Livewire-over-Filament note.
- `vendor/bin/pint` applied — style fixed in `bootstrap/app.php`, `bootstrap/providers.php`, `HistoryExportController`, `PrivacyTest`, `HistoryTableTest`, `routes/web.php`.

### Changed — Phase 10
- `CHANGELOG.md` — `[Unreleased]` content promoted to `[0.1.0] — 2026-05-11`.

### Added — Phase 11 (Native Packaging)
- `nativephp/desktop:dev-main` installed via Composer — the first NativePHP release with `illuminate/contracts ^13.0` support (not yet tagged; `symfony/finder` downgraded v8→v7.4, within Laravel 13's `^7.4|^8` constraint).
- `app/Providers/NativeAppServiceProvider.php` — `Artisan::call('migrate', ['--force' => true])` called on boot so app migrations run in the packaged context; window configured 1280×800, min 900×600, `rememberState()`.
- `config/nativephp.php` — published; auto-updater disabled (`NATIVEPHP_UPDATER_ENABLED=false`).
- `.env` — `NATIVEPHP_APP_ID=com.ihealth.app`, `NATIVEPHP_APP_VERSION=1.0.0`, author, copyright, description. Session/cache/queue drivers changed to `file`/`file`/`sync` for NativePHP compatibility.
- `public/icon.png` (1024×1024), `public/icon.icns`, `public/icon.ico` — application icon set.
- `php artisan native:build mac` — produced ad-hoc-signed macOS bundles: `iHealth-1.0.0-arm64.dmg/zip` and `iHealth-1.0.0-x64.dmg/zip` in `nativephp/electron/dist/`. 42/42 tests green throughout.

### Fixed — Phase 11 (NativePHP + PHP 8.5 compatibility)
- `vendor/nativephp/php-bin/bin/mac/{arm64,x64}/php-8.5.zip` — created as symlinks pointing to `php-8.4.zip`. `nativephp/php-bin` ships 8.3 and 8.4 binaries only; PHP 8.5 is pre-release and not yet packaged. `BuildCommand.php` derives the binary version from PHP constants (`PHP_MAJOR_VERSION.PHP_MINOR_VERSION` = 8.5), so without the symlink the build finds no binary and the app silently produces a window-less dock icon.
- `composer.json` — added `"platform-check": false` to the `config` section. Without this, Composer generates `vendor/composer/platform_check.php` with a `PHP_VERSION_ID >= 80500` guard; when the bundled PHP 8.4 binary runs `artisan`, Composer throws a fatal error before Laravel can boot. Setting `platform-check: false` suppresses generation of this file entirely — the bundled PHP version is an implementation detail, not a public API constraint.

## [0.1.0] — 2026-05-11

> First numbered release — web build complete. All Phase 0–10 checklist items done. Phase 11 (NativePHP stand-alone packaging) tracked separately for `v1.0.0`.

### Added — Phase 0 (Housekeeping & Auth Scaffolding)
- `CHECKLIST.md` — phased implementation roadmap for the V4.0 Master Specification, covering schema, models, scoping, onboarding, the `HealthService`, dashboard, history table, privacy, tests, and polish.
- `CHANGELOG.md` — this file, following Keep a Changelog 1.1.0 and SemVer 2.0.0.
- `CHECKLIST.md` Phase 11 — Native Packaging (NativePHP) covering install, path discipline, window config, build pipeline, icons, code signing, and smoke testing for the eventual stand-alone `v1.0.0` build.
- `CHECKLIST.md` "NativePHP discipline" rules in Phase 0 — path helpers only, no external services, no SMTP at runtime, disable `MustVerifyEmail`, no Vite dev-server dependency.
- `livewire/livewire@4.3.0` and `livewire/volt@1.10.5` Composer dependencies.
- `app/Providers/VoltServiceProvider.php` (generated by `volt:install`) mounting Volt on `resources/views/livewire/` and `resources/views/pages/`.
- `resources/views/components/layouts/app.blade.php` — authenticated layout with header, logout form, and Vite assets.
- `resources/views/components/layouts/guest.blade.php` — centred card layout for login/register.
- `resources/views/livewire/auth/login.blade.php` — Volt class-based component with rate limiting (5 attempts), `Lockout` event dispatch, and `redirectIntended()`.
- `resources/views/livewire/auth/register.blade.php` — Volt class-based component with default password rules and `Registered` event dispatch; auto-logs in on success.
- `resources/views/livewire/dashboard.blade.php` — placeholder for Phase 3/6.
- `lang/en/{auth,passwords,pagination,validation}.php` — published via `php artisan lang:publish` so `auth.failed` / `auth.throttle` keys resolve.

### Changed — Phase 0
- Bumped PhpStorm project PHP language level from **8.3** to **8.5** in `.idea/php.xml` to match the runtime used when the project was created.
- Bumped `composer.json` `require.php` constraint from `^8.3` to `^8.5` for the same reason.
- `routes/web.php` rewritten: `/` redirects based on auth state; `guest`-grouped `/login` + `/register` Volt routes; `auth`-grouped `/dashboard` Volt route and `POST /logout` action with full session invalidation.
- Herd PHP version isolated to **8.5** for this site (`herd isolate 8.5`). Note: Herd's CLI symlink still resolves to `php84` globally, so composer/artisan are invoked via the `php85` binary for now.

### Decided — Phase 0
- **UI stack: Livewire** (with Volt where helpful), overriding the README's "Filament" mention. Reason: the V4.0 "Clean Look" dashboard (goal rings, goal-line charts, duplicate-date hiding) is not a natural fit for Filament's opinionated admin aesthetic, and Livewire pairs better with the multi-step onboarding wizard while staying pure-PHP.
- **Auth scaffolding: Strategy B — manual minimal install** of `livewire/livewire` + `livewire/volt`, hand-rolling login/register/logout/dashboard rather than overlaying the full Livewire starter kit. Email verification and password reset deliberately omitted. Reason: leaner, every component is intentional, no settings UI we'd rip out, fits an offline single-household app.
- **Distribution target: NativePHP at v1.0**, with SQLite (already chosen), no queues, no Redis, no external APIs in the runtime path. Reason: the spec is single-tenant-per-household and an honest stand-alone application is desired long-term.

### Removed — Phase 0
- `resources/views/welcome.blade.php` — replaced by the auth flow.

### Added — Phase 1 (Database Schema)
- `App\Enums\Gender` (string-backed: `male` / `female`) and `App\Enums\MealType` (string-backed: `breakfast` / `lunch` / `dinner` / `snack`) — each with a `label()` helper for UI display. Migration enum lists are derived from `array_column(Enum::cases(), 'value')` so the schema and PHP cases cannot drift.
- Migration `2026_05_11_143923_create_profiles_table` — biometrics, baseline measurements, baseline physiology, and goal fields, with a unique `user_id` FK (cascade on delete).
- Migration `2026_05_11_143924_create_health_records_table` — body / vitals / activity columns (all nullable so partial daily entries are valid), with a composite `(user_id, date)` index for fast per-user history queries.
- Migration `2026_05_11_143924_create_meals_table` — `meal_type`, `description`, `calories`, also indexed on `(user_id, date)`.

All three migrations use `foreignIdFor(User::class)->constrained()->cascadeOnDelete()` for refactor-safe FKs.

### Added — Phase 2 (Models & Scoping)
- `App\Models\Scopes\OwnedByAuthUser` — global Eloquent scope filtering by `auth()->id()` when a user is authenticated. Deliberately a no-op when no user is logged in so artisan, tinker, factories, and seeders are not crippled; defence-in-depth comes from policies in Phase 8 and the auto-fill `creating` hook below.
- `App\Models\Concerns\BelongsToAuthUser` trait — single source of truth that registers the global scope, auto-fills `user_id = auth()->id()` on create (when not provided), and exposes the `user()` `BelongsTo` relation. Each scoped model just `use`s the trait.
- `App\Models\Profile`, `App\Models\HealthRecord`, `App\Models\Meal` — all use `BelongsToAuthUser`, declare `#[Fillable]` attributes, and define a `casts()` method covering enum / date / decimal columns.
- `App\Models\User` — added `profile()` (HasOne), `healthRecords()` (HasMany), `meals()` (HasMany) relations.
- `Database\Factories\{ProfileFactory,HealthRecordFactory,MealFactory}` for testing — gender-aware `baseline_hip` (only set for `Gender::Female`), realistic biometric ranges, and nullable optional fields to mirror the schema.
- Verified end-to-end via `php artisan tinker`: Alice and Bob each see only their own records (3 vs. 5 health_records, 4 vs. 7 meals), `Meal::create([...])` without `user_id` auto-fills to the authenticated user, and enum / date casts roundtrip.

### Added — Phase 3 (Onboarding)
- `App\Http\Middleware\EnsureProfileComplete` — redirects authenticated users without a profile to `/onboarding`; no-op for guests and the onboarding route itself. Registered via `appendToGroup('web', ...)` in `bootstrap/app.php`.
- `resources/views/livewire/onboarding.blade.php` — five-step Volt class-based onboarding wizard: (1) Biometrics, (2) Starting Measurements, (3) Physiological Norms, (4) Goals (all optional), (5) Review & confirm with live BMI / BFP preview via `HealthService`. Hip field shown only for female gender. Per-step validation with sane biometric ranges. Saves a `Profile` and redirects to dashboard on confirm.
- `/onboarding` Volt route added to the `auth` middleware group in `routes/web.php`.

### Added — Phase 4 (HealthService)
- `App\Services\HealthService` (`final class`) — stateless, container-resolvable service implementing every derived metric in the V4.0 spec:
    - `bmi(float, float): float` — metric BMI.
    - `bodyFatPercent(Gender, float, float, float, ?float): float` — U.S. Navy metric BFP, branched by enum; inline `throw new ValueError(...)` if hip is missing for `Gender::Female`.
    - `pulseDeviation(int, int): float` — signed percent from baseline.
    - `bloodPressureVariance(int, int, int, int): BloodPressureVariance` — returns the DTO below.
    - `weightProgress(float, float): float` — `current - baseline`.
    - Typed class constant `public const float BP_THRESHOLD = 0.15;` (PHP 8.3+ typed constants).
- `App\Services\BloodPressureVariance` — `final readonly class` DTO carrying both percentages, both threshold flags, and an `anyExceeds()` helper. Constructor uses property promotion.
- `tests/Unit/HealthServiceTest.php` — 16 Pest tests / 28 assertions covering: reference BMI/BFP/pulse/BP/weight values, symmetric negative BP variance, female-without-hip error case, hip-ignored-for-male, float stability, and exact-zero edge cases. All green (213ms).

### Added — Phase 5 (Daily Entry UI)
- `resources/views/livewire/daily-record.blade.php` — Volt component for creating, editing, and deleting a `HealthRecord`. Receives `$date` from the dashboard parent; displays body / vitals / activity fieldsets; all measurement fields nullable (partial entries valid); delete requires `wire:confirm`.
- `resources/views/livewire/meal-log.blade.php` — Volt component for logging multiple meals per day. Meals sorted by meal order (breakfast→snack); inline add/edit form with type select + description + calories; running daily total shown. Add and edit share the same form.
- Dashboard rewritten: owns the date picker (`wire:model.live="date"`); passes the selected date into both child components via props + `:key` to force re-mount on date change.

### Added — Phase 6 (Dashboard)
- `resources/views/partials/goal-ring.blade.php` — pure SVG circular progress ring; accepts `$pct`, `$label`, `$value`, `$goal`, `$color`; calories ring turns red at 100%+.
- Dashboard (`resources/views/livewire/dashboard.blade.php`) fully implemented:
    - **Today summary card** — weight with delta from target, BMI, BFP, pulse with deviation %, BP with SYS/DIA variance badges (red if >±15% via `HealthService`).
    - **Goal rings** — daily calories / water / exercise progress shown only when goals are set on the profile.
    - **Trend charts** (Chart.js 4) — Weight + target goal line, dual-axis BMI/BFP, Blood Pressure + baseline lines, Pulse + baseline line. Charts hidden until ≥2 records with weight exist. Baseline/target values rendered as dashed goal lines.
- Chart.js 4 CDN script added to `app.blade.php` layout.

### Added — Phase 7 (History Table)
- `resources/views/livewire/history.blade.php` — paginated history table with per-page selector (10/25/50/100, URL-synced via `#[Url]`). Meals rendered as sub-rows beneath their parent health record; date cell is `invisible` on sub-rows to maintain table alignment while achieving the "clean look" duplicate-date hiding.
- `App\Http\Controllers\HistoryExportController` — streamed CSV export combining health records and meals; meal sub-rows mirror the table's visual grouping. Accessible via `GET /history/export`.
- Dashboard/History nav links added to the app layout; active link highlighted.

### Added — Phase 8 (Privacy & Access Control)
- `App\Policies\ProfilePolicy`, `HealthRecordPolicy`, `MealPolicy` — all `final`; gate each model's `view` / `update` / `delete` actions to the owning user (`$user->id === $model->user_id`). Defence-in-depth alongside the global Eloquent scope.
- `tests/Feature/PrivacyTest.php` — 12 tests / 23 assertions verifying: per-user global scope isolation (health records, meals, profiles), `creating` hook auto-fills `user_id`, and all three policy gates (allow owner / deny other).
- `RefreshDatabase` trait enabled for all Feature tests in `tests/Pest.php`.

### Added — Phase 9 (Tests)
- `tests/Feature/OnboardingTest.php` — 5 tests: middleware redirects unprofiled users to onboarding, allows profiled users to reach dashboard, does not redirect on the onboarding route itself, blocks guests from dashboard and onboarding.
- `tests/Feature/HistoryTableTest.php` — 7 tests: page renders, empty state, record data visible, meal sub-rows render, invisible class applied to sub-row date cells, cross-user data isolation, CSV export content.
- `tests/Feature/ExampleTest.php` — updated to assert `GET /` redirects guests to login (was incorrectly asserting 200).
- `tests/Pest.php` — `RefreshDatabase` enabled for all Feature tests.
- **42 tests / 79 assertions — all green.**

### Fixed — Phase 9
- Onboarding Volt component: added missing `#[Layout('components.layouts.guest')]` and `#[Title]` attributes (was returning 500 without a layout).
- History component and CSV export: replaced `Meal::whereIn('date', ...)` with `orWhereDate()` — SQLite stores Eloquent `date`-cast values as `Y-m-d H:i:s`, causing bare string `whereIn` comparisons to silently return zero rows.

[Unreleased]: about:blank
[1.0.0]: about:blank
[0.1.0]: about:blank
