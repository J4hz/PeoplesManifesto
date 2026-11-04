# Deploying on Laravel Cloud

Laravel Cloud runs the app from this GitHub repository. Everything below is set in the Cloud dashboard. There is no config file to commit.

Check each step against the current Laravel Cloud docs as you go. Product menus change, and a few points below are marked "check" where the docs were not clear.

## 1. Application and environment

1. Create an application from the GitHub repository and pick the branch to deploy.
2. Region: EU Central (Frankfurt). It is the closest Laravel Cloud region to Kenya.
3. PHP version: 8.3 or newer. The app needs the `gd`, `exif`, `intl`, `pdo_pgsql` and `redis` extensions. Check they are in the Cloud image before the first deploy, because photo uploads fail without `gd` and `exif`.

## 2. Database: Laravel Serverless Postgres

1. Add a Postgres database and attach it to the environment. Cloud injects the connection settings.
2. pgvector is supported. The first deploy's migrations create the `vector` extension themselves, so there is nothing to run by hand.
3. Hibernation: a sleeping database wakes on the first query, which adds a delay. Africa's Talking gives up on slow callbacks, so turn hibernation off for production, or set a long sleep timeout.
4. Point-in-time restore on the Cloud database is your first line of recovery. Keep the nightly encrypted backup from step 6 as the copy you control.

## 3. Cache, queues and sessions: Laravel Valkey

1. Add a Valkey key-value store and attach it. Cloud injects the Redis connection settings (`REDIS_HOST` and the rest).
2. Horizon needs the Redis queue driver. Set these as custom environment variables, which override anything Cloud injects:

```
QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=redis
```

## 4. Compute

App cluster (web traffic):
- At least 1 replica. Leave it able to scale up for busy days.

Worker cluster (background work), set not to scale to zero:
- Add a custom background process with the command `php artisan horizon` and 1 process. Cloud restarts it after every deploy and if it exits, so the `horizon:terminate` step from the Forge script is not needed.
- Turn on the Scheduler toggle here rather than on the App cluster. The reopen check, the weekly briefs and the batch polling must run on time, and Cloud's docs advise a worker cluster that never sleeps for that.
- Keep this cluster at 1 replica to start. Background processes are multiplied by the replica count.

Every scheduled task in `routes/console.php` already uses `onOneServer()`, so they run once even with several replicas.

## 5. Photos: a private object storage bucket

Cloud's file system is not kept between deploys, so photos must go to a bucket.

1. Create a bucket with private visibility, for example with the disk name `photos`. Do not make it the default disk.
2. Attach it to the environment and redeploy. Cloud injects the disk settings under that name.
3. Set `PHOTOS_DISK=photos` (the disk name you chose). Photos are then streamed through the app after a policy check and never linked directly.

## 6. Backups

1. Create a second private bucket, for example with the disk name `backups`, and attach it.
2. Set `BACKUP_DISK=backups` and a long `BACKUP_ARCHIVE_PASSWORD`.
3. The nightly `backup:run` needs `pg_dump` in the Cloud image (check). After the first deploy, run `php artisan backup:run` once from the environment's command runner and confirm an archive appears in the bucket. If `pg_dump` is missing, rely on Cloud's database restore and say so in `docs/RESTORE.md`.

## 7. Build and deploy commands

Build commands (caching belongs here, not in the deploy step):

```
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
npm ci
npm run build
php artisan optimize
```

Deploy commands (run just before the new version goes live):

```
php artisan migrate --force
```

## 8. Environment variables

Cloud injects the database, Valkey and bucket settings. Add the rest as custom variables. See the README table and `.env.example` for what each one does.

```
APP_NAME="People's Manifesto"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain
APP_KEY=
IDENTITY_HASH_KEY=
TRUSTED_PROXIES=*
SESSION_SECURE_COOKIE=true
QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=redis
PHOTOS_DISK=photos
BACKUP_DISK=backups
BACKUP_ARCHIVE_PASSWORD=
BACKUP_NOTIFY_EMAIL=
ANTHROPIC_API_KEY=
AI_MONTHLY_CAP_USD=50
VOYAGE_API_KEY=
AT_USERNAME=
AT_API_KEY=
AT_SANDBOX=false
AT_SHORT_CODE=
AT_SENDER_ID=
AT_WEBHOOK_TOKEN=
AT_WEBHOOK_IPS=
TURNSTILE_SITE_KEY=
TURNSTILE_SECRET_KEY=
SENTRY_LARAVEL_DSN=
MAIL_MAILER=
MAIL_FROM_ADDRESS=
OPERATOR_NAME=
OPERATOR_PRIVACY_EMAIL=
OPERATOR_ODPC_REGISTRATION=
```

Notes:
- `APP_KEY` and `IDENTITY_HASH_KEY` must never change after launch. Keep copies off the platform, as `docs/RESTORE.md` explains.
- `TRUSTED_PROXIES=*` is safe only because all traffic reaches the app through Cloud's own edge. It lets the per-IP rate limits see the resident's real address.
- Cloud does not send email. Set `MAIL_MAILER` and its keys for a provider such as Postmark, Resend or SES, for leader password links and reopen notices.
- Redeploy after changing variables. They take effect only on the next deploy.

## 9. Domain, Turnstile and Africa's Talking

1. Add your custom domain in Cloud and follow its DNS steps. HTTPS is handled by Cloud.
2. Create a Cloudflare Turnstile widget for the domain and set its two keys. Turnstile works whether or not the domain's DNS is on Cloudflare.
3. In Africa's Talking, set the SMS callback URL to `https://your-domain/webhooks/africastalking/{AT_WEBHOOK_TOKEN}/sms`.

## 10. First admin

From the environment's command runner, run:

```
php artisan manifesto:create-admin you@example.org "Your Name"
```

The admin gets an email to set a password, then must add an authenticator app at first sign-in. Invite leaders and staff from the admin area after that.

## 11. Check it works

- Open `/up`. It should return 200.
- With `AT_SANDBOX=true` and `AT_USERNAME=sandbox`, sign in as a resident using the Africa's Talking simulator. Then switch to the live account and sign in with a real phone.
- Raise a report and check it is sorted within a minute, under Admin, Report sorting.
- Open `/horizon` as the admin and check both supervisors are running.
