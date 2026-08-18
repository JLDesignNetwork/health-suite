# Contributing to Health Suite

## Workflow

- Development happens directly on `main`. Commit atomically: code changes and their corresponding `.dev/2606/backlog.json` updates land in the same commit.
- Commit message conventions:
  - Backlog task: `Fix PROJ-TODO-XX: <description>`
  - Backlog issue: `Fix PROJ-ISSUE-XX: <description>`
  - General change (no backlog ID): Conventional Commits — `feat:`, `fix:`, `refactor:`, `docs:`, `style:`, `test:`, `chore:`

## Local Setup

See [`docs/usage.md`](../docs/usage.md) for full install/dev/test instructions.

## Quality Gates

Before submitting a change:

```bash
./vendor/bin/pint          # PHP formatting
./vendor/bin/pest          # Test suite (must stay green)
```

- Every feature or bug fix must be covered by a Pest test.
- Never delete or weaken an existing test assertion to make the suite pass — fix the underlying defect.
- Never hardcode filesystem paths — use `storage_path()`, `database_path()`, `config_path()`, `resource_path()`, `public_path()` (see `docs/architecture.md#perpetual-nativephp-discipline-guardrails`).

## Dependencies

Do not add, remove, or upgrade a `composer.json` / `package.json` dependency without prior approval. This project uses `pnpm` exclusively — `npm`/`yarn`/`npx` are not used; substitute `pnpm dlx` for `npx`.
