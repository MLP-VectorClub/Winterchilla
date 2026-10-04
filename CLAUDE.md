# Winterchilla

PHP web app (MLP Vector Club) using DeviantArt OAuth2, Redis caching, ElasticSearch for the color guide.

## UI test coverage plan (Celestia/Luna migration prep)

**Goal:** 100% browser (e2e) test coverage of user-facing routes/functionality in this repo, built up
incrementally, so the resulting suite becomes the confidence net for re-implementing this functionality
in the Celestia/Luna projects. This plan lives here (not just in a chat session) specifically so
progress survives across sessions and machines — update the checkboxes and status lines as work lands,
and commit this file alongside the tests it describes.

Tests live under `tests/Browser/`, written with Pest (`it(...)` blocks), driven against a real running
app via `TestSeederConstants::BASE_URL` (see `tests/Browser/Helpers/`). `test-login/[user_id]` is the
existing shortcut for authenticated flows. The OAuth begin/end endpoints themselves are driven against a
TEST_MODE-only fake DeviantArt/Discord provider (see Stage 6).

`ServerManager` starts the test server with `TEST_MODE=true` regardless of `.env` (test-only routes, the
fake OAuth provider and the test database — so a local dev site can keep `TEST_MODE=false` and use the real
DeviantArt), `APP_URL` set to the test origin (so OAuth redirect URIs point back at it), `PHP_CLI_SERVER_WORKERS=4` (the app calls the fake OAuth provider on the same server
mid-request, which deadlocks a single worker), and `opcache.revalidate_freq=0` (a CLI opcache with the
default-ish 180s revalidation otherwise serves stale code right after an edit).

Assertion gotcha: `assertDontSee('Fatal error')` does **not** catch a PHP fatal — the test server returns
a bare 500 without those words. Assert real page content (a heading, a known string) instead. The weak pattern has been
replaced everywhere (Stage 7); don't reintroduce it.

Route inventory source of truth: `config/routes/pages.php`.

Running: `vendor/bin/pest tests/Browser` runs the browser suite; a bare `vendor/bin/pest` runs only the unit
tests (the browser suite is skipped unless the args mention `tests/Browser`).

### Known infra gotcha: every browser test times out at ~5s uniformly

If you ever see **every single test** in the suite (including pre-existing, untouched ones) fail with
`Timeout 5000ms exceeded` at a near-identical duration, and no screenshot gets attached to the failure
(Playwright never even reaches a renderable page) — **do not** chase ElasticSearch, Redis, leftover
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

### Stage 0 — Baseline (done)

Already covered, no action needed unless a regression is found:
- Dialog component — `tests/Browser/Dialog/DialogTest.php` (20 tests, all dialog types/states)
- Auth via test-login — `tests/Browser/User/AuthTest.php`
- Admin panel (index, logs+filter, useful links, notices, pcg-appearances, 403-to-guest) — `tests/Browser/Admin/AdminTest.php`
- Appearance CRUD + color group CRUD (admin) — `tests/Browser/Admin/AppearanceManagementTest.php`
- Color guide index/guide/full-list/change-list, picker (file open + clipboard paste) — `tests/Browser/User/ColorGuideTest.php`
- Episodes (list, detail, `/episode/latest` redirect) — `tests/Browser/User/EpisodeTest.php`
- Events (list, detail guest/logged-in) — `tests/Browser/User/EventTest.php`
- User profile (regular/admin view, own account settings, 403-to-guest) — `tests/Browser/User/UserProfileTest.php`
- Guest smoke pages (homepage redirect, cg index, tags, blending, about, privacy, 404) — `tests/Browser/Guest/PublicPagesTest.php`

Resolved: `tests/Browser/User/PostsTest.php` duplicated `EpisodeTest.php`; its one unique case (episode
page as a logged-in user) was folded into `EpisodeTest.php` and the file deleted during Stage 4.

Resolved: `AppearanceManagementTest > full color group lifecycle` used to time out clicking
`[data-testid="edit-colorgroup-btn"]`. It wasn't a flake or a selector problem — it was a real app bug:
`ColorGroupAPIController` never called `_initAppearancePageState()` (lost in the API controllers refactor),
so the `APPEARANCE_PAGE` flag was ignored and creating/editing a group on the appearance page swapped in
compact HTML with no Edit/Delete buttons. Fixed in the controller; the test passes unchanged.

### Stage 1 — PersonalGuideController (done)

Routes (`config/routes/pages.php` lines ~112-116):
- [x] `/users/[id]/[cg]/[guide]?/[v]` and `/users/[id]/[cg]/[i]?` — personal guide list
- [x] `/users/[id]/[cg]/slot-history/[i]?` — slot history (redirects to point-history/[i]?, same handler)
- [x] `/users/[id]/[cg]/point-history/[i]?` — point history

New file: `tests/Browser/User/PersonalGuideTest.php`. Covers guest/owner/staff access levels for both
the list and point-history pages (owner-or-staff guard on point-history, open-by-default on list per
`User::canVisitorSeePCG()`).

`TestSeeder` also seeds the regular user's personal guide (a public appearance with a color group and a private one
with a fixed `?token=`, plus a manual point grant from the admin) and two major changes on the official appearance,
so the personal guide list/appearance/point-history pages and the changes list have real content
(`TestSeederConstants::PERSONAL_*`/`PRIVATE_PERSONAL_*`). Note the guest behavior they pin down: a private personal
appearance is *listed* to guests as a locked entry, but its page is a 403 without the token. The seeder resets the
identity sequences of the tables it seeds with explicit IDs at the end — otherwise the first row the app creates
collides (that bit color groups).

### Stage 2 — UserController remaining (done)

- [x] `/users` — user browse/list page (guest "Club Members" vs staff "Users")
- [x] `/u/[uuid]` — profile by UUID (developer-only; test asserts guests and regular users get 404)
- [x] `/users/[id]/contrib/[type]/[i]?` — contribution tabs (cms-provided, unknown type 404, requests
      owner/staff-only)
- [ ] `/user/contrib/lazyload/[favme]` — not covered: on a cache miss it calls the real DeviantArt oEmbed
      API, so a test would depend on the network. Needs a `CachedDeviation` seed row (or a fake oEmbed
      endpoint like the OAuth one) first
- [x] `/users/verify` — verify/block page renders (the JS-driven API call itself isn't exercised)
- [x] Legacy `@[username]` redirect — one representative test (`/@name` and `/@name/contrib/...`); the
      unknown-name path calls DeviantArt (`Users::fetchDA`) so it isn't covered

Extended `tests/Browser/User/UserProfileTest.php`.

### Stage 3 — ColorGuide/Appearance remaining (done)

- [x] `/[cg]/blending-reverse` — reverse blending tool (forward already covered)
- [x] `/[cg]/preferred` — preferred-guide redirect
- [x] `/[cg]/[guide]/tag-changes/[id][adi]?` — tag-changes page (`AppearanceController::tagChanges` is an
      unfinished stub that unconditionally 404s — test asserts that current behavior; revisit once the
      feature is implemented)
- [x] `/[cg]/cutiemark/[id].svg` — cutiemark SVG render (404 + success path)
- [x] `/[cg]/cutiemark/download/[id][adi]?` — cutiemark download (404, rendered download, guest `?source`)

`TestSeeder` seeds cutie mark `TestSeederConstants::CUTIEMARK_ID` (900001 — deliberately high because
`fs/` is shared with the dev environment) and copies `tests/Browser/fixtures/cutiemark.svg` to
`fs/cm_source/`. Not yet covered: staff-only `?source` download of the original upload.

Extended `tests/Browser/User/ColorGuideTest.php`.

Found and fixed along the way: `CoreUtils::loadPage()` omitted the `ws_server_host` Twig variable
whenever `TEST_MODE` was on, but `layout/_scripts.html.twig` referenced it unconditionally under
`strict_variables` — so every page with `default_js` fataled (HTTP 500) in test mode. Fixed by always
setting the variable (`null` in test mode) instead of omitting the key.

Also found: `/cg` (the guide index) 500'd on a fresh checkout because `ColorGuideController::index()` read
`public/dist/mlpvc-colorguide.json` without generating it first (the per-guide page did); fixed the same way.

Also fixed: `CoreUtils::minifySvgData()` passed svgo 1.x `--disable`/`--enable` CLI flags, which svgo 2.x
rejects, so SVG minification silently no-opped. Plugin selection now lives in `svgo.config.js` (passed via
`--config`), and a non-zero svgo exit throws instead of being ignored. The cutiemark render test asserts
the minified output (width/height dropped, viewBox kept) so this can't silently regress again.

### Stage 4 — ShowController movies/generic (done)

- [x] `/movies` — not a separate list: shares `ShowController::index` and redirects to `/show`
- [x] `/show` — episode and movie tables, staff-only admin controls (add/edit/delete) vs guest
- [x] `/[st]/[id][adi]?` — movie page at its canonical `/movie/[id]-[title]` URL, wrong-type
      canonicalization (`/special/[id]` → `/movie/...`), 404 for a missing ID

`TestSeeder` now also seeds a movie (`TestSeederConstants::MOVIE_ID`). Extended
`tests/Browser/User/EpisodeTest.php` (kept the name; it covers the whole show side now).

### Stage 5 — Misc smoke coverage (done)

`tests/Browser/Guest/MiscRoutesTest.php`:
- [x] `/eqg/[id]` and `/eqg/[adi]` (EQGController) — redirect-only routes
- [x] `/s/[thing]/[id]` (PostController) — share-link redirect; base36 ID resolves to the seeded post
      (`TestSeederConstants::POST_ID`), unknown/malformed IDs and types 404. The legacy
      `/s/req|res/[id]` success path isn't covered (no `legacy_post_mappings` seed row)
- [x] `/about/browser/[session]?` (AboutController) — page loads; session variant 403s for non-developers
- [x] `/components` (ComponentsController) — loads (noindex page, still reachable)
- [x] `/docs` (DocsController) — loads
- [x] `/muffin-rating` (MuffinRatingController) — returns `image/svg+xml` with the requested width

### Stage 6 — OAuth edges (done)

- [x] `/da-auth/begin`, `/da-auth`, `/da-auth/end` (AuthController) — `tests/Browser/User/DeviantArtAuthTest.php`:
      new-user sign-in, existing user by DA ID, `?return=` redirect, username change renames the local
      user, deny, state mismatch, missing code/state, failed token exchange, a stale return URL from an
      abandoned sign-in not hijacking a later popup sign-in, failed-attempt lockout
- [x] `/discord-connect/begin`, `/discord-connect/end` (DiscordAuthController) —
      `tests/Browser/User/DiscordAuthTest.php`: guest 403, deny, state mismatch, linking with/without
      server membership, already-linked short-circuit

Approach: a fake OAuth provider served by the app itself, only in TEST_MODE
(`App\Controllers\TestOAuthController`, routes under `/test-oauth/...` mirroring the real providers'
paths). In TEST_MODE the DA client (`App\Testing\TestDeviantArtProvider`), the Discord client
(`host`/`apiDomain` options) and the restcord bot client (`apiUrl`) point there, so the app's real
redirect/state/token-exchange/profile code runs unchanged. Its consent page picks the identity to sign in
as (and, for Discord, server membership) and offers Approve / Deny / Approve-with-invalid-code.
Tokens match DeviantArt's real lengths (the DB columns are `varchar(50)`/`varchar(40)`), so they're opaque
and stored under `fs/tmp/test-oauth/` (cleared by `scripts/reset-test-db.sh`); see `App\Testing\FakeOAuth`.
Seeded users have fixed DA IDs (`TestSeederConstants::USER_DA_ID`/`ADMIN_DA_ID`).
Like DeviantArt for newly registered apps, the fake DA provider requires PKCE (S256 `code_challenge`) and
verifies the `code_verifier` at the token exchange, so the sign-in tests cover the app's PKCE round-trip
(`pkceMethod` option of `seinopsys/oauth2-deviantart` ^1.2; verifier kept in the session by AuthController).

Not covered by Pest: the sign-in *popup* flow (`$.openAuthPopup` in `global.jsx`) — Pest's browser plugin
can't drive popups, so the tests use the site's full-page redirect fallback (`/da-auth/begin?return=...`).
It was verified manually with a standalone Playwright script instead. Background: DeviantArt's sign-in
pages send `Cross-Origin-Opener-Policy`, which cuts the popup off from its opener — `popup.closed` then
reads true while it's still open and `window.opener` is null in it. That used to make the main page
redirect to DeviantArt too, and the popup never reported back. The popup's result pages (`login_confirm`,
`pages/error/auth.js`) now report over a `BroadcastChannel` and close once acknowledged; the fake consent
page sends the same COOP header so the local setup reproduces DA's behavior. Token refresh isn't exercised
either (fake tokens outlive a test run).

Found and fixed along the way: the account settings page (`/users/[id]/account`) fataled for every
signed-in user — commit a9954636 removed the `DA_AUTHORIZED_APPS_URL` constant but left the controller
passing it to the template (which no longer used it). `UserProfileTest` missed it because of the weak
`assertDontSee('Fatal error')` pattern; it now asserts real page content.

### Stage 7 — Closeout audit (done; the one open box is superseded by the API contract tests)

- [x] Diffed covered routes against `config/routes/pages.php`; the gaps found are covered by
      `tests/Browser/Guest/RemainingRoutesTest.php` (manifest, `/browser`, `/blending`, picker frame,
      paginated episodes/movies/events/logs lists, wsdiag + `/diagnose/*` 403s, appearance exports
      json/gpl/png/facing svg). Known remaining gaps: personal-guide appearance pages
      (`/users/[id]/[cg]/.../v/[id]` — needs a seeded personal appearance), sprite exports, the
      `[sett]` profile shortcut, and the developer-only pages (wsdiag, diagnose) since no developer is
      seeded
- [x] Remove/merge `tests/Browser/User/PostsTest.php` duplication (done in Stage 4)
- [ ] Decide whether `public_api_v0.php` endpoints need direct coverage beyond what's exercised
      incidentally through page-level UI flows
- [x] Replaced every `assertDontSee('Fatal error')` with a real page heading. This surfaced one real miss:
      the guest blending test visited `/cg/pony/blending`, which is a 404 (route is `/cg/blending`) and
      passed anyway
- [x] Removed the dead `/admin/discord` route (always 500'd): its page — a manual Discord member ↔ DA user
      binding tool — was deleted in 2018 (b713ef1f) when Discord linking moved to OAuth, but the route
      survived. `AdminTest`'s "discord page" test only passed because of the weak assertion above

## API contract for the Celestia/Luna reimplementation (built; production runs `368625c4`)

**Purpose:** the `/api/v0` API is the deliverable Celestia (Next.js SSR front end) and Luna (Laravel + Sanctum back end) build against,
alongside the browser/contract suites as the behavioral spec. Winterchilla keeps rendering with Twig until it is retired — no SSR,
hydration state or serializer layer for Twig lives here. Nothing in this section is left to do in Winterchilla.

**Artifacts to build against**
- `public/dist/api.json` — the OpenAPI 3 document (written by `scripts/generate_api_schema.php`, also served by `/docs`), 149 method+path
  operations (19 marked `x-internal`, see below) with Luna-style operation IDs (`GET /appearances/{id}/color-groups` → `GetAppearancesIdColorGroups`). Celestia's `packages/api-types` generator
  runs on it unchanged. Docblocks live next to each controller (`app/Controllers/API/*`, `DiscordAuthController`); the shared schemas, tags and
  security scheme live on `ApiSchemas` (not on `APIController`: swagger-php merges a parent class's schemas into every subclass that declares one);
  rendered-HTML response fields (`li`, `html`, `cgs`, `section`, `render`, `list`, `suggestion`, `entryHtml`, …) are marked there as
  *Winterchilla UI details, not part of the contract*.
- `tests/Browser/Api/*` — HTTP-level contract tests for every endpoint (status codes, body shapes, permissions, validation errors); they run
  against the test server with seeded data and double as the spec a re-implementation can be run against: `Tests\Browser\Helpers\ApiClient`
  honors `CONTRACT_BASE_URL`, `CONTRACT_API_PATH` (empty = no prefix), `CONTRACT_AUTH=bearer`, `CONTRACT_LOGIN_URL`/`CONTRACT_LOGIN_METHOD`
  (the bearer login endpoint must answer `{"token": …}`), and `scripts/dump-contract-seed.sh` dumps the seeded data (it resets the shared test DB).
  The browser suites under `tests/Browser/{Admin,User,Guest}` pin the UI behavior.
- `docs/api-path-alignment.md` — how the old Winterchilla paths map to Luna's resource style, the deviations ("As built") and the handoff notes
  for the Celestia/Luna plans (what is `x-internal`, what is deliberately not provided).
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

**`x-internal` operations** (`CoreUtils::INTERNAL_OPERATIONS`, enforced by `ApiSchemaTest`): the HTML-only endpoints, Winterchilla's own
session handling, `DELETE /admin/stat-cache`, the staff-only e-mail/password flows under testing and the staff `GET /tags/autocomplete`. A
re-implementation does not need them. Response fields that carry rendered HTML are described as "Winterchilla UI detail, not part of the contract".

**Known differences from Luna's own API** (so a re-implementation does not chase them): shows have no `generation` (dropped here), `previewData`
is the appearance's first four colors, `/about/connection` has no `deviceIdentifier`, `/useful-links/sidebar` returns `[]` for guests like Luna.
Not covered by automated tests because they need the real network: creating posts with real images, finishing with a deviation, approval success,
event entry submission (disabled in the app anyway), a successful Discord sync, the e-mail flow past validation.

**Open items (what is left)**
- **Deployed:** production runs `368625c4` (2026-10-02, deployed by the user; migration `20261001010000_clear_invalid_default_guide_prefs` ran). Public
  smoke checks pass; a signed-in smoke test is still worth doing for the behavior changes: JSON-bodies/false handling, `postAs` (was `post_as`),
  appearance edits keeping `guide`/`notes`/`private` when omitted.
- **Luna and Celestia are building from this contract** in their own repos/sessions (`Luna/docs/winterchilla-contract-plan.md`,
  `Celestia/docs/winterchilla-parity-plan.md`). They report spec/runtime mismatches back; fixes land here, and the seeded API for them is
  `scripts/serve-seeded-api.sh [port] [database]` (own port + database, never port 8765 / `winterchilla_test`). Luna's progress was 91 of 127
  contract operations at the last report. Open questions from Luna: which image providers the post endpoints must cover (answer: DeviantArt
  `fav.me`/`sta.sh`, Imgur, Derpibooru, Lightshot/prntscr via `ImageProvider`; hosts outside that list are rejected with 422 `imageUrl`) and
  which post operations Celestia actually calls (not yet answered by Celestia).
- **Not done on purpose:** `Input` reports one validation error at a time; Winterchilla's own page scripts still read the `export_vars`
  globals; no data endpoint for the browser-recognition page; a successful Discord sync and the network-dependent post flows have no automated test (they need the real
  providers).
- **Prod cleanup done:** the user's smoke-test appearance 680 (cutie mark 254) was deleted through the site, and its files went with it (verified 2026-10-02).
  The six gap-page endpoints (palette, image, cutie mark download, tag changes, staff PCG list, `users/da-uuid`) and `/events/{id}/entries` being POST-only shipped in `9f47f1d7`; test-created
  appearances start at ID 900101 so they never pick up the dev site's stale sprite files in the shared `fs/`; the seed has a developer (user 9007).
- **Palette PNG sprite fix (deployed in `368625c4`):** `renderAppearancePNG` looked for the sprite at `<id>.png<id>.png`, so it was never drawn next to the colors. Fixed; the cached
  palette PNGs of the 126 appearances with a sprite were deleted on prod on 2026-10-02 so they regenerate with the sprite (checked on appearance 10).
- **Cutie marks:** at most 2 per appearance (was an unexplained cap of 4 from 2017; prod has at most 2). The test run removes the files the seeder writes for
  cutie marks 900001/900002 when it ends, so they don't show up as orphans in a prod-copy file migration.
- **Tests that assert Winterchilla UI details** were loosened where Luna could not satisfy them (appearance create `goto`/`message`, color
  group `cgs`, show `url`/`upcoming`/`newhtml`/`html`/`section`, log `details`, default-sprite fallback). A few tests still cover `x-internal`
  operations (tag autocomplete, `GET /notifications`, `stat-cache`, sessions, password/e-mail, avatar-wrap, lazyload/reload/suggestion,
  `about/upcoming`, `cg/full`, `show/{id}/posts`, contributions cache, preview); another implementation should skip them.

**Guards in CI** (`.github/workflows/ci.yml`): `tests/ApiSchemaTest.php` (no swagger-php warnings, unique readable operation IDs, no dangling
`$ref`, no old path prefixes), the "API Types" job (generates the document, converts it with `openapi-typescript@7.13.0` — Celestia's major — and
type-checks the result), PHPStan, ESLint, unit and browser suites. **Run both `vendor/bin/pest` (unit) and `vendor/bin/pest tests/Browser` locally**
before pushing; CI's "Browser Tests" job also runs the unit tests.

**UI tests against another implementation** (goal: the browser UI tests also pass on Celestia backed by Luna; as of 2026-10-04 98 of 99 do). Run `scripts/ui-test-celestia.sh`: it reloads Luna's contract
seed (`UI_RESET=0` skips that), then runs `tests/Browser/{Admin,User,Guest}` with `UI_BASE_URL=http://localhost:3000` (Celestia, started with `E2E_TEST_LOGIN=1`; it must be on `localhost:3000` because Luna's Sanctum only
accepts session cookies from its `FRONTEND_URL`) and `CONTRACT_BASE_URL=http://127.0.0.1:8766` (Luna's `scripts/serve-contract.sh`, which the tests' own data setup talks to with a bearer token), excluding
`winterchilla-only`. The tests need the data of `scripts/dump-contract-seed.sh` (`build/contract-seed.sql`), a GET sign-in route matching `UI_LOGIN_PATH` (default `/test-login/{id}`; Celestia serves a page that calls
Luna's `GET /test/session-login/{id}`) and, for deviations and the club gallery, Luna's `/test/deviations/{id}` and `/test/club-gallery/{id}` (`tests/Browser/Helpers/Fixtures.php`). No Winterchilla server or DB reset happens
when `UI_BASE_URL`/`CONTRACT_BASE_URL` is set. Groups: `winterchilla-only` = cannot apply elsewhere (the dialog test page, the fake OAuth flows, `/components`, `/docs`, diagnose pages, and flows that drive Winterchilla's own DOM,
which `celestia-only` variants re-implement against Celestia's dialogs, skipped on Winterchilla). Celestia adopted Winterchilla's ids and `data-testid`s (`docs/ui-contract.md`, generated by `scripts/generate-ui-contract.py`, lists
what each test needs). A failure list grouped by cause: `scripts/ui-failures.py run.log`. Assertions use wording both sites can produce (e.g. 'already exists', 'slots'); check state through the API when dialogs differ.
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

## Database cutover to Luna (audit, nothing implemented yet)

**Goal:** move the prod data into Luna's database and point Winterchilla at it ahead of the cutover.
Audited 2026-09-30 by comparing migration files (Winterchilla `db/migrations` vs `Luna/database/migrations`) and
the local dev DB. **Prod was not inspected and no Luna DB was built**, so the drift list below is from source only —
confirm it with a real schema diff first (see "Next step").

### Status of the earlier schema alignment

Luna's `2020_04_29_190000_import_old_schema` is a port of Winterchilla's post-2020 schema (users table, uuid
`deviantart_users`, single-column PKs). Later Winterchilla changes Luna followed: blocked_emails and
email_verifications (`166bfe8`), event cleanup (`9686422`), dropped `notifications.read_action` and
`show.synopsis_last_checked` (`603f495`), widened `discord_members.access/refresh` (both sides, 2026).

### Drift still open (Winterchilla changed, Luna did not follow)

- [ ] `show_videos` dropped in Winterchilla (2022-03, also deleted `logs` rows of type `video_broken`); Luna still creates it
- [ ] `show.generation` column and `mlp_generation` enum dropped in Winterchilla (2024-11); Luna's import still creates both,
      and `MlpGeneration` enum / `MlpGenerationType` DBAL type are still in its code
- [ ] `discord_members.discriminator` is `smallint` in Winterchilla (2023-05), `char(4)` in Luna
- [ ] Row cleanups from 2022 that Luna code may still reference: `notifications` of type `sprite-colors`,
      `user_prefs` key `ep_hidesynopses`
- `discord_members.display_name` is `varchar(32)` here vs `varchar(128)` in Luna — harmless (Luna's is wider)

### File migration rehearsal (done 2026-09-30)

Pulled `fs/cm_source`, `fs/sprites`, `fs/sprites_pcg` (5.5 MB; the rest of `fs/` is render caches) with
`ssh vinyl.vps 'sudo -n tar -C /var/www/Winterchilla -cf - fs/cm_source fs/sprites fs/sprites_pcg'`, then ran
`php artisan fs:migrate <fs> 1 --wipe` in Luna against `luna_import`: **138 sprites + 146 cutie mark SVGs imported**
(`media` rows, `image/png` 137, `image/jpeg` 1, `image/svg+xml` 146). Two things surfaced:
- `sprites_pcg/641.png` is really a JPEG. Luna's `sprites` collection only accepted `image/png`, and the command aborts
  on the first bad file. Fixed in Luna (uncommitted there): the collection accepts `image/jpeg`, and
  `Core::generateHashFilename` takes the extension from the real mime type, so it is stored as `.jpg`.
- 6 files in `cm_source` (IDs 234, 246, 247, 249, 250, 251) have no `cutiemarks` row: they belonged to
  appearances that were later deleted (`cutiemarks.appearance_id` cascades, so no `cm_delete` log is written and the
  `cm_source` file is left behind): 234 = Obscura (608, deleted 2022-08), 246/247 = two Mirror King Sombra PCG
  appearances (660/661, identical files), 249-251 = three identical uploads for the 2026-07-08 "Applejack" PCG
  appearances (675-677); the `logs` table confirms the add/delete dates.
  Fixed in Winterchilla: `Appearance` now has a `before_destroy` callback (`removeCutiemarkFiles`) that removes the
  source/tokenized/rendered files while the rows still exist (regression test: `tests/Browser/Api/AppearanceApiTest.php`,
  backed by a seeded `DELETABLE_APPEARANCE_ID`). The leftovers on prod (6 `cm_source` files + stale derived copies) were deleted
  by hand on 2026-09-30, so the real `fs:migrate` should not hit the orphan check (the fix itself still needs deploying). The JPEG upload support in Luna's `sprites` collection is
  deliberately not documented in its OpenAPI. `fs:migrate` (rightly) refuses to run with orphans, so the rehearsal used a scratch
  copy without them. For the real run, exclude them (or delete them on the server first).
- The command was run with `--user $(id -u)`; a root-owned `storage/` from earlier root container runs needs a `chown`.

### Running both apps on the imported data (done 2026-09-30)

**Luna** (nginx vhosts `api.luna.lc` / `cdn.luna.lc` from `Luna/setup`, host php-fpm, `.env` pointing at `luna_import`)
serves the imported data: `/users/{id}`, `/users/da/{name}`, `/about/members`, `/appearances/{id}` (+ `/color-groups`,
`/full`, `/pinned`), `/color-guide` (418 pony incl. Universal Colors + 21 eqg, matches prod),
`/color-guide/major-changes`, `/show?types[]=..&order=overall|series` (153 episodes+movies+specials, matches prod).
Sprites redirect to the CDN vhost and resolve (`200 image/png`). Not usable locally: anything backed by ElasticSearch
(`/appearances`, `/appearances/autocomplete` fail with "No alive nodes"). `/useful-links/sidebar` is `[]` for guests by
design. Luna's `/show` payload still has a `generation` field (always `null` on imported data).

**Winterchilla on the Luna-schema DB works.** Started with `php -d variables_order=EGPCS -S ...` and `DB_NAME=luna_import`
(plain `DB_NAME=... php -S` is *ignored*: `$_ENV` isn't populated from the real environment, so `.env` wins; confirm the
override took effect with a bogus `DB_NAME`, which should 503), next to the same server on `prod_copy`: 20
pages/endpoints (cg lists, appearances, tags, blending, show, users, profiles, about, admin 403, `/api/v0/*`) return
identical status codes and byte-identical bodies apart from the CSP nonce. Caveat: both servers share Redis, so cached
fragments could hide differences.

**Still not verified:** logged-in flows (DeviantArt sign-in, Sanctum tokens, Luna auth), write endpoints on either app,
ElasticSearch-backed search on Luna, Luna's response shapes vs Winterchilla's `/api/v0` (different contracts), and a
full repeat of import + `fs:migrate` into prod's real (empty, migrated) `luna` DB.

### Why a plain dup + reimport is not enough

- **Files are not in the DB.** Sprites, cutie marks and `cm_source` live in `fs/`; Luna reads them via Spatie media
  library. `php artisan fs:migrate <fs folder> <uid>` (Luna) does the copy and must be part of the cutover.
- **Luna-owned data would be lost** if Luna's DB is overwritten: `media`, `personal_access_tokens`, `activity_log`,
  and any users registered natively on Luna. Check whether prod Luna holds anything worth keeping.
- **Winterchilla on the new DB:** it uses Phinx (`phinxlog`) and expects its own schema. Extra Luna-only tables
  (`media`, `personal_access_tokens`, `password_resets`, `failed_jobs`, `activity_log`) are fine, but Luna schema changes
  to shared tables would break it. Safest plan: keep the Winterchilla-schema DB as the source of truth and make Luna's
  migrations no-ops against it by pre-seeding Laravel's `migrations` table.

### Prod import rehearsal (done 2026-09-30)

Rehearsed locally with a real prod dump. Setup: `ssh vinyl.vps "sudo -n -u postgres pg_dump -d mlpvc-rr --column-inserts"`
into a local scratch DB (`prod_copy`; the dump holds user e-mails and ~1000 `sessions` token rows, keep it out of git and
delete it afterwards). Luna's locked deps (Laravel 9) don't install on PHP 8.5, so Luna runs in Docker (`php:8.1-cli` +
`pdo_pgsql exif gd bcmath intl zip` + composer, `--network host`) against a scratch DB (`luna_import`) with the `citext`
extension and `php artisan migrate`. The data load was `pg_dump --data-only --column-inserts --disable-triggers --no-owner
-T phinxlog` from `prod_copy` into `luna_import`.

**Result: the import loads cleanly.** Zero errors, every table's row count identical (1447 users, 509 appearances, 2297
posts, 155 shows, 6049 logs, ...), sequences already ahead of `max(id)` (the dump's `setval`s carry over), all Luna
models hydrate, appearance -> colour groups -> colours relations work. Prod already has a `luna` DB on the same server with
Luna's 16 migrations applied and no data in it, and a `luna_ro` role with grants on `mlpvc-rr`.

Schema differences that remain (prod vs Luna after migrate):
- [ ] `show_videos` (Luna only, dead table) and `show.generation` + `mlp_generation` enum (Luna only, dropped in
      Winterchilla 2024-11; Luna's `MlpGeneration` enum / DBAL type / `show` unique key `(season, episode, generation)`)
- [ ] `UserPrefKey` enum lacks `discord_token`: prod has 8 such `user_prefs` rows (a dead key, Winterchilla no longer
      references it) and `UserPref::all()` throws on them. Delete those rows in the import; the enum also still has
      `ep_hidesynopses`, which Winterchilla removed in 2022
- [ ] `cutiemarks.contributor_id` FK is `ON DELETE RESTRICT` in prod, `CASCADE` in Luna
- [ ] `pinned_appearances.created_at/updated_at` are `timestamp` (no zone) in Luna, `timestamptz` in prod
- [ ] `email_verifications` has `created_at/updated_at` only in prod (table is empty in prod, so nothing to drop)
- `discord_members.discriminator` `smallint` -> `char(4)` stores `'0   '`/`'1   '` (space padded, no leading zeros);
  harmless, Luna only does `$discriminator % 5` with it. `pcg_slot_history.change_amount` `real` -> `int`: no
  fractional values in prod. `show.score` `real` -> `double`: values carry over exactly. `settings.name` 50 vs 255 and
  `discord_members.display_name` 32 vs 128 are wider in Luna. Unique constraints match (prod uses unique indexes).
- Only 3 columns are nullable in prod but `NOT NULL` in Luna (`deviantart_users.user_id`, `locked_posts.post_id`,
  `locked_posts.user_id`); prod has no NULLs in them. (The local dev DB is *not* representative of prod's nullability —
  it showed ~70 such columns — so rehearse against a prod dump, not the dev DB.)
- Luna quirk unrelated to the import: `Post::getCreatedAtColumn()` reads `requested_by` before it's loaded and emits an
  "Undefined property" warning on every hydrate.

### Next steps

1. Luna-side migrations following Winterchilla for the open items above (and drop the dead code).
2. Rehearse the full path against `luna_import`: `fs:migrate`, then hit Luna's API endpoints (not just Eloquent) and diff
   against Winterchilla's `/api/v0` output.
3. Decide the cutover method: load the data into prod's empty `luna` DB (delete `discord_token` prefs first) vs
   adopt `mlpvc-rr` in place; then point Winterchilla at the chosen DB. Delete the local prod dump when done.

## Working on this plan

- Update the relevant stage's checkboxes and flip its heading from "not started" → "in progress" →
  "done" as you go; commit `CLAUDE.md` in the same commit as the tests it tracks so the two never
  drift apart.
- Route inventory can shift as the app changes — if `config/routes/pages.php` gains/loses routes,
  reconcile this plan in the same PR.
