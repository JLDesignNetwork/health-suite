# Security Policy

Health Suite is a local-first, zero-cloud application — all household health data is stored in a local SQLite database and never leaves the device except through user-initiated AI Assistant queries (Anthropic Claude / Google Gemini / OpenAI-compatible, configured with the user's own API key).

## Reporting a Vulnerability

Please use [GitHub Private Vulnerability Reporting](../../security/advisories/new) on this repository, or email:

```
jldesignnetwork@icloud.com
```

Do not open a public issue for security reports. We aim to acknowledge reports within 5 business days.

## Supported Versions

Only the latest tagged release (`main` branch) receives security fixes.

## Scope Notes

- API keys for AI providers are stored locally and used only for outbound requests the user explicitly initiates from the AI Assistant or Medication Lookup features.
- `.env`, `database/*.sqlite*`, and `auth.json` are excluded from AI tooling context via `.aiexclude` and from version control via `.gitignore`.
