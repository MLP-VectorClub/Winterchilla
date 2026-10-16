# Winterchilla

PHP web app (MLP Vector Club) using DeviantArt OAuth2, Redis caching, OpenSearch for the color guide.

## Read-only mode (how production runs now)

`READ_ONLY=true` in `.env` (or the environment) turns the site into a viewer: it keeps showing what the database holds and refuses everything that would change it.
- The database session is switched to read-only (`config/init/db_class.php`), so a write that slips through fails with SQLSTATE 25006 instead of changing data the other application also uses. For real protection give the site a database role that can only read: `DB_READ_USER` / `DB_READ_PASS` are used instead of `DB_USER` / `DB_PASS` when `READ_ONLY` is on (`CREATE ROLE winterchilla_ro LOGIN PASSWORD '…'; GRANT CONNECT ON DATABASE <db> TO winterchilla_ro; GRANT USAGE ON SCHEMA public TO winterchilla_ro; GRANT SELECT ON ALL TABLES IN SCHEMA public TO winterchilla_ro; GRANT USAGE ON ALL SEQUENCES IN SCHEMA public TO winterchilla_ro;`).
- `public/index.php` answers every request that is not GET / HEAD / OPTIONS with 503 (`{"message", "readOnly": true}` for the API, a notice page otherwise), and the start and return addresses of the DeviantArt and Discord sign in (`/da-auth/begin|end`, `/discord-connect/begin|end`), which write a session or an account; the sign in button and the "sign in to vote" hint are not rendered. The `/test-*` routes of the browser tests are the exception.
- Code that writes while a page is viewed has to skip it with `CoreUtils::readOnly()`: `Session::registerVisit` / `refreshAccessToken`, the personal guide slot history (`User::recalculatePCGSlotHistroy`, `syncPCGSlotCount`; `getPCGAvailablePoints` works the number out in memory), `PostAPIController::reload` (would mark posts broken), `Users::fetchDA` (would add users), the browser recognition page. A new write on a GET route needs the same guard; `tests/Browser/ReadOnly` crawls the pages as guest, user and admin and fails when one answers 5xx.
- `READ_ONLY_URL` (optional) is the address of the new site: the notice at the top of every page and the sign in refusal page link to the same page there ("To make changes, please visit the new site: <host><path>"), so no notice has to be written by hand for it.
- Visitors signed in on the new site are signed in here too (`App\LunaSession`, called from `Users::authenticate` in read-only mode only): the session cookie of Luna (`SESSION_COOKIE`, the same name as in Luna's `.env`, shared by the subdomains) is decrypted with Luna's `APP_KEY` (`LUNA_APP_KEY`), the session is read from `luna_sessions` with the read-only role (`LUNA_SESSION_LIFETIME` in minutes, 43200 like Luna's default) and its user is the one the page is rendered for. Nothing is written, no cookie is set, and the Sign out button is hidden (that happens on the new site). Without `SESSION_COOKIE` and `LUNA_APP_KEY` nobody is signed in this way. Tests: `tests/LunaSessionTest.php` (the cookie fixture was made by Luna's own encrypter) and the last test of `tests/Browser/ReadOnly/ReadOnlyTest.php`.
- Luna stores the e-mail addresses of users encrypted with its app key (`users.email`, plus the keyed lookup hash `email_hash`): `User::get_email` reads the plain address through `LunaSession::emailFromStored` (`LUNA_APP_KEY`; no key means no address, never the ciphertext; plain values the old site wrote are read as they are). Lookups by address (`find_by_email`, `User::exists(['conditions' => ['email' => ...]])`) cannot work on the table any more, they are in code that changes data, which read-only mode refuses anyway. Do not write addresses from here: Luna would not find those users by them.
- Tokens that are not real are counted (`App\InvalidTokenLimiter`, Redis, per `REMOTE_ADDR`): a Luna cookie that does not decrypt (it fails the MAC before anything is looked up) and an `access` cookie nobody has a session for. Past `INVALID_TOKEN_LIMIT` (30) in `INVALID_TOKEN_WINDOW` seconds (600), requests that carry such a cookie get 429 with `Retry-After` before any lookup; requests without one are not held up. Read-only mode hands out no guest `access` cookie (it saves no session) and drops a stale one, otherwise every guest would count. Tests: `tests/InvalidTokenLimiterTest.php` and the last read-only test.
- Test: `scripts/ui-test-readonly.sh`. The normal suites are unaffected (the read-only tests skip themselves without `READ_ONLY=true`).

## Browser tests

The browser suite (Pest + Playwright) is the regression net for this site and the spec that Celestia/Luna were checked against.

Tests live under `tests/Browser/`, written with Pest (`it(...)` blocks), driven against a real running
app via `TestSeederConstants::BASE_URL` (see `tests/Browser/Helpers/`). `test-login/[user_id]` is the
existing shortcut for authenticated flows. The OAuth begin/end endpoints themselves are driven against a
TEST_MODE-only fake DeviantArt/Discord provider.

`ServerManager` starts the test server with `TEST_MODE=true` regardless of `.env` (test-only routes, the
fake OAuth provider and the test database — so a local dev site can keep `TEST_MODE=false` and use the real
DeviantArt), `APP_URL` set to the test origin (so OAuth redirect URIs point back at it), `PHP_CLI_SERVER_WORKERS=4` (the app calls the fake OAuth provider on the same server
mid-request, which deadlocks a single worker), and `opcache.revalidate_freq=0` (a CLI opcache with the
default-ish 180s revalidation otherwise serves stale code right after an edit).

Assertion gotcha: `assertDontSee('Fatal error')` does **not** catch a PHP fatal — the test server returns
a bare 500 without those words. Assert real page content (a heading, a known string) instead. Do not reintroduce that pattern.

Route inventory source of truth: `config/routes/pages.php`.

Features that only exist on the new site get a page here that says so and links to it (`READ_ONLY_URL` + the new path), instead of a 404: add an entry to `config/new_site_only.php` (title, summary, this site's route patterns, the new path with `{param}` placeholders) and nothing else, `routes/pages.php` registers `NewSiteController::unavailable` for every pattern (`RouteHelper::processHandler` passes the entry's key as the third item of the route target). Tests: `tests/NewSiteFeaturesTest.php`, `MiscRoutesTest`. Wired in so far: the sprite color check (`/[cg]/sprite-colors/[id]`).

Running: `vendor/bin/pest tests/Browser` runs the browser suite; a bare `vendor/bin/pest` runs only the unit
tests (the browser suite is skipped unless the args mention `tests/Browser`).

### Known infra gotcha: every browser test times out at ~5s uniformly

If you ever see **every single test** in the suite (including pre-existing, untouched ones) fail with
`Timeout 5000ms exceeded` at a near-identical duration, and no screenshot gets attached to the failure
(Playwright never even reaches a renderable page) — **do not** chase OpenSearch, Redis, leftover
processes, system load, or WSL/Playwright browser-launch speed first. Those were all investigated at
length in a past session and ruled out. Instead check first: is `tests/Browser/Helpers/ServerManager`'s
PHP built-in test server actually running (`ss -ltn | grep 8765`, or just `curl 127.0.0.1:8765` from a
shell)? If nothing is listening, the browser layer is fine — it's failing because it has nothing to
connect to.

Root cause (fixed, but noted here in case of regression): Pest 4's `BootFiles` bootstrapper
(`vendor/pestphp/pest/src/Bootstrappers/BootFiles.php`) only auto-loads the single root `tests/Pest.php`
— it does **not** recursively discover nested `Pest.php` files in subdirectories. `tests/Pest.php` now
has an explicit `require_once __DIR__ . '/Browser/Pest.php'`, guarded to browser-test invocations only
(checks `$_SERVER['argv']` for `tests/Browser`), so don't remove that guard/require.

Separately — and this bit further: even once the file loads, a bare top-level `beforeAll()`/`afterAll()`
call only fires for tests defined *in that same file* (Pest 4 keys the hook by the closure's own
defining filename — see `Pest\Repositories\BeforeAllRepository::set()`/`get()`). Since `Pest.php` itself
defines no tests, those hooks silently no-op — no error, no exception, they just never run. The correct
Pest 4 way to apply a hook to every test in a directory is the fluent chain:
`uses(Trait::class)->beforeAll(fn () => ...)->afterAll(fn () => ...)->in(__DIR__);` — `tests/Browser/Pest.php`
uses this pattern now. If you ever add another nested `Pest.php`, use the same fluent form, not bare
`beforeAll()`/`afterAll()` calls.

Caveat: in that fluent chain `beforeAll` fires but `afterAll` does **not** (verified by instrumenting both
hooks). That used to orphan the `php -S` server after every run, and the next run's readiness check then
silently reused the stale server. `ServerManager` now registers a `register_shutdown_function` to stop the
server when the Pest process exits, and `start()` throws if port 8765 is already taken — if you see that
error, a server survived an aborted run; `fuser -k 8765/tcp` clears it. Don't rely on `afterAll` for cleanup.

### Fresh-machine setup for the browser suite

Beyond `composer install`, `pnpm install && pnpm build`, a `.env` (`TEST_MODE` there doesn't matter for the
browser tests — `ServerManager` forces it on) and running Postgres/Redis: the PHP `pdo_sqlite` extension must be enabled (composer platform requirement), and
Playwright's own browser build must match the installed `playwright` npm version —
`pnpm exec playwright install chromium`. A mismatch makes every test fail instantly with
`PlaywrightOutdatedException` (no requests ever reach the server).

Don't bump `playwright` past 1.60 while on `pestphp/pest-plugin-browser` 4.x (`package.json` pins `~1.59.1`).
Playwright 1.61+ rejects local file paths from clients connected over a websocket, which is how the plugin
connects, so `->attach()` fails with `localPaths are not allowed when the client is not local` (the color
picker file-upload test). It's fixed only on the plugin's 5.x line (Pest 5, needs `symfony/process` ^8.1);
that upgrade is what unblocks newer Playwright versions.

Diagnosing the timeout issue above took a long time because both failure modes are *silent* — no PHP fatal, no Pest error,
tests just report the standard 5s navigation timeout as if the app were slow. The fastest way to confirm
either bug in the future: put an unconditional `throw` at the very top of the file in question and see if
it actually aborts the run.

## API contract (`/api/v0`, frozen; Luna implements it)

**Purpose:** the `/api/v0` API was the contract that Celestia (Next.js) and Luna (Laravel + Sanctum) were built against, with the browser/contract suites as the behavioral spec. Luna keeps a frozen copy (`docs/contract/api.json`); this site only renders with Twig and is read-only.

**Artifacts to build against**
- The OpenAPI document and its annotations are gone from this repo (`/docs` redirects to Luna's documentation); Luna's `docs/contract/api.json` is the frozen copy.
- `tests/Browser/Api/*` — HTTP-level contract tests for every endpoint (status codes, body shapes, permissions, validation errors); they run
  against the test server with seeded data and double as the spec a re-implementation can be run against: `Tests\Browser\Helpers\ApiClient`
  honors `CONTRACT_BASE_URL`, `CONTRACT_API_PATH` (empty = no prefix), `CONTRACT_AUTH=bearer`, `CONTRACT_LOGIN_URL`/`CONTRACT_LOGIN_METHOD`
  (the bearer login endpoint must answer `{"token": …}`), and `scripts/dump-contract-seed.sh` dumps the seeded data (it resets the shared test DB).
  The browser suites under `tests/Browser/{Admin,User,Guest}` pin the UI behavior.
- `config/routes/public_api_v0.php` — the route table (`$api_endpoint($path, $target, $methods)`; several controllers share a path by method).

**Contract format (Luna's, verified from `Luna/app`)**
- camelCase JSON keys for requests and responses (kept snake_case on purpose: OAuth protocol parameters, the page-level `sort_by` and the
  preference keys such as `cg_itemsperpage`). Write bodies may be `application/json` or form-encoded. JSON values are turned into the form
  representation the inputs read (`Controller::readJsonBody`: booleans become `1`/`0`, lists of scalars comma lists, nested values JSON strings), so
  read on/off request fields with `CoreUtils::requestFlag()`/`truthy()`, never `isset()` or a bare `$value ? …` (`"0"`/`false` must count as off).
  The on/off preferences are booleans on the wire. An edit that omits an optional field leaves it unchanged (`guide`, `private`; `notes` is
  cleared only when sent empty).
- No `status` envelope: success is a proper 2xx with the resource as the body (201 on create, 204 for actions without a body), failure a proper
  4xx/5xx: `401` signed out, `403` forbidden, `404`, `409` state conflict, `419` CSRF, `422` validation, `429` throttled, `501` disabled feature,
  `502/503` dependency down. Error bodies are `{message}` and `{message, errors: {field: [..]}}` (one field error per response: `Input` stops at the
  first failure, deliberately — continuing would need null-safe handling in ~100 methods that write between inputs).
- Lists are paginated `{currentPage, totalPages, totalItems, itemsPerPage}`; read payloads carry the visitor's permissions (`canEdit`, `canManage`,
  `canEnter`, …) so a front end does not re-derive authorization from roles.
- Auth is described per endpoint as public / signed in / role (the spec's default `security` is `SessionCookie`, public endpoints say `security={}`);
  Luna maps that onto Sanctum (bearer tokens or stateful cookies). Sign-in, token issuing and OAuth belong to Luna; Winterchilla's own sessions,
  `test-login` and the CSRF cookie (`CSRF_TOKEN` echoed on writes) are *not* part of the contract. Public payloads show a developer under the
  `dev_role_label` setting; `/users/me` shows the raw role.
- Stay on `/api/v0` (documented as unstable); no `/api/v1`. Celestia proxies its API prefix to the backend, so there is no CORS work.

**What exists** (read endpoints Celestia's fetchers need, as data: `GET /appearances/{id}|full|pinned|autocomplete|{id}/locate|preview`, `/color-guide`,
`/color-guide/major-changes`, `/show` (+ `season`/`episode` filters) + `/show/{id}` + `/show/latest`, `/posts?showId&kind`, `/tags`, `/events` + `/events/{id}`, `/users`, `/users/{id}`,
`/users/da/{username}`, `/users/{id}/profile|contributions/{type}|personal-guide/{appearances,point-history}`, `/useful-links/sidebar`,
`/user-prefs/me`, `/notices/current`, `/about/connection|members`, `/config`, and staff-only `/admin/logs`, `/notices`; plus the write API on
Luna-style paths: appearances, color groups, tags, posts, events/entries, shows, users/sessions/preferences, personal guide, Discord, settings,
notifications, useful links). `GET /config` returns constants and validation patterns (`{source, flags}`) — the replacement for the page
globals Twig embeds with `export_vars`; **retiring `export_vars`/`datastore.js` on the Winterchilla side is deliberately not done** (it is part of
the Celestia/Luna split, which has no such globals).

**Known differences from Luna's own API** (so a re-implementation does not chase them): shows have no `generation` (dropped here), `previewData`
is the appearance's first four colors, `/about/connection` has no `deviceIdentifier`, `/useful-links/sidebar` returns `[]` for guests like Luna.
Not covered by automated tests because they need the real network: creating posts with real images, finishing with a deviation, approval success,
event entry submission (disabled in the app anyway), a successful Discord sync, the e-mail flow past validation.

**Guards in CI** (`.github/workflows/ci.yml`): PHPStan, ESLint, unit and browser suites. **Run both `vendor/bin/pest` (unit) and `vendor/bin/pest tests/Browser` locally**
before pushing; CI's "Browser Tests" job also runs the unit tests.

**UI tests against another implementation** (the browser UI tests also run on Celestia backed by Luna). Run `scripts/ui-test-celestia.sh`: it reloads Luna's contract
seed (`UI_RESET=0` skips that), then runs `tests/Browser/{Admin,User,Guest}` with `UI_BASE_URL=http://localhost:3000` (Celestia, started with `E2E_TEST_LOGIN=1`; it must be on `localhost:3000` because Luna's Sanctum only
accepts session cookies from its `FRONTEND_URL`) and `CONTRACT_BASE_URL=http://127.0.0.1:8766` (Luna's `scripts/serve-contract.sh`, which the tests' own data setup talks to with a bearer token), excluding
`winterchilla-only`. The tests need the data of `scripts/dump-contract-seed.sh` (`build/contract-seed.sql`), a GET sign-in route matching `UI_LOGIN_PATH` (default `/test-login/{id}`; Celestia serves a page that calls
Luna's `GET /test/session-login/{id}`) and, for deviations and the club gallery, Luna's `/test/deviations/{id}` and `/test/club-gallery/{id}` (`tests/Browser/Helpers/Fixtures.php`). No Winterchilla server or DB reset happens
when `UI_BASE_URL`/`CONTRACT_BASE_URL` is set. Groups: `winterchilla-only` = cannot apply elsewhere (the API tests that pin Winterchilla's own response shape, like `li`/`url`/`html` fields or `/users/signout`, carry it too and are what Luna's run skips) (the dialog test page, the fake OAuth flows, `/components`, `/docs`, diagnose pages, and flows that drive Winterchilla's own DOM,
which `celestia-only` variants re-implement against Celestia's dialogs, skipped on Winterchilla). Celestia adopted Winterchilla's ids and `data-testid`s. Assertions use wording both sites can produce (e.g. 'already exists', 'slots'); check state through the API when dialogs differ.
Failed runs leave data behind, hence the seed reload; test cleanup must use page sizes Luna accepts (max 50).

**Code coverage** (`scripts/coverage.sh`, CI job "Code Coverage"): two reports, from the unit tests plus the browser/contract tests (`--unit-only` skips the browser run).
- *PHP (`app/`)* → `build/coverage/html/index.html`, `clover.xml`, text summary. Server side: with `COVERAGE_DIR` set `ServerManager` starts `php -S` with
  `tests/Browser/Helpers/coverage_prepend.php`, which records each request's lines per worker; `scripts/coverage-report.php` merges them with `build/coverage/unit.cov`.
  Needs PCOV; there is no distro package for PHP 8.5 here, so build it (`git clone https://github.com/krakjoe/pcov && phpize && ./configure --enable-pcov && make`)
  and put `pcov.so` at `~/.local/lib/php/pcov.so` or set `PCOV_SO`. Excluded (phpunit.xml `<source>`): `DeviantArt.php` (real network) and the test infrastructure
  (`app/Testing`, `Test*Controller`). First full run: 69.1% of lines (6990/10110); weakest `Logs.php`, `CGUtils.php`, `CoreUtils.php`, `Appearances.php`, `Users.php`.
- *Page scripts (`assets/js`, not `lib/`)* → `build/coverage/js-html/index.html`, `js-lcov.info`. `COVERAGE=1 pnpm build` instruments every script with istanbul
  (`build.mjs`) and prepends `assets/coverage-reporter.js`, which posts the hit counts (on a 1 s timer and on pagehide) to the TEST_MODE-only `/test-coverage/js`
  (`TestCoverageController`, writes `COVERAGE_JS_DIR/<page load>.json`); `scripts/coverage-js-report.mjs` sums them over `build/coverage/js-initial.json`, so scripts no test
  page loads count as 0%. `coverage.sh` rebuilds `public/js` normally when it ends. First full run: 21.0% of statements (1279/6085) over 76 page loads.

**Lessons worth keeping**
- Real bugs the contract work uncovered (all fixed): post edit authorization never denied anyone, the admin API had no staff check, "sign out
  everywhere" posted to a route that did not exist, `fixPath()` dropped array query parameters, `/appearances/{id}/preview` was documented but
  unrouted, creating a color group saved it before validating its colors, and several 500s on edge input. Weak assertions (`assertDontSee('Fatal error')`)
  hid some of them — assert real content.
- Client side: `$.API.get/post/put/delete(url, data)` return the jqXHR; use `.done(resp => …)` and `.fail($.API.fail(title))` or
  `.fail($.API.failWith(body => …))` (`body.message` is HTML-escaped for dialogs, `body.rawMessage` is plain text, 422 `errors` are joined into
  the message). Requests made this way are marked `apiHandled` so the global status dialogs stay quiet.
- Test seams that avoid the network: the fake OAuth provider (`/test-oauth/...`, TEST_MODE only), Redis-seeded deviations with local image
  URLs, club-gallery marker files (`ClubGallery`), seeded users (`TestSeederConstants`), and `reset-test-db.sh`. Seeds with explicit IDs must
  advance their sequences (end of `TestSeeder`). `Pest`'s `toHaveKey($key, $value)` takes a *value* as its second argument, not a message.
- Other Claude sessions may edit the same tree; production deploys (`git push deploy main` or the GitHub Actions "Deploy" button) need the
  user's explicit go-ahead. `origin` (GitHub) push is not a deploy.
