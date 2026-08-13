# STACKx Ad Intelligence

Internal creative-analytics + competitor-intelligence cockpit for the STACKx
team. Skaler-style, but scoped for our own use — no billing, no public signup,
no multi-tenant SaaS. See [`PROJECT_SPEC.md`](./PROJECT_SPEC.md) for the full
spec, pillars, scoring model, and milestones.

## Stack

Next.js 14 (App Router, Server Actions, TypeScript) · Supabase (Postgres, Auth,
Storage, pgvector) · Tailwind + shadcn/ui · TanStack Query · Anthropic API
(Claude) · Meta Marketing + Ad Library APIs · Vercel. Package manager: **pnpm**.

## Milestone status

| Milestone | Scope | Status |
|-----------|-------|--------|
| **M1** | Scaffold + auth + cockpit shell | ✅ this build |
| M2 | Ingest + data model (CSV import, migrations) | next |
| M3 | Deterministic scoring engine + score meter | — |
| M4 | Analytics report (P1 done) | — |
| M5 | AI tagging + recommendations (Claude) | — |
| M6+ | P2 Brand Spy → P3 Discovery → P4 Creation → P5 Reports | — |

## Getting started

```bash
pnpm install
cp .env.example .env.local   # fill in the required vars (see below)
pnpm dev                     # http://localhost:3000
```

### Required env for M1

`NEXT_PUBLIC_SUPABASE_URL`, `NEXT_PUBLIC_SUPABASE_ANON_KEY`,
`STACKX_ALLOWED_EMAILS`, `NEXT_PUBLIC_APP_URL`. The rest are documented in
[`.env.example`](./.env.example) and wired in at their milestone.

### Supabase console setup (manual, one-time)

The allowlist is enforced in the app (middleware + `lib/auth/allowlist.ts`).
For defense in depth, in the Supabase dashboard:

1. **Authentication → Providers → Email**: enable email OTP / magic link.
2. **Authentication → Sign In / Providers**: disable public sign-ups if you
   want to block unknown emails from ever creating a session (they'd still be
   stopped by the allowlist, but this keeps the user table clean).
3. DB-level RLS (org-scoped) is added with migrations in **M2**.

## Auth model

Passwordless magic link. Access is gated by `STACKX_ALLOWED_EMAILS`
(comma-separated full emails or `@domain` globs; empty = fails closed). An
authenticated user off the allowlist lands on `/not-authorized`.

## Project structure

```
app/
  (auth)/login, (auth)/not-authorized   # unauthenticated + gated states
  auth/callback                          # magic-link code exchange
  (app)/                                 # authenticated cockpit shell
    analytics (P1) · spy (P2) · discovery (P3) · create (P4) · reports (P5)
  actions/                               # server actions
components/  ui/ · shell/ · score-meter  # score meter = signature §6 element
lib/         supabase/ · auth/ · utils   # clients, allowlist, RM formatting
config/nav.ts                            # single source for the 5 pillars
```

Currency is **RM (MYR)** throughout. Metrics render in JetBrains Mono with
tabular figures. Theme respects `prefers-reduced-motion` and shows visible
keyboard focus.

## Data provenance

Deterministic scores are computed in TypeScript and labelled as computed.
AI-inferred tags and recommendations are labelled **inferred** in the UI. We
never present hallucinated numbers as real metrics.
