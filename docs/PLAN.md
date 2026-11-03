# Build plan

Read with `CLAUDE.md` (the brief), `docs/DECISIONS.md` and `docs/PROGRESS.md`.

## Shape of the app

One Laravel 12 app, server rendered with Blade. Two audiences with separate guards:

* `citizen` guard: phone number plus SMS one-time code. Session based. Mobile first views.
* `web` guard: leaders, leader staff and platform admins. Email and password through Fortify, with an authenticator app as a required second factor.

All AI calls run in queued jobs. Web requests only write rows and dispatch jobs.

```
app/
  Actions/            single-purpose write operations (CreateSubmission, PostClusterUpdate, RecordRating ...)
  Ai/                 Anthropic client, intake analyser, brief writer, schemas, cost meter
  Embeddings/         Voyage client and a local hashing embedder used when no key is set
  Sms/                Africa's Talking gateway, log gateway, SMS conversation flow
  Http/Controllers/   Citizen/, Leader/, Admin/, Webhooks/
  Http/Requests/      one Form Request per write route
  Http/Middleware/    security headers and CSP, 2FA enforcement, citizen locale
  Jobs/               ProcessSubmission, RecalculateCluster, RefreshClusterSummary, CheckDoneClusters,
                      DispatchWeeklyBriefs, PollBriefBatches, SendSms
  Models/
  Notifications/      IssueUpdated, IssueMarkedDone, IssueReopened, plus an SMS channel
  Policies/
  Services/           ClusterRanker, Clusterer, IdentityHasher, AuditLogger, PhotoStore, SpendGuard
config/ranking.php    ranking weights
config/ai.php         models, prices, spend cap, thresholds
config/civic.php      categories, severity, routing of categories to offices, rating rules
```

## Tables

| Table | Notes |
| --- | --- |
| counties, constituencies, wards, areas | hierarchy; areas are villages or estates inside a ward |
| offices | type (mca, mp, governor, senator, women_rep), morph to the jurisdiction (ward, constituency or county) |
| users | leaders, staff, admins. role column, leader_id for staff, Fortify 2FA columns |
| leaders | office_id, user_id, display name, term dates |
| citizens | phone (encrypted cast), phone_hash (HMAC, unique), ward_id, locale, trust_score, verified_at |
| otp_codes | phone_hash, code_hash, expires_at, attempts, consumed_at |
| submissions | body, original_language, translation, category, severity, ward, area, photo_path, channel, kind (report or voice), processing state, embedding vector(1024). No citizen column. |
| submission_authors | identity table: submission_id, citizen_ref (HMAC of citizen id), citizen_id (encrypted) |
| issue_clusters | title, summary, category, ward, routed office, status, severity, resident_count, areas_count, trend, score, centroid vector, first_reported_at, last_activity_at, done_at, done_round, rating window state |
| cluster_memberships | submission_id (unique), cluster_id, similarity |
| cluster_updates | the brief's `Update`. cluster, leader, author user, status, message, expected completion, photo, published_at, notify flag |
| ratings | cluster, round, citizen_ref, verdict, stars, praise_tags, thanks, channel |
| weekly_briefs | ward, week_start, body, rising_topics, improving_topics, batch reference |
| ai_batches | Anthropic batch id, kind, status, request map |
| ai_usage_logs | job, model, ward, tokens by type, cost in USD, batch flag |
| sms_sessions | phone_hash, step, data, expires_at |
| audit_logs | actor, action, subject, before, after, ip, created_at. Trigger blocks UPDATE, DELETE and TRUNCATE. |
| notifications | Laravel database notifications for citizens |

Database triggers also block DELETE on submissions, changes to what a citizen wrote, and any UPDATE or DELETE on ratings and published updates.

## Policies and gates

* `SubmissionPolicy`: citizens create and see their own. `update` and `delete` return false for everyone.
* `RatingPolicy`: a citizen who reported the cluster may rate once per Done round. No update or delete.
* `IssueClusterPolicy`: `view` (public data), `respond` (leader or staff whose jurisdiction covers the ward), `markDone` (leader only), `addVoice` (citizen in the same ward, cluster not done).
* `ClusterUpdatePolicy`: `create` (leader or staff), `publish` (leader only), `update` (author, drafts only).
* `AuditLogPolicy`: leaders and staff see entries on their own account, admins see all.
* `AdminPolicy` style gates: `admin`, `leader-dashboard`, `viewHorizon`.

## Jobs and schedule

* `ProcessSubmission` (queue `intake`): Haiku intake, schema validation, embedding, clustering, ranking. On invalid output it marks the submission unprocessed and retries with backoff.
* `RecalculateCluster` (queue `default`): counts, spread, trend, score.
* `RefreshClusterSummary` (queue `intake`): Haiku rewrites the cluster title and summary when membership crosses 3, 10, 25, 50, 100.
* `CheckDoneClusters` daily 06:15: applies the reopen rule.
* `DispatchWeeklyBriefs` Monday 05:30: one Fable batch for every active ward, skipped when the spend cap is reached.
* `PollBriefBatches` every 15 minutes: stores finished briefs and logs tokens.
* `backup:clean` and `backup:run` daily, `backup:monitor` daily.
* `horizon:snapshot` every five minutes.

## Blade components

`x-layout.citizen`, `x-layout.leader`, `x-status-pill`, `x-issue-card`, `x-stat-tile`, `x-rating-stars`, `x-icon`, `x-severity-tag`, `x-field-error`, `x-flash`, `x-turnstile`.

## Phases

1. Setup, Sail, pgvector, Redis, pilot seeders, citizen OTP login, Fortify with 2FA for leaders.
2. Submissions, identity split, encrypted casts, rate limits, audit log and triggers.
3. AI intake, embeddings, clustering, ranking, Horizon.
4. Citizen views.
5. Leader dashboard, updates, notifications, rating record, audit view.
6. Ratings and the reopen job.
7. SMS flow and SMS ratings.
8. Weekly briefs through the Batch API, token logging, spend cap.
9. Hardening, translations, privacy notice, deployment config and docs.
