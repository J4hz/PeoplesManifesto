# The People's Manifesto

A non-partisan civic platform for Kenyan wards. Residents raise issues in their ward by phone or SMS. Reports about the same problem are grouped and ranked by how many residents raised them, how serious they are, how far they have spread and how fast they are growing. The right leader (MCA, MP, Governor and so on) sees the ranked list, posts public updates, and marks issues done. The residents who reported an issue then say whether it was really fixed. If most say no, it reopens.

The pilot covers Ruiru constituency in Kiambu County. Wards, constituencies and counties are data, so new areas need no code changes.

Built with Laravel 12, Blade, PostgreSQL with pgvector, Redis, Horizon, Fortify, Africa's Talking and the Anthropic API. See `docs/PLAN.md` for the structure and `docs/DECISIONS.md` for the choices made along the way.

## Run it locally with Sail

You need Docker, PHP 8.3 and Composer on the host for the first install.

```bash
cp .env.example .env
composer install
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
```

Then, in two more terminals:

```bash
./vendor/bin/sail artisan horizon
./vendor/bin/sail artisan schedule:work
```

Open http://localhost. Mail goes to Mailpit at http://localhost:8025.

With no API keys set the app runs on local stand-ins: a keyword analyser instead of Claude, a hashing embedder instead of Voyage, SMS written to `storage/logs/laravel.log` instead of sent, and weekly briefs written from the figures. Sign-in codes for residents appear in that log.

### Demo accounts

`migrate --seed` loads the pilot wards and, in the local environment only, demo data. Every demo staff account uses the password `password` and the authenticator secret `JBSWY3DPEHPK3PXP`. Add that secret to any authenticator app by hand to get the six digit codes.

| Account | Email |
| --- | --- |
| Platform admin | admin@manifesto.test |
| MCA for Kiuu | mca.kiuu@manifesto.test |
| Staff for the MCA | clerk.kiuu@manifesto.test |
| MP for Ruiru | mp.ruiru@manifesto.test |

Demo residents use the numbers 0700 000 001 to 0700 000 030. Staff sign in at `/staff/login`.

### Tests and checks

```bash
./vendor/bin/sail test
```

```bash
./scripts/check-style.sh
```

The test suite needs Postgres with pgvector. Sail creates the `manifesto_test` database on first start.

## Environment settings

Every value lives in `.env`. `.env.example` lists them all with comments.

| Setting | What it is for |
| --- | --- |
| `APP_KEY` | Encryption key for sessions and encrypted columns. Generate once with `php artisan key:generate`. |
| `APP_URL` | The public address, with https in production. |
| `APP_ENV`, `APP_DEBUG` | `production` and `false` on the live site. |
| `APP_TIMEZONE` | Defaults to `Africa/Nairobi`. The scheduler uses it. |
| `TRUSTED_PROXIES` | Proxy addresses whose forwarded headers are trusted. Cloudflare ranges, or `*` when the origin only accepts Cloudflare traffic. |
| `IDENTITY_HASH_KEY` | Key for the phone number and identity hashes. Generate a long random value and never change it after launch. Required in production. |
| `DB_*` or `DB_URL` | PostgreSQL 15 or newer with the pgvector extension. |
| `REDIS_*` or `REDIS_URL` | Queues, cache and sessions. |
| `SESSION_SECURE_COOKIE`, `SESSION_SAME_SITE`, `SESSION_ENCRYPT` | Cookie settings. Secure cookies are on by default in production. |
| `MAIL_*` | Password reset links and reopen notices for leaders. |
| `ANTHROPIC_API_KEY` | Claude for report intake (Haiku) and weekly briefs (Fable, through the Batches API). |
| `AI_INTAKE_MODEL`, `AI_BRIEF_MODEL` | Model names. Defaults are `claude-haiku-5-5` and `claude-fable-5-1`. |
| `AI_MONTHLY_CAP_USD` | Monthly AI budget. Weekly briefs pause at the cap. Intake continues up to 20 percent above it, then waits for the next month. |
| `VOYAGE_API_KEY`, `VOYAGE_MODEL` | Embeddings for grouping similar reports. |
| `CLUSTER_SIMILARITY_THRESHOLD` | How alike two reports must be to share an issue. 0.82 by default. |
| `AT_USERNAME`, `AT_API_KEY`, `AT_SANDBOX` | Africa's Talking account. Use `sandbox` and `AT_SANDBOX=true` while testing. |
| `AT_SHORT_CODE`, `AT_SENDER_ID` | The short code residents text, and the sender name for outgoing SMS. |
| `AT_WEBHOOK_TOKEN` | Long random string. Africa's Talking does not sign callbacks, so this token in the callback URL is the check. |
| `AT_WEBHOOK_IPS` | Optional comma separated list of Africa's Talking addresses to accept callbacks from. |
| `TURNSTILE_SITE_KEY`, `TURNSTILE_SECRET_KEY` | Cloudflare Turnstile on the sign-in and report forms. Missing keys fail closed in production. |
| `SENTRY_LARAVEL_DSN` | Error reporting. Personal data is not sent. |
| `PHOTOS_DISK` | `local` on a single server, `s3` where web and workers do not share a disk. Photos are never public. |
| `AWS_*` | The private bucket for photos when `PHOTOS_DISK=s3`. |
| `BACKUP_DISK`, `BACKUP_ARCHIVE_PASSWORD`, `BACKUP_S3_*`, `BACKUP_NOTIFY_EMAIL` | Encrypted daily backups. See `docs/RESTORE.md`. |
| `OPERATOR_NAME`, `OPERATOR_PRIVACY_EMAIL`, `OPERATOR_ODPC_REGISTRATION` | Shown on the privacy notice once set. |

## Deploy

Production needs PHP 8.3, PostgreSQL with pgvector, Redis, a queue worker running Horizon, and the scheduler. Do not use shared cPanel hosting: there is no way to run Horizon or keep secrets out of the web root there.

### Laravel Cloud

This is the planned host. The full checklist is in `docs/deploy/laravel-cloud.md`. In short:

1. Create the application from this repository in the EU Central (Frankfurt) region, on PHP 8.3 or newer.
2. Attach a Laravel Serverless Postgres database (pgvector is supported, and the migrations create the extension) and a Valkey store. Turn database hibernation off for production so SMS callbacks are not slowed by a cold start.
3. Add a worker cluster that never scales to zero. Give it a background process running `php artisan horizon` and turn its Scheduler toggle on.
4. Attach two private buckets, one for photos and one for backups, and set `PHOTOS_DISK` and `BACKUP_DISK` to their disk names.
5. Build commands: `composer install --no-dev`, `npm ci`, `npm run build`, `php artisan optimize`. Deploy command: `php artisan migrate --force`.
6. Add the custom environment variables listed in the guide, including `QUEUE_CONNECTION=redis`, then deploy and create the first admin with `php artisan manifesto:create-admin`.

The VPS and Docker options below need Cloudflare in front with SSL set to Full (strict).

### Laravel Forge on a VPS

1. Create a server in a region close to Kenya with PHP 8.3, PostgreSQL 17 and Redis.
2. On the server, install pgvector: `sudo apt install postgresql-17-pgvector`, then in the database run `CREATE EXTENSION vector;`.
3. Create the site from this repository. Paste `docs/deploy/forge-deploy.sh` as the deploy script.
4. Fill in the environment from `.env.example` with `APP_ENV=production`, `APP_DEBUG=false` and real keys.
5. Under Daemons, add `php artisan horizon`. Under Scheduler, turn on the Laravel scheduler.
6. Issue a Let's Encrypt certificate, then switch Cloudflare to Full (strict).
7. Create the first admin: `php artisan manifesto:create-admin you@example.org "Your Name"`.

The deploy script runs `php artisan optimize`, which caches config, routes, views and events.

### Render with Docker

`render.yaml` describes a web service, a Horizon worker, a scheduler worker, Postgres 17 and Redis, all in Frankfurt. In the Render dashboard choose New, Blueprint, and pick this repository. Render asks for each secret once.

Render services do not share a disk, so set `PHOTOS_DISK=s3` with a private bucket and `BACKUP_DISK=s3-backups`. Enable pgvector on the Render database with `CREATE EXTENSION vector;` before the first deploy. Migrations run before each deploy. Then open a shell on the web service and run `php artisan manifesto:create-admin`.

### Cloudflare

- Proxy the domain through Cloudflare and set SSL to Full (strict).
- Allow only Cloudflare addresses to reach the origin, then set `TRUSTED_PROXIES`.
- Create a Turnstile widget for the domain and set its two keys.
- Exclude `/webhooks/africastalking/*` from Bot Fight Mode so Africa's Talking can reach it.
- In Africa's Talking, set the SMS callback URL to `https://your-domain/webhooks/africastalking/{AT_WEBHOOK_TOKEN}/sms`.

## Where things are

- `app/Ai` Claude intake, briefs, schema checks, cost tracking and the spend cap
- `app/Embeddings` Voyage client and the local hashing embedder
- `app/Sms` Africa's Talking gateway and the SMS conversation
- `app/Services` ranking, clustering, identity hashing, audit log, photo storage
- `app/Policies` who may do what
- `config/ranking.php` ranking weights
- `config/civic.php` categories, routing of categories to offices, rating rules
- `config/ai.php` models, prices and the monthly cap
