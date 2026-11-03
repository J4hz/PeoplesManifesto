# Progress

Read with `CLAUDE.md` and `docs/PLAN.md`.

## Phase status

| Phase | Status |
| --- | --- |
| 1. Setup, Sail, pgvector, Redis, pilot seeders, resident OTP sign-in, Fortify with two factors | Done |
| 2. Submissions, identity split, encrypted casts, rate limits, audit log with database triggers | Done |
| 3. AI intake with Haiku, embeddings, clustering, ranking, Horizon | Done |
| 4. Resident views: raise issue, my ward, rate the fix, my reports | Done |
| 5. Leader dashboard: overview, respond, updates, notifications, rating record, audit log | Done |
| 6. Ratings and the reopen job | Done |
| 7. SMS flow with Africa's Talking, including SMS ratings | Done |
| 8. Weekly briefs with Fable through the Batches API, token logging, spend cap | Done |
| 9. Hardening: headers and CSP, Turnstile, backups, Sentry, translations, privacy notice, deployment | Done |

Phases 2 to 8 were built together and landed in one commit once the whole suite passed, rather than one commit per phase. Phase 9 is its own commit.

## Final checks

- `./vendor/bin/pest`: 121 tests, 524 assertions, all passing. They cover the Policies, the reopen rule, ranking, rate limits (OTP per number and per IP, reports, ratings, inbound SMS), AI output validation (twelve kinds of bad output), the database triggers, the SMS conversation, the batch brief flow and the spend cap.
- `php artisan migrate:fresh --seed` runs cleanly. The demo seeder pushes reports through the real intake, clustering, ranking, update, rating and brief code with the local stand-ins.
- The app was driven end to end in Chromium with the seeded data: resident sign-in with an SMS code, raising and rating, leader sign-in with an authenticator code, responding, and the admin pages. No console or CSP errors.
- `php artisan optimize` caches config, routes, views and events without errors.
- `./scripts/check-style.sh` finds no em or en dashes, no emojis, no `{!! !!}` anywhere in Blade, no exclamation marks in copy and none of the banned words.

## Not verified here

- The Docker image and `compose.yaml` were written but not built, because this environment has no Docker daemon. Build and run them once before relying on them.
- Real calls to Anthropic, Voyage and Africa's Talking were not made. All three are exercised in tests through Laravel's HTTP fakes with the documented request and response shapes.
- `backup:run` was not run against a real backup disk.

## What I need you to do

1. Keys: create an Anthropic API key, a Voyage AI key and an Africa's Talking account, and put them in `.env`. Check the prices in `config/ai.php` against the current pricing pages and set `AI_MONTHLY_CAP_USD`.
2. Africa's Talking: test in the sandbox first, then apply for a short code (and a sender ID if you want named SMS). Set `AT_WEBHOOK_TOKEN` to a long random string and point the SMS callback at `https://your-domain/webhooks/africastalking/{token}/sms`. Ask Africa's Talking for their callback IP ranges and set `AT_WEBHOOK_IPS`. Confirm whether inbound SMS will be free to residents.
3. Cloudflare: proxy the domain, SSL Full (strict), lock the origin to Cloudflare addresses, set `TRUSTED_PROXIES`, create a Turnstile widget and set both keys, and exclude the webhook path from Bot Fight Mode.
4. ODPC: register the operating organisation as a data controller (and processor if relevant) with the Office of the Data Protection Commissioner, then set `OPERATOR_NAME`, `OPERATOR_PRIVACY_EMAIL` and `OPERATOR_ODPC_REGISTRATION`. Decide the retention period for reports and backups and add it to the privacy notice (marked TODO in `resources/views/pages/privacy.blade.php`). Have a lawyer read both language versions.
5. Pilot data: get the list of villages and estates for each pilot ward from the ward administrators and add them under Admin, Wards and areas. Confirm the eight Ruiru ward names against the current IEBC list.
6. Accounts: run `php artisan manifesto:create-admin` on the server, then invite each leader and their staff from the admin area once they agree to take part.
7. Secrets: generate `APP_KEY`, `IDENTITY_HASH_KEY` and `BACKUP_ARCHIVE_PASSWORD`, and keep copies off the server (see `docs/RESTORE.md`). Set up an off-server backup bucket.
8. Sentry: create a project and set `SENTRY_LARAVEL_DSN`.

## Summary

The platform takes reports from residents by web or SMS in English, Kiswahili or Sheng, stores who sent them apart from what they said, sorts each report with Claude Haiku under a strict schema, groups similar reports with pgvector and ranks the groups by distinct residents, severity, spread, growth and account trust. Leaders see only their own jurisdiction, post public updates (staff draft, leaders publish), and mark work done. Residents who reported an issue rate the fix, and a daily job reopens it when most say it was not fixed. Leaders get a Monday brief written by Claude Fable through the Batches API, with token costs logged per ward and a monthly cap. Every leader and admin action goes to an audit log that the database itself refuses to change.
