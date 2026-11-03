# Backups and restore

## What is backed up

`spatie/laravel-backup` runs every night at 01:30 Nairobi time (`routes/console.php`):

- a `pg_dump` of the whole database
- the private photo folder, `storage/app/private`, when photos are kept on the server

The archive is a zip encrypted with AES-256 using `BACKUP_ARCHIVE_PASSWORD`. It is written to every disk in `BACKUP_DISK`: `backups` on the server and `s3-backups` for an off-server copy. `backup:clean` at 01:00 thins old archives, and `backup:monitor` at 07:00 emails `BACKUP_NOTIFY_EMAIL` if the newest backup is more than a day old.

Keep a copy of `BACKUP_ARCHIVE_PASSWORD`, `APP_KEY` and `IDENTITY_HASH_KEY` somewhere other than the server, for example a password manager held by two people. Without `APP_KEY` the encrypted phone numbers and identity links in a restored database cannot be read. Without `IDENTITY_HASH_KEY` residents cannot sign in again.

When photos are on S3 (`PHOTOS_DISK=s3`), the photo archive is empty and the bucket itself holds the photos. Turn on object versioning for that bucket.

## Check that backups work

```bash
php artisan backup:list
```

Do a test restore into a scratch database at least once a quarter, following the steps below.

## Restore

1. Put the site into maintenance mode and stop the workers.

```bash
php artisan down
```

```bash
php artisan horizon:terminate
```

2. Download the archive you want from the backup disk and unzip it with the archive password. With 7-Zip:

```bash
7z x peoples-manifesto-2026-11-03-01-30-00.zip
```

The archive holds `db-dumps/postgresql-manifesto.sql` and, if photos are local, `storage/app/private/...`.

3. Restore the database into an empty database with pgvector available.

```bash
dropdb manifesto && createdb manifesto
```

```bash
psql -d manifesto -c "CREATE EXTENSION IF NOT EXISTS vector;"
```

```bash
psql -d manifesto -f db-dumps/postgresql-manifesto.sql
```

The dump includes the triggers that keep the audit log, ratings and reports unchangeable, so they are back in force as soon as the restore finishes.

4. Copy the photos back.

```bash
rsync -a storage/app/private/ /path/to/site/storage/app/private/
```

5. Make sure `.env` has the same `APP_KEY` and `IDENTITY_HASH_KEY` as when the backup was taken. Then clear caches and bring the site back.

```bash
php artisan optimize
```

```bash
php artisan up
```

6. Start Horizon again (Forge restarts the daemon on its own; on Render, redeploy the worker).

7. Record the restore in the incident log: who did it, which archive, and why. Under the Data Protection Act, a loss of personal data must be reported to the ODPC within 72 hours of becoming aware of it.
