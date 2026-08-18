# Health Suite

[![Buy Me A Coffee](https://img.shields.io/badge/Buy%20Me%20a%20Coffee-FFDD00?style=flat-square&logo=buy-me-a-coffee&logoColor=black)](https://buymeacoffee.com/jldesignnetwork)
![GVS](https://img.shields.io/badge/GVS-2606.2.1--s-6366f1?style=flat-square)

A personal health-tracking desktop application for households. Tracks daily weight, body measurements, vitals, meals, and exercise; shows trend charts; and computes derived metrics (BMI, US Navy body fat %, blood pressure variance) relative to each user's individual baselines.

Built as a stand-alone macOS app using **NativePHP** — it installs like any desktop app, stores all data locally in SQLite, and requires no internet connection, no account, and no subscription.

---

## Stack

| Layer | Choice |
|---|---|
| Runtime | NativePHP (Electron + bundled PHP 8.4) |
| Framework | Laravel 13 |
| Language | PHP 8.5 (development), PHP 8.4 (bundled binary) |
| UI | Livewire 4 + Volt (class-based components), Tailwind v4 |
| Database | SQLite (per-household, stored in OS app-data directory) |
| Charts | Chart.js 4 |
| Tests | Pest 4 — 57 tests / 106 assertions |
| AI | Anthropic Claude / Google Gemini / OpenAI-compatible (configurable) |

---

## Features

- **Household Mode** — switch between standard login (email + password) and a password-free household picker in **Settings**. In household mode, an Apple TV–style profile-card screen replaces the login page; tap a card to sign in instantly. Members can be added (full onboarding wizard), deleted (cascades to all data), and selected for individual or shared entries — all without leaving the app.
- **Multi-user** — each household member has a fully isolated dataset; all records are scoped by user at the database level (`OwnedByAuthUser` global Eloquent scope + Policies).
- **Onboarding wizard** — 5-step wizard collects biometrics, starting measurements, physiological norms, and optional goals before the dashboard is accessible.
- **Profile page** — view and edit personal information (gender, DOB, height), starting measurements (weight, neck, waist, hip), and goals (target weight, calorie, water, exercise targets) at any time. Physiological baselines (resting pulse, BP) remain read-only after onboarding.
- **Daily entry** — log body measurements (weight, neck, waist, hip), vitals (blood pressure, pulse), water intake, and exercise minutes for any date.
- **Meal log** — add, edit, and delete personal meals with optional calorie count (`✦ Estimate` button uses AI to estimate from description). In household mode, a **Household Meal** form logs one meal for multiple members. Both forms include a **saved recipe picker** — select a recipe to auto-fill description and calories, or type a custom meal. Ingredient usage tracking: check off pantry items used per meal to automatically reduce stock (`quantity_on_hand`).
- **Dashboard**
  - Today summary card: weight delta from target, BMI, body fat %, pulse deviation %, and BP variance badges (red/green at ±15% of baseline).
  - Goal rings: SVG circular progress for daily calories, water, and exercise.
  - Trend charts: weight with **starting weight** (grey dashed) and target (indigo dotted) reference lines; BMI/BFP dual-axis; blood pressure + baseline lines; pulse + baseline line. Charts hidden until ≥ 2 records with weight exist.
- **Health Record** (`/health-record`) — complete Personal Health Record (PHR) module with six tabbed sections: **Allergies** (severity colour-coded), **Conditions** (status badges: Active / Managed / Remission / Resolved), **Surgeries**, **Family History**, **Screenings & Immunisations** (next-due date highlighted red when overdue), and **Medications** (grouped Active → Paused → Discontinued; category + status badges). Every section: full add / edit / delete CRUD with inline forms.
- **Medication Lookup** — "Look Up" button on every medication row sends a targeted AI query covering drug class, side effects, interactions with the user's current medications, timing recommendations, and OTC/prescription status. Result displayed inline with a disclaimer.
- **History table** — paginated (10/25/50/100 per page, URL-synced); meals rendered as sub-rows beneath each health record; duplicate date cells hidden visually.
- **CSV export** — streamed export of the full history at `GET /history/export`.
- **Privacy** — `OwnedByAuthUser` global scope and `creating` hook on every model; `ProfilePolicy`, `HealthRecordPolicy`, `MealPolicy` for defence-in-depth.
- **Kitchen** (`/kitchen`) — two-tab module:
  - **Ingredients** — pantry with free-text amount, numeric stock count (`Qty on hand`), category grouping, live search/filter, stock badges (in stock / low / depleted), shared toggle in household mode.
  - **Recipes** — AI-generated using only pantry ingredients; respects dietary regimen, allergies, health goals; meal-type selector (Breakfast → Side Dish); **# of people** (1–12); variety engine rotates away from overused ingredients; saved recipes shared across household in household mode.
- **AI Assistant** — conversational health advisor at `/ai`. The user's full health profile (conditions, medications, allergies, baselines, lifestyle, recent records) is injected as background context on every query. Supports Anthropic Claude (recommended: `claude-3-7-sonnet-20250219`), Google Gemini (`gemini-2.5-flash`), and any OpenAI-compatible endpoint. Configured in Settings with an API key, model field, and live test connection. Quick-start suggestions on first open (nutrition, medication interactions, exercise, BP trend).
- **Settings** — app-level settings page (`/settings`): `auth_mode` toggle (login / household); AI provider/model/key configuration with live model fetch from the provider API (model input becomes a dropdown of currently available models); **Backup & Restore** — export all household data as a dated JSON file, or restore from a previous backup (merges settings, matches users by email, full re-import of all health data).
- **Backup format** — single JSON file containing all users, profiles, health records, meals, medications, ingredients, recipes, and settings. Suitable for migrating to a new device or rolling back to a previous state.

---

## Installation, Development & Architecture

Full setup, testing, and native-build instructions live in the in-repo wiki:

- [`docs/usage.md`](docs/usage.md) — installation (packaged app), local development setup, running tests, building the native app.
- [`docs/architecture.md`](docs/architecture.md) — multi-tenancy/privacy model, household mode, onboarding, `HealthService` formulas, NativePHP runtime, and implementation history.
- [`docs/index.md`](docs/index.md) — wiki index.

---

## Project Docs

- [`CHANGELOG.md`](CHANGELOG.md) — full release history (Keep a Changelog + GVS).
- [`.dev/ROADMAP.md`](.dev/ROADMAP.md) — multi-generational strategic roadmap.
- [`.dev/2606/backlog.json`](.dev/2606/backlog.json) — active generation task/issue register.
- [`.dev/2606/ideas.json`](.dev/2606/ideas.json) — conceptual proposals.
- [`docs/`](docs/) — in-repo wiki (architecture, usage).

## Funding & Support

[![Buy Me A Coffee](https://img.shields.io/badge/Buy%20Me%20a%20Coffee-FFDD00?style=flat-square&logo=buy-me-a-coffee&logoColor=black)](https://buymeacoffee.com/jldesignnetwork)
