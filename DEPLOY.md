# Deploying STACKx Ad Intelligence — Laravel Cloud

The app is Laravel 13 + Inertia/React on Vite, with a Postgres database
(pgvector for semantic search). **It is not a Node app — it does not deploy to
Vercel.** We deploy to [Laravel Cloud](https://cloud.laravel.com), which
natively handles asset builds, migrations, queue workers, and the scheduler.

It's a multi-tenant SaaS: anyone can self-register at `/register`, each signup
gets its own isolated workspace, and each tenant enters their own API keys at
`/settings`.

## Before you start — three non-negotiables

1. **A working database + migrations.** Either provision **Laravel Cloud
   Postgres** (simplest — vars injected for you, pgvector available) or point at
   **Supabase** using the **session pooler, port 5432** (not 6543 — it breaks
   prepared statements). If Supabase auth fails, reset the DB password in the
   Supabase dashboard and paste the new one. The first deploy runs
   `php artisan migrate --force`, which also enables the RLS deny-all lock.
2. **Production flags.** `APP_ENV=production`, `APP_DEBUG=false`, a generated
   `APP_KEY`, and an `https://` `APP_URL` — **with no `< >` angle brackets**
   (they cause "Invalid URI: host is malformed" and shell errors).
3. **Mail is optional.** Login is email + password, so mail is NOT required to
   sign in. It's only needed to send verification + password-reset emails. You
   can launch with `MAIL_MAILER=log` and bootstrap a pre-verified account from
   the CLI (see "First sign-in"), then add real SMTP later.

See [`.env.production.example`](./.env.production.example) for the full list.

## Steps

1. **Sign in** to https://cloud.laravel.com with GitHub.
2. **Create application** → connect `stackx-digital/stackx` → pick the branch.
3. **Database** → provision **Laravel Cloud Postgres** (recommended), or add the
   Supabase `DB_*` vars from `.env.production.example` (port **5432**, real
   password).
4. **Environment variables** → paste from `.env.production.example`. Required:
   `APP_KEY`, `APP_URL` (clean https), the `DB_*` set. Everything else
   (`MAIL_*`, AI/Meta keys) is optional — AI/Meta keys are entered per-tenant
   at `/settings`; the env values are only a fallback.
5. **Build command:**
   ```bash
   composer install --no-dev --optimize-autoloader && npm ci && npm run build
   ```
6. **Deploy command:**
   ```bash
   php artisan migrate --force && php artisan optimize
   ```
7. **Enable Worker + Scheduler** (one toggle each) — the foundation for the
   background syncs (Meta sync, batch tagging, alert detection).
   - Worker command: `php artisan queue:work --tries=3 --max-time=3600`
   - Scheduler: runs `php artisan schedule:run` every minute (managed).
8. **Deploy.** HTTPS, FrankenPHP/Octane, and zero-downtime releases are handled
   for you.

## First sign-in

**With SMTP configured:** go to `/register`, create an account, click the
verification link from your inbox, then you land on `/welcome`.

**Without SMTP (fastest to get in):** run this from the Laravel Cloud
**Commands** tab to create a ready-to-use, pre-verified account:

```bash
php artisan stackx:account you@company.com --company="Your Company"
```

It prints a generated password (or pass `--password=…`). Sign in at `/login`,
then finish the `/welcome` checklist (add an AI key, load demo data).

## Post-deploy smoke test

- `/register` → verify (or `stackx:account`) → `/welcome`.
- "Load demo data" → `/analytics` → "Recompute scores" → scored table.
- `/settings` → paste an Anthropic or OpenAI key → "Generate AI insights" on
  Analytics returns inferred tags + recommendations.

## Notes

- The app trusts proxy headers (`trustProxies(at: '*')`) so it sees HTTPS behind
  Laravel Cloud's load balancer — required for correct URLs and secure cookies.
- Sessions, cache, and queue are all database-backed, so scaling to multiple
  app instances + a worker is safe with no extra config.
- Tenant credentials are encrypted with `APP_KEY`. Don't rotate `APP_KEY` after
  tenants have saved keys, or those values become undecryptable.
