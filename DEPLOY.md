# Deploying STACKx Ad Intelligence — Laravel Cloud

The app is Laravel 13 + Inertia/React on Vite, with Supabase Postgres as the
database. **It is not a Node app — it does not deploy to Vercel.** We deploy to
[Laravel Cloud](https://cloud.laravel.com), which natively handles asset builds,
migrations, queue workers, and the scheduler.

## Before you start — three non-negotiables

1. **Email (SMTP) — required for login.** Sign-in is passwordless magic links.
   In production you MUST configure a real mailer (Resend, Postmark, Amazon SES,
   Mailgun…). Without it, **no one can sign in.**
2. **Database password + migrations.** Get `DB_PASSWORD` from Supabase →
   Project Settings → Database. Use the **session pooler, port 5432** (not the
   transaction pooler 6543 — it breaks Laravel's prepared statements). The first
   deploy runs `php artisan migrate --force`, which also enables the RLS
   deny-all lock on Postgres.
3. **Production flags.** `APP_ENV=production`, `APP_DEBUG=false`, a generated
   `APP_KEY`, and an `https://` `APP_URL` (secure cookies).

See [`.env.production.example`](./.env.production.example) for the full list.

## Steps

1. **Sign in** to https://cloud.laravel.com with GitHub.
2. **Create application** → connect `stackx-digital/stackx` → pick the branch.
3. **Database** → keep **Supabase** (we need pgvector for P3 later). Add the
   `DB_*` variables from `.env.production.example` (port **5432**, real password).
   Alternatively provision Laravel Cloud Postgres, but Supabase stays the
   pgvector host.
4. **Environment variables** → paste from `.env.production.example` and fill in
   secrets: `APP_KEY`, `DB_PASSWORD`, `MAIL_*`, `STACKX_ALLOWED_EMAILS`,
   `ANTHROPIC_API_KEY` (or set `AI_PROVIDER=openai` + `OPENAI_API_KEY`).
5. **Build command:**
   ```bash
   composer install --no-dev --optimize-autoloader && npm ci && npm run build
   ```
6. **Deploy command:**
   ```bash
   php artisan migrate --force && php artisan optimize
   ```
   (`optimize` caches config, routes, and views in one step.)
7. **Enable Worker + Scheduler** (one toggle each). Not strictly needed for P1
   today — imports/scoring/AI run in-request — but they're the foundation for
   P2–P5 (Meta sync, batch tagging, Slack reports), which is why we're on
   Laravel.
   - Worker command: `php artisan queue:work --tries=3 --max-time=3600`
   - Scheduler: runs `php artisan schedule:run` every minute (managed).
8. **Deploy.** HTTPS, FrankenPHP/Octane, and zero-downtime releases are handled
   for you.

## First sign-in

1. Add your email to `STACKX_ALLOWED_EMAILS` (full email or `@stackx.my`).
2. Optionally pre-seed team users once: `php artisan db:seed` (idempotent).
3. Go to `/login`, enter your email, click the magic link from the SMTP inbox.

## Post-deploy smoke test

- `/login` → magic link arrives → lands on `/analytics`.
- "Load demo data" → "Recompute scores" → scored table with winners/losers.
- "Generate AI insights" → inferred tags + recommendations (needs an AI key).

## Notes

- The app trusts proxy headers (`trustProxies(at: '*')`) so it sees HTTPS behind
  Laravel Cloud's load balancer — required for correct URLs and secure cookies.
- Sessions, cache, and queue are all database-backed, so scaling to multiple
  app instances + a worker is safe with no extra config.
