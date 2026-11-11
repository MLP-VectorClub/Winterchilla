# Running Winterchilla in the cluster (read-only legacy site)

Winterchilla runs read-only (`READ_ONLY=true`, see CLAUDE.md "Read-only mode") on Luna's database, served as `www.`/`old.mlpvector.club`. Its `/dist` assets are also used by Celestia.

## Images (`.github/workflows/images.yml`, every push to `main`; tags `sha-<sha7>` only)
- `ghcr.io/mlp-vectorclub/winterchilla-fpm`: PHP 8.5 php-fpm on `127.0.0.1:9000`, non-root `www-data`, logs to stderr. Also used for the CronJobs.
- `ghcr.io/mlp-vectorclub/winterchilla-web`: nginx (unprivileged) on **8080**: static files, fastcgi to the fpm container of the same pod. Config baked in at `/etc/nginx/conf.d/default.conf` (`docker/nginx.conf`), a ConfigMap may override it.
- Web pod = both containers (same pod, `127.0.0.1:9000`).

## Health
`GET /health` (nginx → `public/health.php`): 200 when PostgreSQL and Valkey answer, 503 otherwise.

## Read-only behaviour
nginx answers every method except GET/HEAD/OPTIONS with a JSON 405, `/js/min` and `/scss/min` with 410. With `READ_ONLY=true` the app itself also refuses writes, the DeviantArt/Discord sign-in (`/da-auth/*`, `/discord-connect/*` answer a read-only notice, not an OAuth redirect), skips every write on GET routes, uses the read-only role (`DB_READ_USER`/`DB_READ_PASS`) and signs in visitors from Luna's session cookie.

## Environment variable names (nothing in git)
- In the image: `LOG_PATH=php://stderr`, `PRODUCTION=true`, `NPM_BUILD_LOCK_FILE_PATH`. There is no `.env` file; the real environment is used.
- Web pod: `READ_ONLY=true`, `READ_ONLY_URL`, `APP_URL`, `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_READ_USER`, `DB_READ_PASS`, `REDIS_HOST`, `REDIS_PORT`, `REDIS_USERNAME`, `REDIS_PASSWORD`, `REDIS_PREFIX`, `ELASTIC_HOST`, `SESSION_COOKIE`, `LUNA_APP_KEY`, `LUNA_SESSION_LIFETIME`, `INVALID_TOKEN_LIMIT`, `INVALID_TOKEN_WINDOW`, `DA_CLIENT`, `DA_SECRET`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_FROM`, optional `MAIL_USERNAME`, `MAIL_PASSWORD`, `OEMBED_PROXY_URL`, `DA_SCRAPE_PROXY_URL`, `DISCORD_*`, `WS_SERVER_*`, `CSP_*`, `MAINTENANCE_START`. Never `TEST_MODE`.
- CronJobs: the same minus `READ_ONLY`/`DB_READ_*`/`SESSION_COOKIE`/`LUNA_APP_KEY`: they connect as `DB_USER`/`DB_PASS` and write.

## Volumes
- `fs/` (`/var/www/html/fs`, ~12 MB): sprites, renders, caches (also `members.json`). PVC read-write on the fpm container, seeded from the old server.
- `/data/export`: the colour guide export written by the export CronJob (and on first visit of the guide); `public/dist/mlpvc-colorguide.json` is a symlink to it in both images. Mount the same volume in the fpm, web and export CronJob containers.

## CronJobs (fpm image, `concurrencyPolicy: Forbid`)
| Schedule | Command | Writes |
|---|---|---|
| `10 0 * * *` | `php scripts/clear_old_logged_ips.php` | `UPDATE logs SET ip` (rows older than 3 months); `DELETE FROM failed_auth_attempts` (older than 3 months) |
| `20 0 * * *` | `php scripts/clear_old_email_confirmations.php` | `DELETE FROM email_verifications` (older than 24 h) |
| `0 */12 * * *` | `php scripts/export_color_guide.php` | none in the database (writes `/data/export/mlpvc-colorguide.json`) |

## Migrations
None. Luna owns the schema; never run phinx or the seeders here.

## One instance only?
No. Replicas are fine; no bots or schedulers.

## External services
PostgreSQL (`luna`), Valkey (`REDIS_PREFIX=winterchilla:`), OpenSearch (`ELASTIC_HOST`), SMTP, DeviantArt.

### DeviantArt calls
- `backend.deviantart.com/oembed`: `OEMBED_PROXY_URL`.
- `www.deviantart.com/global/difi/`, `/mlp-vectorclub/modals/memberlist/`, sta.sh / fav.me pages (`HTTP::legitimateRequest`, `HTTP::findRedirectTarget`): `DA_SCRAPE_PROXY_URL` (http:// or socks5h://), applied automatically to deviantart.com, sta.sh and fav.me hosts.
- `www.deviantart.com/api/v1/oauth2/*` and the OAuth token endpoints: direct. In read-only mode sign-in is disabled, so only already-stored tokens are used.
