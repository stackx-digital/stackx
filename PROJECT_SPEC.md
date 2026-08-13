# STACKx Ad Intelligence — Project Spec & Claude Code Kickoff

> Paste this whole file as your first prompt in Claude Code, **or** commit it as `PROJECT_SPEC.md` at repo root and start with: *"Read PROJECT_SPEC.md and build Milestone 1. Confirm your plan before writing code."*

---

## 0. Role & operating rules (read first)

You are a senior full-stack engineer building an **internal** ad-intelligence tool for **STACKx**, a Malaysian digital marketing agency. This is a Skaler-style creative analytics + competitor-spy + AI-ad-creation platform, but scoped for our own team — **not** a multi-tenant SaaS. No billing, no public signup, no app-store polish. Optimise for correctness, speed of iteration, and honest data.

Rules:
- **Confirm the plan before writing code.** For each milestone, propose file structure + schema first, wait for my go.
- **Never hardcode secrets.** Everything via `.env.local` (documented in `.env.example`).
- **Small, composed components.** No 500-line files. Server components by default; client components only when needed.
- **Ask before destructive DB changes.** Use migrations, never edit prod data directly.
- **Be honest about data provenance.** Anything computed = computed. Anything AI-inferred = labelled as inferred in the UI. Never present hallucinated numbers as real metrics.
- If a step needs Meta app review / access I don't have yet, **stub it behind a feature flag** and keep building.

---

## 1. Tech stack (pinned — this is our house stack, don't substitute)

- **Next.js 14+** (App Router, TypeScript, Server Actions)
- **Supabase** — Postgres, Auth, Storage, **pgvector**
- **Tailwind CSS + shadcn/ui**
- **TanStack Query** for client data fetching where needed
- **Anthropic API** (Claude) for the AI layer — model via `ANTHROPIC_MODEL` env, structured JSON outputs
- **Meta Marketing API** (ad account insights) + **Meta Ad Library API** (competitor spy)
- **Vercel** deploy; **Vercel Cron** (or n8n on our NAS) for scheduled syncs
- Package manager: **pnpm**

Use the **Supabase MCP** for schema/migrations and the **Vercel MCP** for deploy config if available in the session.

---

## 2. What we're building (5 pillars)

| # | Pillar | What it does |
|---|--------|--------------|
| P1 | **Creative Analytics** | Sync Meta ad + insights data → auto-tag each ad (format/hook/angle/audience) → score on 4 funnel stages → winners/losers report with scale/cut recommendations |
| P2 | **Brand Spy** | Track competitors via Meta Ad Library → their active ads, longest-running, angles, hooks |
| P3 | **Ad Discovery** | Semantic search (pgvector) across saved/scraped ads |
| P4 | **Ad Creation** | Take a winner (ours or a competitor's) → Claude generates copy/angle/hook variations |
| P5 | **Reports** | Shareable internal report views + scheduled Slack summary |

**MVP = P1 end-to-end.** Ship that fully before touching P2–P5. Everything else is phased.

---

## 3. The scoring model (IMPORTANT — get this architecture right)

Scores are the core value. Split responsibilities cleanly:

**Deterministic (compute in TypeScript, NOT AI):**
Four funnel scores per ad, each normalised **0–100 as a percentile rank within the ad account** (so scores always spread and are relative to our own baseline, not absolute):
- **Hook Score** ← 3-sec video plays / impressions (hook rate). Fallback: CTR (all).
- **Watch Score** ← ThruPlays / impressions (hold rate). Fallback: Hook Score.
- **Click Score** ← link CTR.
- **Convert Score** ← ROAS, or inverse of cost-per-result when ROAS absent.

If a metric is missing across the whole account, mark that score `N/A` — do not fabricate.

Winner/loser classification: rank by ROAS (fallback: cost-per-result) **weighted by spend** so low-spend flukes don't top the list. Emit an action per ad: `scale` / `keep` / `cut` with a threshold-based reason.

**AI-inferred (Claude, labelled as inferred in UI):**
- Creative tags: `format`, `hook_type`, `angle`, `audience` — inferred from ad name + (Phase 2) creative thumbnail via Claude vision.
- Strategic recommendation text ("why scale / why cut") layered on top of the deterministic action.

> Constraint for MVP: Meta insights export gives us ad **name + metrics**, not the creative image. So MVP tagging infers from naming conventions + metrics. Phase 2 pulls creative thumbnails via Meta API and adds **Claude vision** tagging — build the tagging service with a pluggable input (text now, image later).

---

## 4. Data model (Supabase — propose migrations, don't assume)

Sketch to refine with me:

- `brands` — our clients (id, name, meta_ad_account_id)
- `ad_accounts` — connected Meta accounts (token ref, brand_id)
- `ads` — (id, ad_account_id, meta_ad_id, name, status, created_at, thumbnail_url)
- `ad_metrics` — daily rows (ad_id, date, spend, impressions, reach, ctr_all, ctr_link, cpc, cpm, thruplays, video_3s, results, cost_per_result, roas)
- `ad_scores` — (ad_id, computed_at, hook, watch, click, convert, action, action_reason)
- `ad_tags` — (ad_id, format, hook_type, angle, audience, inferred_by, confidence)
- `competitors` — (id, name, meta_page_id)
- `competitor_ads` — (id, competitor_id, ad_library_id, first_seen, last_seen, body, media_url, days_running)
- `ad_embeddings` — pgvector (ad_id/source, embedding, kind)
- `ad_variations` — (id, source_ad_id, product, prompt, output, created_by)

Enable **RLS** even for internal use (org-scoped). Use a single `org` for STACKx now; keep it multi-org-ready in schema but don't build org UI.

---

## 5. Integrations

**Meta Marketing API (P1):**
- For internal use, prefer a **System User token** (Business Manager) over per-user OAuth — simpler, no consumer login flow.
- Needs `ads_read`. Note advanced-access/app-review requirement; until granted, support a **manual CSV import** path (Ads Manager export) as the primary MVP ingest, with the API sync behind a flag.
- Build ingest as: `parseMetaCsv()` (flexible header mapping — Meta column names vary by locale/currency, e.g. "Amount spent (MYR)", "CTR (link click-through rate)", "ThruPlays") → normalise → upsert `ads` + `ad_metrics`.

**Meta Ad Library API (P2):**
- Public API, no user token, but region/political-ad limits apply. Handle rate limits + pagination. Store into `competitor_ads`, dedupe by ad_library_id, track `days_running`.

**Anthropic API (all AI):**
- Central `lib/ai/claude.ts` wrapper. Structured JSON outputs (system prompt: "respond with JSON only, no prose"), Zod-validated. Batch ads (~8–10/request) for tagging to control cost/latency; show progress. Retry + graceful degrade if AI fails (analytics must still work without tags).
- Model + max tokens from env.

**Slack (P5):** incoming webhook for scheduled report summary.

---

## 6. Design direction

Internal **performance cockpit** — used daily by media buyers who scale/cut like traders. NOT generic dark-SaaS.
- Base: deep ink slate (`#0E141B`), lifted panels (`#171F2A`), hairline borders (`#26313F`).
- Type: **Space Grotesk** (display/UI), **Inter** (body), **JetBrains Mono** for all numbers/metrics (tabular figures, aligned columns).
- Accents: amber `#FFB020` (primary/scale-signal), green `#34D399` (winner), red `#F76B6B` (cut), muted blue `#5B9BD5` (neutral).
- **Signature element:** the 4-segment **creative score meter** (Hook/Watch/Click/Convert) — a compact segmented bar rendered per ad; it's the visual DNA repeated across table rows, ad detail, and reports.
- Currency in **RM** (MYR). Mobile-first, keyboard focus visible, respects `prefers-reduced-motion`.

---

## 7. Milestones (build in order, confirm plan per milestone)

**M1 — Scaffold + auth + shell**
Next.js + Tailwind + shadcn + Supabase client. Supabase Auth (email allowlist for STACKx team only). App shell with cockpit theme, nav for the 5 pillars (P2–P5 as empty "coming soon" states). `.env.example`.

**M2 — Ingest + data model (P1 core)**
Migrations for M4's tables. CSV import UI (drag-drop) with flexible Meta header mapping + a "Load demo data" seed (realistic MY-context sample ads) so the app is usable with zero setup. Upsert into `ads` + `ad_metrics`.

**M3 — Scoring engine**
Deterministic percentile scoring service + winner/loser/action classifier (spend-weighted). Unit tests on the scoring math. Render the score meter component.

**M4 — Analytics report (P1 done)**
Account summary (total spend, blended ROAS/CPA, sales). Scored ad table (sortable by any score/metric), creative grouping, winners & losers sections, per-ad detail drawer.

**M5 — AI tagging + recommendations**
Claude tagging service (batched, Zod-validated), inferred tags shown with an "inferred" badge. AI scale/cut reasoning layered on the deterministic action.

**M6+ — P2 Brand Spy → P3 Discovery (pgvector) → P4 Ad Creation → P5 Slack reports.** Spec each when we reach it.

---

## 8. First task for this session

1. Confirm you understand the scope and the deterministic-vs-AI split in §3.
2. Propose the **M1** file structure + the Supabase Auth allowlist approach.
3. List the exact env vars you'll need in `.env.example`.
4. Wait for my go before writing code.

Do not start P2–P5. Do not build billing or public signup. Keep the scoring math deterministic and honest.
