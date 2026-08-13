# STACKx Ad Intelligence

Internal creative-analytics + competitor-intelligence cockpit for the STACKx
team. Skaler-style, but scoped for our own use — no billing, no public signup,
no multi-tenant SaaS. See [`PROJECT_SPEC.md`](./PROJECT_SPEC.md) for the full
spec, pillars, scoring model, and milestones.

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
| M1 | Scaffold + magic-link auth + cockpit shell | ✅ |
| M2 | Ingest + data model (CSV import, migrations) | ✅ |
| M3 | Deterministic scoring engine + score meter | ✅ |
| M4 | Analytics report (P1 done) | ✅ |
| **M5** | AI tagging + recommendations (via the AI layer) | ✅ **MVP P1 complete** |
| M6+ | P2 Brand Spy → P3 Discovery → P4 Creation → P5 Reports | — |

## Getting started

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate

# DB: fill Supabase Postgres creds in .env (or set DB_CONNECTION=sqlite for
# a zero-setup local run), then:
php artisan migrate
php artisan db:seed        # pre-provision allowlisted team members (optional)

# Run (two terminals, or use a process manager):
php artisan serve          # http://localhost:8000
npm run dev                # Vite
```

### Signing in (passwordless)

1. Go to `/login`, enter a team email.
2. A magic link is emailed **only** to allowlisted addresses. In local dev
   `MAIL_MAILER=log` writes the link to `storage/logs/laravel.log` — open it and
   paste the URL.
3. The link is one-time and expires (`STACKX_MAGIC_LINK_TTL`, default 15 min).

Access is gated by `STACKX_ALLOWED_EMAILS` (comma-separated full emails or
`@domain` globs; empty = fails closed). An authenticated user off the allowlist
lands on `/not-authorized`.

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
  Http/Controllers/Auth/MagicLinkController.php   # passwordless login
  Http/Middleware/EnsureAllowlisted.php           # allowlist gate
  Notifications/MagicLoginLink.php
  Services/Ai/                                     # swappable AI layer
    AiManager.php · Contracts/AiProvider.php · Drivers/{Anthropic,OpenAi}Provider.php
  Support/{Allowlist.php, Facades/Ai.php}
config/{stackx.php, ai.php}
resources/js/
  Pages/{Analytics, Spy, Discovery, Create, Reports}/Index.tsx · Auth/Login.tsx
  Components/{ScoreMeter.tsx, ui/Button.tsx, shell/{Sidebar,Topbar,ComingSoon}}
  Layouts/AppLayout.tsx · config/nav.ts · lib/utils.ts
routes/{web.php, auth.php}
tests/  # allowlist, magic-link auth, AI manager
```

Currency is **RM (MYR)** throughout. Metrics render in JetBrains Mono with
tabular figures. The cockpit forces dark mode, shows visible keyboard focus,
and respects `prefers-reduced-motion`.

## Tests

```bash
php artisan test     # 18 passing: allowlist, magic-link auth, AI manager
npm run build        # tsc + vite production build
```
