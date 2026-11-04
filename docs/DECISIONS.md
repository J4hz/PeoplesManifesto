# Decisions

One line of reasoning for each choice made without asking.

## Platform

* Pilot area is Ruiru constituency (sub-county) in Kiambu County, with its eight wards. It is a busy peri-urban area with the water, roads and security issues the platform is built for. Change `PilotAreaSeeder` to move the pilot.
* Areas inside each ward are seeded as numbered placeholder zones marked TODO. Real village and estate names must come from the ward administrator, not from guesswork.
* Anthropic calls go through a small service class over Laravel's HTTP client instead of the official PHP SDK. The SDK uses its own transport, so Laravel's `Http::fake()` cannot intercept it, and the brief requires HTTP fakes in tests. The request shapes follow the Messages and Batches API docs.
* Africa's Talking is called through Laravel's HTTP client for the same reason.
* When a key is missing, the container binds local stand-ins: a keyword based intake analyser, a hashing embedder, a log SMS gateway and a template brief writer. The app runs end to end with no keys.
* Plain CSS with custom properties instead of Tailwind. Smaller download on slow networks and the palette is defined once.
* Fonts are self-hosted through Fontsource packages instead of Google Fonts. No third-party request on page load and a tighter CSP.
* Alpine.js uses its CSP build so the Content Security Policy does not need `unsafe-eval`.
* Icons are Lucide paths (ISC licence) inlined through one Blade component at stroke width 2.

## Identity and privacy

* The identity table stores an HMAC of the citizen id for lookups and the citizen id itself under the `encrypted` cast. A leaked database dump without the app keys cannot link a grievance to a person.
* Phone lookups use HMAC-SHA256 with a separate `IDENTITY_HASH_KEY`, so rotating `APP_KEY` does not break lookups and the hash is useless without the key.
* Ratings store the same citizen HMAC, not the citizen id. Aggregates need only a stable reference.
* The respond page shows sample submissions only when intake did not flag personal details (names, phone numbers, ID numbers). This is automatic, not a human hiding content.
* Admin moderation covers accounts (wards, offices, leaders, staff), the AI intake queue (retry unprocessed submissions) and spend. Admins have no route or permission that edits, hides or deletes a submission or rating.
* A citizen can delete their account. That removes the phone number and empties the encrypted citizen id in the identity table. The keyed reference stays so counts do not change, and with the phone number gone it no longer points to anyone. This matches the Data Protection Act right to erasure without letting anyone erase community history.

## Issues and ranking

* A "+" (add your voice) creates a submission of kind `voice` linked straight to the cluster. It counts as a distinct resident and skips the AI.
* The citizen's chosen category and severity win over the model's guesses. The model fills what the citizen left blank. People know their own situation, and it limits what a prompt injection can change.
* The model may name a different pilot ward if the text clearly says so. The answer must match the supplied list, otherwise the citizen's ward stands.
* Clusters are matched within the same ward and category, by cosine similarity on the English translation's embedding, threshold 0.82 (in `config/civic.php`). The local hashing embedder uses 0.3 because its scores run much lower.
* A trend is called "falling" only when at least two residents raised it in the previous week. One quiet resident is not a trend.
* Template briefs list only issues marked done as improving. An issue that went quiet may simply be ignored.
* Cluster titles and summaries are rewritten by Haiku when an issue reaches 3, 10, 25, 50, 100 and 250 reports. Reports flagged for personal details are left out of that text. These rewrites pause with the briefs at the spend cap.
* Categories route to an office type through `config/civic.php` (for example water and roads to the MCA, schools and security to the MP). Any leader whose jurisdiction covers the ward can respond. Only leaders, never staff, can set Done.
* Each time a cluster is marked Done its `done_round` goes up, and ratings are per round, so a reopened and re-fixed issue gets a fresh vote.
* When the 7 day window ends with nobody rating, the cluster stays Done and the window closes.
* "Recent growth" compares distinct residents in the last 7 days with the 7 days before.

## AI cost

* At the monthly cap, weekly briefs pause until the next month. Intake keeps running because each call costs a fraction of a cent, until spend passes the hard ceiling (cap times 1.2). Past that, intake jobs are released back to the queue every hour and wait for the new month.
* Intake runs Haiku at `low` effort with a structured JSON schema and still validates the output with a strict Laravel validator before saving.
* Fable briefs go through the Batches API, which costs half the standard rate. The server-side fallback parameter is not sent because the Batches API rejects it.
* Prices live in `config/ai.php` and should be checked against Anthropic's pricing page before launch.

## SMS

* Africa's Talking does not sign callbacks. The callback URL carries a long random token compared in constant time, and an optional IP allowlist can be switched on. Both are in `config/services.php`.
* SMS ratings ask only for the verdict (1 Yes, 2 Partly, 3 No). Stars are left empty for SMS ratings and averages ignore them.
* SMS conversations default to English with a `SW` keyword to switch to Kiswahili.

## Interface

* The leader and admin dashboards are in English, the working language of county and national government offices. Everything residents see, including SMS, errors and the privacy notice, is in English and Kiswahili.
* Category chips and severity have no default. A resident who skips them lets intake decide, which is better than a preset everyone leaves alone.
* Photo routes are split by area (resident or dashboard), so a leader who is also a resident is always checked under the right account.

## Hosting

* A demo mode (`DEMO_MODE=true` with `APP_ENV=demo`) shows sign-in codes on screen and puts a banner on every page, so people can try the resident side without an SMS account. The app refuses to start with it on in production.

* Laravel Cloud is the chosen host. Photos and backups go to private Cloud buckets because the Cloud file system does not last between deploys. The scheduler and Horizon run on a worker cluster that never sleeps, so the reopen check and weekly briefs run on time. Forge and Render instructions stay in the README as fallbacks.

* Photos use the `local` disk on a single VPS and a private S3 bucket on Render, where web and worker containers do not share a disk. Either way they are streamed through a policy check, never linked directly.
* Render services are placed in Frankfurt, the closest Render region to Kenya.
* The Docker image is based on serversideup/php, which runs nginx and PHP-FPM together and caches config and routes on start. Migrations run once per deploy, not per container.
* Backups cover the database and the private photo folder, not the code, which lives in git.

## Security

* Production session cookies are Secure, HttpOnly, SameSite Lax and encrypted. Lax rather than Strict so links from SMS messages keep the session.
* Staff passwords need 12 characters, and in production are checked against known breached passwords.
* Without an SMS key the log gateway writes messages to the log. It refuses to run in production, so sign-in codes can never end up in production logs.
* The SMS job is encrypted on the queue because its payload carries a phone number.
* Horizon is gated to admins in every environment, local included, because job payloads can carry report text.
* Turnstile is skipped only when no secret is configured and the app is not in production. In production a missing secret fails closed.
* Photos are re-encoded with GD, which drops all EXIF data including GPS, after applying the EXIF rotation. Stored on the private disk and served through an authorised route.
