# STACKx Ad Intelligence

Multi-tenant creative-analytics + competitor-intelligence SaaS. Skaler-style:
teams self-register, and each signup gets its own isolated workspace
(organization). Free/beta — no billing yet. See
[`PROJECT_SPEC.md`](./PROJECT_SPEC.md) for the full spec, pillars, scoring
model, and milestones.

## Stack

**Laravel 13** (PHP 8.4) + **Inertia.js + React 18 (TypeScript)** · **Vite** ·
**Tailwind CSS** · **Supabase** (Postgres + pgvector + Storage) ·
provider-agnostic **AI layer** (Anthropic / OpenAI, swappable) · **Meta
Marketing + Ad Library** APIs · **Laravel Queue/Scheduler/Horizon** for
background syncs.

> We chose Laravel + Inertia + React over Next.js because the workload is
> background-job heavy (Meta sync, batch AI tagging, embeddings, Ad Library
> scraping) — Laravel's queue/scheduler story fits that better, while Inertia
> keeps the React + cockpit UI.

## Milestone status

| Milestone | Scope | Status |
|-----------|-------|--------|
| M1 | Scaffold + auth + cockpit shell | ✅ |
| M2 | Ingest + data model (CSV import, migrations) | ✅ |
| M3 | Deterministic scoring engine + score meter | ✅ |
| M4 | Analytics report (P1 done) | ✅ |
| M5 | AI tagging + recommendations (via the AI layer) | ✅ **MVP P1 complete** |
| P2 | Brand Spy — competitor Ad Library tracking | ✅ |
| P3 | Ad Discovery — pgvector semantic search | ✅ |
| P4 | Ad Creation — AI copy variations | ✅ |
| P5 | Reports — shareable snapshots + weekly Slack | ✅ **all 5 pillars done** |
| SaaS 1 | Public signup + email verification + per-org tenancy | ✅ |
| SaaS 2 | Per-tenant BYO API keys (encrypted) + Settings page | ✅ |
| SaaS 3 | Onboarding welcome checklist + finish-setup banner | ✅ |

## Getting started

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate

# DB: fill Supabase Postgres creds in .env (or set DB_CONNECTION=sqlite for
# a zero-setup local run), then:
php artisan migrate

# Run (two terminals, or use a process manager):
php artisan serve          # http://localhost:8000
npm run dev                # Vite
```

### Signing up & in (SaaS)

1. Go to `/register`, create an account (name, optional company, email,
   password). Each signup provisions its own **organization** — a fresh, empty,
   isolated workspace.
2. A verification email is sent. In local dev `MAIL_MAILER=log` writes it to
   `storage/logs/laravel.log` — open the link to verify. App routes require a
   **verified** email (`/analytics`, `/spy`, …).
3. Sign in at `/login` with email + password. Password reset is at
   `/forgot-password`.
4. First verification lands on **`/welcome`** — a guided checklist (add an AI
   key, load demo data or import a CSV). Until it's finished or skipped, a
   "finish setup" banner shows across the app. Steps are derived from real
   state (has a key? has ads?), so the checklist can't drift out of sync.

**No SMTP yet?** Provision a ready-to-use, pre-verified account from the CLI:

```bash
php artisan stackx:account you@company.com --company="Your Agency"
# prints a generated password (or pass --password=…)
```

Every tenant only ever sees its own data — isolation is enforced by
`CurrentOrganization` (resolved from the signed-in user's `organization_id`)
plus the `BelongsToOrganization` global scope, with Postgres RLS deny-all as a
second layer.

## Per-tenant credentials (BYO keys)

Each workspace enters its own credentials at **`/settings`** — Anthropic /
OpenAI keys + model, embedding provider (OpenAI / Voyage), Meta Ad Library
token, and Slack webhook. They're stored **encrypted at rest** and never sent
back to the browser (the page only shows whether each is set).

On every authenticated request, `ApplyTenantSettings` middleware overlays the
tenant's stored values onto runtime `config()` (`config/ai.php`,
`embedding.php`, `ad_library.php`, `services.php`). Every downstream service —
`AiManager`, `EmbeddingManager`, `SlackNotifier`, `MetaAdLibraryClient` — keeps
reading `config()` unchanged, so it transparently uses that org's keys, falling
back to the deployment's env values when a tenant hasn't set one. Features
degrade honestly (and the Settings page shows a live "Ready / Not set" strip)
when no key is available.

## AI layer (provider-agnostic)

The AI layer uses Laravel's driver pattern — like `MAIL_MAILER` or
`QUEUE_CONNECTION`. Swap providers with **one env var**, no code change:

```env
AI_PROVIDER=anthropic   # or: openai
```

```php
use App\Support\Facades\Ai;

$tags = Ai::structuredJson($system, $userPrompt);          // default provider
$tags = Ai::driver('openai')->structuredJson($system, $u); // force a provider
```

Both `Anthropic` and `OpenAI` drivers ship (`app/Services/Ai/Drivers`), return
validated JSON objects, and throw `AiException` on failure so callers can
degrade gracefully — **analytics keeps working even when the AI layer is down**.

## Data provenance

Deterministic scores (M3) are computed in PHP and labelled as computed.
AI-inferred tags and recommendations (M5) are labelled **inferred** in the UI.
We never present hallucinated numbers as real metrics.

## Project structure

```
app/
  Http/Controllers/Auth/                           # register · login · verify · reset
  Http/Requests/Auth/LoginRequest.php              # email+password + rate limit
  Console/Commands/CreateAccountCommand.php        # stackx:account (SMTP-less bootstrap)
  Services/Ai/                                     # swappable AI layer
    AiManager.php · Contracts/AiProvider.php · Drivers/{Anthropic,OpenAi}Provider.php
  Support/{CurrentOrganization.php, Facades/Ai.php} # tenant scoping
  Models/Concerns/BelongsToOrganization.php        # org global scope
config/{stackx.php, ai.php}
resources/js/
  Pages/{Analytics, Spy, Discovery, Create, Reports}/Index.tsx
  Pages/Auth/{Login,Register,VerifyEmail,ForgotPassword,ResetPassword}.tsx
  Components/{ScoreMeter.tsx, auth/AuthShell.tsx, ui/Button.tsx, shell/*}
  Layouts/AppLayout.tsx · config/nav.ts · lib/utils.ts
routes/{web.php, auth.php}
tests/  # auth (register/login/verify/reset), tenancy isolation, scoring, AI
```

Currency is **RM (MYR)** throughout. Metrics render in JetBrains Mono with
tabular figures. The cockpit forces dark mode, shows visible keyboard focus,
and respects `prefers-reduced-motion`.

## Tests

```bash
php artisan test     # 18 passing: allowlist, magic-link auth, AI manager
npm run build        # tsc + vite production build
```
