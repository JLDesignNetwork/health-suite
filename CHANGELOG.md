# Changelog

All notable changes to **iHealth** will be documented in this file.

The format is based on [Keep a Changelog 1.1.0](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning 2.0.0](https://semver.org/spec/v2.0.0.html).

> No release version has been cut yet. All work is tracked under **[Unreleased]** and will be promoted to the first numbered release (planned `0.1.0`) when the Phase 0–Phase 10 checklist in [`CHECKLIST.md`](CHECKLIST.md) is complete.

## [Unreleased]

### Added
- `CHECKLIST.md` — phased implementation roadmap for the V4.0 Master Specification, covering schema, models, scoping, onboarding, the `HealthService`, dashboard, history table, privacy, tests, and polish.
- `CHANGELOG.md` — this file, following Keep a Changelog 1.1.0 and SemVer 2.0.0.
- `CHECKLIST.md` Phase 11 — Native Packaging (NativePHP) covering install, path discipline, window config, build pipeline, icons, code signing, and smoke testing for the eventual stand-alone `v1.0.0` build.
- `CHECKLIST.md` "NativePHP discipline" rules in Phase 0 — path helpers only, no external services, no SMTP at runtime, disable `MustVerifyEmail`, no Vite dev-server dependency.

### Changed
- Bumped PhpStorm project PHP language level from **8.3** to **8.5** in `.idea/php.xml` to match the runtime used when the project was created.
- Bumped `composer.json` `require.php` constraint from `^8.3` to `^8.5` for the same reason.

### Decided
- **UI stack: Livewire** (with Volt where helpful), overriding the README's "Filament" mention. Reason: the V4.0 "Clean Look" dashboard (goal rings, goal-line charts, duplicate-date hiding) is not a natural fit for Filament's opinionated admin aesthetic, and Livewire pairs better with the multi-step onboarding wizard while staying pure-PHP.
- **Auth scaffolding: Livewire starter kit auth**, but with email verification and password reset disabled. Reason: aligns with the chosen UI stack and works offline.
- **Distribution target: NativePHP at v1.0**, with SQLite (already chosen), no queues, no Redis, no external APIs in the runtime path. Reason: the spec is single-tenant-per-household and an honest stand-alone application is desired long-term.

[Unreleased]: about:blank
