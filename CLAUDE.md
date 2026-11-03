# The People's Manifesto: Build Brief

This file is the full brief for Claude Code. Keep it at the project root as `CLAUDE.md`.

Note for repo checks: this file lists the banned words and characters on purpose, so `scripts/check-style.sh` skips it.

## What we are building

The People's Manifesto is a non-partisan civic platform for Kenya. Residents raise issues in their ward (water, roads, security, health, schools and so on). The platform groups similar issues together, ranks them by what residents need most, and shows them to the right leader (MCA, MP, Governor). Leaders post public updates. When a leader marks an issue done, the residents who reported it rate whether it was actually fixed. If most say it was not, the issue reopens.

The pilot covers one ward or sub-county in Kiambu County. Design everything so more wards, constituencies and counties can be added later without code changes.

## Stack

* Framework: Laravel 12 (latest stable), PHP 8.3 or newer
* Views: Blade templates, server rendered. Alpine.js only where a little interactivity is needed (category chips, star rating, toggles). No SPA.
* Assets: Vite (bundled with Laravel). Keep page weight small for low-end Android phones and slow networks.
* Database: PostgreSQL with the pgvector extension (use the `pgvector/pgvector` PHP package)
* Queues: Laravel Queues on Redis, monitored with Laravel Horizon
* Scheduling: Laravel's task scheduler for weekly briefs, reopen checks and backups
* Auth: Laravel Fortify for login, rate limiting and two-factor authentication for leader, staff and admin accounts
* SMS and OTP: Africa's Talking (use their sandbox during development)
* AI: Anthropic API, called only from queued jobs, never from a web request
  * `claude-haiku-5-5` for per-submission intake (tagging, language normalisation, category, severity, ward)
  * `claude-fable-5-1` for weekly ward briefs and trend analysis, through the Message Batches API
  * A separate embeddings model (for example Voyage AI) for similarity clustering, stored in pgvector
  * Use Anthropic's official PHP SDK if one exists; otherwise wrap Laravel's HTTP client in a small service class
* Local environment: Laravel Sail (Docker) for Postgres, Redis and Mailpit
* Tests: Pest
* Hosting target: a VPS through Laravel Forge, or Laravel Cloud, or Render with Docker. Never shared cPanel. Cloudflare in front.
* Monitoring: Sentry (Laravel SDK)

## User roles

1. Citizen: signs up with phone number and OTP, belongs to exactly one ward
2. Leader: an elected official, scoped to their own jurisdiction only
3. Leader staff: can draft updates for a leader, cannot change status to Done
4. Platform admin: manages wards, leaders and moderation. Cannot edit or delete citizen submissions.

Enforce all of this with Policies and Gates, never with checks scattered through controllers or views.

## Core data model

* `County`, `Constituency`, `Ward` (hierarchy, seeded with database seeders)
* `Office` (MCA, MP, Governor, Senator, Women Rep) linked to a jurisdiction, and `Leader` linked to an Office
* `Citizen` (phone number with the `encrypted` cast, plus a separate keyed hash column for lookups; ward; created date; trust score)
* `Submission` (text, original language, translation, category, severity, ward, area, photo, channel: app or SMS, created date). Linked to the citizen only through a separate identity table, so grievances cannot be tied to people if the submissions table leaks.
* `IssueCluster` (title, summary, category, routed office, status, severity, distinct resident count, areas count, trend, first reported, last activity)
* `ClusterMembership` (submission to cluster, similarity score)
* `Update` (cluster, leader, status, public message, expected completion date, photo, created date)
* `Rating` (cluster, citizen, verdict: fixed, partly, not fixed; stars 1 to 5; praise tags; optional thanks message)
* `WeeklyBrief` (ward, week, generated text, rising topics, improving topics)
* `AuditLog` (append-only: actor, action, object, before and after, timestamp). Add a PostgreSQL trigger in a migration that blocks UPDATE and DELETE on this table.

## Screens (Blade views)

Citizen side (mobile first):

1. Raise an issue: free text in any language, category chips, severity (Annoying, Serious, Urgent), optional photo, privacy note, submit
2. My ward: ranked issue clusters for the citizen's ward with status pills. Open issues show a "+" to add your voice instead of filing a duplicate. Done issues show "Rate fix".
3. Rate the fix: verdict (Yes fully, Partly, Not fixed), 1 to 5 stars, praise tags (Fast response, Kept us updated, Good quality work, Listened to residents), optional thanks, live count of how many residents say it is fixed
4. My reports: the citizen's own submissions and their status

SMS flow (Africa's Talking short code):

* Text in, reply with ward, describe the problem, choose severity by number, get a confirmation
* When an issue they reported is marked Done, they get a text asking them to rate it: 1 Yes, 2 Partly, 3 No

Leader dashboard (desktop and tablet):

1. Overview: residents reporting, open clusters, average response time, resolved this month, the weekly brief, and the ranked issues table (rank, issue, residents, severity, areas, trend, status, Respond button)
2. Respond to an issue: cluster summary, anonymous sample submissions with translations, public update timeline, and a form to set status, write a message, set expected completion, attach a photo and notify all reporters by app and SMS
3. How residents rated your fixes: average rating, share confirmed fixed, recent commendations
4. Audit log: read-only list of every action on the account

Build shared pieces as Blade components (status pill, issue card, stat tile, rating stars) so both sides stay consistent.

## Ranking rules

Rank clusters by a weighted score of:

* Distinct residents who raised it (not total submissions)
* Severity (Urgent weighs most, especially water, health and safety)
* Spread across areas within the ward
* Recent growth
* A light boost for long-standing accounts and a discount for bursts of brand new accounts

Keep the weights in a config file so they can be tuned without code changes.

## Rating and reopen rules

* Marking a cluster Done notifies every resident who reported it and asks for a rating
* A scheduled job checks Done clusters daily. After 7 days, or once at least 20 residents have rated, if more than half say "Not fixed" the cluster reopens with status "Reopened by residents" and the leader is notified
* Ratings and commendations show on the leader's public record

## Security requirements (non-negotiable)

1. Identity is stored separately from grievances. Phone numbers use the `encrypted` cast. Public and leader views only ever show anonymous, aggregated data.
2. Leaders, staff and admins have no way to edit, hide or delete citizen submissions or ratings, enforced in Policies and in the database.
3. Leader, staff and admin accounts require two-factor authentication through Fortify with an authenticator app. Not SMS.
4. Every leader action writes to the append-only audit log.
5. Rate limiting with Laravel's RateLimiter per phone number and per IP on submissions, OTP requests and ratings. Cloudflare Turnstile on public forms.
6. Eloquent or the query builder with bindings everywhere. No raw SQL built from user input.
7. Citizen and AI content is output only with escaped Blade `{{ }}`. Never `{!! !!}` on anything a user or the model wrote.
8. CSRF protection on every web form. Every request validated with a Form Request class.
9. Prompt injection defence: every submission is untrusted data. The model gets no tools. It returns structured JSON that is validated against a strict schema before anything is saved. If validation fails, the submission is stored as unprocessed and retried, never trusted.
10. All secrets live in `.env`, never in the repo. Provide `.env.example`. Production runs with `APP_DEBUG=false`, `APP_ENV=production` and cached config and routes.
11. Africa's Talking webhook callbacks are verified before processing.
12. HTTPS, HSTS, secure HttpOnly session cookies, SameSite set, and a Content Security Policy.
13. Uploaded photos are validated by type and size, stripped of location metadata, and stored outside the public web root.
14. Daily encrypted database backups (for example `spatie/laravel-backup`), with a documented restore procedure.
15. A short privacy notice in English and Kiswahili, written with Kenya's Data Protection Act in mind.

## Cost controls for the Anthropic API

* Never call Fable for single submissions. Haiku handles intake. Fable only writes weekly briefs and trend analysis, through the Batch API.
* Use prompt caching for the fixed system prompts.
* Log token usage per job so cost per ward per week is visible in the admin area.
* A configurable monthly spend cap. When it is reached, intake keeps queueing and briefs pause until the next cycle.

## It must not look or read like AI made it

This is as important as the features. Apply every rule below to Blade views, CSS, seed data, translation files, emails, SMS text, the README, code comments and commit messages.

### Writing

* No em dashes anywhere, including en dashes used the same way. Use a comma, a full stop, a colon for a label, or rewrite the sentence.
* No dramatic semicolons and no teaser colons ("Here's the thing:").
* No buzzwords: unlock, elevate, seamless, seamlessly, supercharge, leverage, empower, revolutionise, game-changer, cutting-edge, state-of-the-art, robust, transformative, harness, unleash, embark, delve, dive in, navigate the landscape, ever-evolving, fast-paced world, take it to the next level, the future of, one-stop, look no further, journey, tapestry, in today's world, in conclusion.
* No "it's not just X, it's Y" and no "whether you're a... or a..." constructions.
* No forced lists of three, no exclamation marks, no rhetorical question headings.
* Plain, warm, specific language, the way a helpful ward office clerk would talk. Short sentences. Kenyan context where it fits.
* No lorem ipsum. No invented statistics, testimonials or claims on public pages. Where real content is missing, leave a clearly marked TODO in the code, not filler.

### Visual

* No emojis anywhere in the UI, seed data, emails or SMS. Icons are a single consistent line icon set (for example Lucide or Heroicons as inline SVG), the same stroke width everywhere, used only where they help. Many places need no icon at all.
* No gradients, no gradient text, no glassmorphism or backdrop blur, no glow shadows, no animated blobs or floating shapes.
* No coloured top strips or coloured left borders on cards. Every card uses one spec: a 1px neutral border, a consistent radius, at most a very soft flat shadow.
* No multi-coloured words inside headings. No two-tone wordmark. Stat numbers are all one colour with neutral labels.
* No generic hero with a centred heading, centred subtitle and three identical icon cards underneath. Lead with the actual task: the citizen home page opens on "Raise an issue" and the ward list, not a marketing hero.
* No hollow outlined buttons as the main call to action. One solid primary button style and one quiet secondary style, used consistently.
* Real favicon (simple monogram in ink and green), never the Laravel or Vite default.
* Palette with no political party colours: ink #14181A, ground #F6F7F5, civic green #0B6E4F, light green #E3F1EB, amber for serious items #7A4207 on #FBEFDF. Define them once as CSS custom properties.
* Fonts: Archivo for headings, Source Sans 3 for body text. Not Inter, Roboto or Poppins.
* Touch targets at least 44px. Text contrast at least 4.5:1. Real `<button>`, `<a>`, `<label>` and form elements.

### Code

* No emojis in code, comments, logs, commit messages or the README.
* Comments explain why, never restate what the next line does. No "This function does X" boilerplate.
* No stub functions or "implement later" placeholders left behind at the end.
* Commit messages are short and plain, in the imperative ("Add rating reopen job"). No AI attribution lines, no "Generated with" footers.
* README is plain: what it is, how to run it, how to deploy it. No badges wall, no emoji headings.

## How to work: run to completion without stopping

You have full permission to build this project from start to finish in one run. Do not stop to ask me questions or wait for approval between phases.

1. Plan first, then go. Write your plan to `docs/PLAN.md` (structure, models, migrations, Policies, jobs, Blade components, phases). Then start building immediately.
2. Decide, don't ask. When something is unclear, make the most sensible choice for a Kenyan civic platform, note it in `docs/DECISIONS.md` with one line of reasoning, and keep going.
3. Track progress on disk. Keep `docs/PROGRESS.md` updated after every phase with what is done, what is next and anything blocked. If your context gets compacted or the session restarts, re-read `CLAUDE.md`, `docs/PLAN.md` and `docs/PROGRESS.md` and continue from where you stopped.
4. External services. I have not provided real keys yet. Put placeholders in `.env.example`, use Africa's Talking sandbox mode, and fake the Anthropic, embeddings and SMS clients in tests with Laravel's HTTP fakes. The app must run locally with fakes when keys are missing.
5. Every phase ends green. Run the Pest test suite and fix every failure before moving on. Then commit.
6. Stay in this project. Work only inside this project folder. Do not deploy anything, do not push to any remote, do not touch files outside the project, and do not read my SSH keys or cloud credentials.
7. Finish properly. The run is done only when every phase below is complete, all tests pass, and the final checks pass.

## Phases

1. Laravel setup with Sail, Postgres with pgvector, Redis, ward seeders for the pilot area, citizen phone OTP login, Fortify with two-factor for leaders
2. Submissions, the identity split, encrypted casts, rate limits, audit log with the database trigger
3. AI intake jobs with Haiku, embeddings, clustering and ranking, Horizon
4. Citizen Blade views: raise issue, my ward, rate the fix, my reports
5. Leader dashboard: overview, respond, updates, notifications, rating record, audit log view
6. Ratings and the reopen job
7. SMS flow with Africa's Talking, including SMS ratings
8. Weekly briefs with Fable through the Batch API, token logging and the spend cap
9. Hardening: security headers and CSP, Turnstile, backups, Sentry, English and Kiswahili translations, privacy notice, deployment config and docs

## Final checks before you stop

* Full Pest suite passes, with tests covering Policies, the reopen rule, ranking, rate limits and AI output validation
* `php artisan migrate:fresh --seed` runs cleanly and the app works end to end with fakes
* A search of the whole repo finds no em dashes, no en dashes used as dashes, no emojis, no `{!! !!}` on user or AI content, and none of the banned buzzwords
* `README.md` explains local setup with Sail, every `.env` value needed, and how to deploy with Forge or Render
* `docs/PROGRESS.md` ends with a short summary of what was built and anything I need to do myself (real API keys, Africa's Talking short code, Cloudflare setup, ODPC registration)
