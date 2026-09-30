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

### Stage 7 — Closeout audit (in progress)

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

## API-driven pages migration (audit + plan, nothing implemented yet)

**Purpose:** preparation for the Celestia/Luna reimplementation of these features. The API is the deliverable:
it is the contract those projects will build against (alongside the browser suite as the behavioral spec).
Migrating Winterchilla's own front end onto it is secondary — a way to prove the API is complete — so
prefer the smallest change that makes each page's data available over JSON, and don't invest in
client-side rendering of Winterchilla itself beyond what's needed to validate an endpoint.

**Goal (decided):** move data passing from views to an API — ultimately full API-driven pages (server
stops embedding data and rendering list/table HTML; the front end fetches JSON and renders), which is
the shape Celestia/Luna will need. The browser suite above is the regression net: it must stay green
(or change only where a page's markup legitimately moves) at every step.

### Current state (audit)

Data reaches JS in three ways:
1. **Datastore blobs.** Templates call `export_vars({...})` (`CoreUtils::exportVars`), emitting
   `<aside class="datastore">` JSON; `assets/js/datastore.js` copies each key onto `window`. ~27 call
   sites in 17 templates, read as bare globals by 14 page scripts. Regexes are shipped as `/src/flags`
   strings and revived by `datastore.js`. `layout/_scripts.html.twig` exports for every page.
2. **Server-rendered HTML fragments** returned by API endpoints (`'html'` in ~10 API responses: post
   lists, appearance blocks, `lazyload` endpoints) and whole pages rendered by Twig (episode lists,
   posts, guide, tag lists, logs).
3. **Data attributes / inline markup** read by scripts (not audited yet).

Existing API: internal `/api/v0/...` (`config/routes/public_api_v0.php`, 75 endpoints,
`Controllers/API/*`) is already used by the UI for mutations and some reads; it is not yet a complete
read API for page data. Note the `/api/v0` docs are generated from swagger-php docblocks.

Globals inventory (key → where exported → readers):

| Group | Keys | Source of value | Readers |
|---|---|---|---|
| Static constants | `TAG_TYPES_ASSOC`, `ROLES`, `ROLES_ASSOC`, `showTypes`, `PRINTABLE_ASCII_PATTERN`, `HEX_COLOR_PATTERN`, `MAX_SIZE`, `discordInviteLink` | PHP constants/config | colorguide `manage.jsx`/`tag-list.js`, `user/manage.js`, `admin/useful-links.js`, `event/view.js`, `show/index-manage.jsx`, `global.jsx` |
| Client config | `wsServerHost`, `signedIn` (unused by JS) | env / session | `websocket.js` |
| Page context: guide | `GUIDE`, `AppearancePage`, `OwnerId` | route params, appearance owner | `colorguide/guide.js`, `full-list.js`, `manage.jsx` |
| Page context: user | `username`, `userId`, `sameUser` | profile/account/pcg-slots user | `user/{profile,account,pcg-slots,manage}.js`, `manage.jsx` |
| Page context: show | `showId`, `showType`, `isEpisodePage`, `linkedPostURL` | show model, share link | `show/{view,manage,index-manage}.js(x)` |
| Validation regexes | `usernameRegex`, `episodeTitleRegex` | `RegExp` PHP objects | `show/manage.jsx`, `show/index-manage.jsx` |
| Verify flow | `verifyHash`, `verifyAction` | query string | `user/verify.js` |

### Target design

- **Bootstrap/config endpoint** `GET /api/v0/config` (cacheable): all static constants and validation
  patterns (as `{source, flags}` objects instead of `/x/` strings), plus `wsServerHost` and
  `discordInviteLink`. Replaces every "static constants" and "client config" global. The layout's
  only remaining data is the identity of the page itself (route name + route params), e.g. a
  `data-page`/`data-params` attribute, or nothing if the router lives in JS.
- **Resource endpoints for page context**: reuse/complete `/user/[id]`, `/show/[id]`, `/cg/appearance/[id]`,
  `/cg/guide/[guide]` (new), `/users/me` (exists) so scripts derive `username`, `userId`, `sameUser`,
  `showType`, `OwnerId`, `GUIDE` from the fetched resource plus URL params, not globals.
- **List endpoints returning data, not HTML** for: show index (episodes/movies), show posts
  (`/show/[id]/posts` exists — currently HTML), appearances/guide pages, tag list, users list,
  contributions, events, admin logs/notices/links. One serializer per resource shared by page and API
  during the transition so they can't drift.
- **Client rendering**: page scripts (already React/JSX for the manage screens) render from JSON;
  HTML templates shrink to a shell. Decide whether to keep server-rendered first paint for SEO-relevant
  pages (guide/appearance/episode pages are public and indexed) — see open questions.

### Phases (revised for the migration-prep purpose)

0. **Contract tests** — HTTP-level tests (no browser) pinning the JSON shape of every endpoint a phase
   touches. These double as the spec Celestia/Luna can run against their own implementation.
1. **Read API completeness** — for every page in the coverage plan, an endpoint that returns exactly the
   data that page renders (episode list, show + posts, guide page, appearance, tag list, users list,
   contributions, events, profile, personal guide, admin lists), as data not HTML. Backed by shared
   serializers so the Twig page and the endpoint use the same source.
2. **Config endpoint** `GET /api/v0/config` for constants, validation patterns (`{source, flags}`) and
   client config (replaces the static-constant and regex globals).
3. **Document** the contract: complete the swagger-php annotations so `/docs` describes every endpoint,
   error shape and auth rule; treat that OpenAPI output as the artifact handed to Celestia/Luna.
4. **Prove it** — move Winterchilla's page scripts off `window` globals onto these endpoints where cheap
   (context globals, constants, regexes), then retire `datastore.js`/`export_vars`. Full client-side list
   rendering only where an endpoint needs validating end to end.

Each phase: add/adjust tests first, keep `vendor/bin/pest tests/Browser` green, update checkboxes here.

### Decisions

- **Versioning:** stay on `/api/v0` for now (already documented as unstable); no `/api/v1`.
- **Auth:** verified from source — Luna uses **Laravel Sanctum** (`laravel/sanctum` ^2.0; not Passport):
  `auth:sanctum` routes, plain-text bearer tokens issued by `User::authResponse()` after sign-in, plus
  stateful-cookie support for its own frontend host. Luna has its own users and DB and re-implements the
  same resources itself (`/appearances`, `/users`, `/color-guide`, `/about`, `/useful-links`, ...), so
  Winterchilla's API is the *behavioral spec* Luna implements, not something Luna authenticates against.
  Contract tests therefore must not depend on Winterchilla's DeviantArt session/`test-login` mechanics as part of
  the contract; document auth per endpoint as an abstract requirement (public / signed in / role) that
  Luna maps onto Sanctum.
- **Browser calls:** Celestia proxies `NEXT_PUBLIC_API_PREFIX/:path*` to the backend via a Next.js
  rewrite (`Celestia/apps/celestia/next.config.js`), so there is no cross-origin browser traffic and no
  CORS work is needed.
- **Writes are in scope:** existing mutation endpoints get the same contract tests and OpenAPI docs as the
  reads.

### Contract format: Luna's (verified from `Luna/app`)

The contract follows what Luna already does, so Winterchilla's API converges on it rather than the reverse:
- **camelCase JSON keys** (`response()->camelJson`); pagination is
  `{currentPage, totalPages, totalItems, itemsPerPage}` (`Core::mapPagination`).
- **No `status` envelope.** Success is a proper 2xx with the resource as the body; actions with nothing to
  return are `204 No Content`. Failure is a proper 4xx/5xx.
- **Error bodies** (`ErrorResponse` / `ValidationErrorResponse` schemas in `Luna/app/Http/Controllers/Controller.php`):
  `{"message": "..."}` and, for validation, `422 {"message": "...", "errors": {"field": ["msg", ...]}}`.
  `401` unauthenticated, `403` forbidden, `404` missing, `429` throttled, `503` dependency down (e.g. ElasticSearch).
  Luna returns an *empty* 404 body in production, so clients must not rely on a 404 message.
- **OpenAPI 3 via swagger-php annotations** with shared schemas (`ErrorResponse`, `ValidationErrorResponse`,
  `PageNumber`, `OneBasedId`/`ZeroBasedId`, `IsoStandardDate`, enums for guide names etc.); reuse the same
  schema names in Winterchilla's docs so the two specs can be diffed.

### Error and status migration (`success: true/false` → HTTP statuses)

Today every API response is `200` with `{status: bool, message?, ...data}` (`App\Response`; 313 call sites:
256 `fail`, 2 `failApi`, ~31 `dbError`, 26 `success`, 99 `done`), and ~100 JS call sites check `this.status`
(`$.mkAjaxHandler` only sees 2xx responses). Failures are shipped as HTML in `message` (e.g. the sign-back-in
button), which is also not contract-friendly.

`docs/api-error-inventory.md` lists every call with its function and a *heuristic* suggested status — review per
call, don't apply blindly. Approach, in order:
1. `Response::fail`/`failApi`/`dbError` gain an explicit HTTP status (default derived from the empty-message
   auth case: 401 signed-out / 403 signed-in; otherwise required at the call site during migration).
   `success` sends 200 (or 204 when there is no body). Bodies drop `status` and use Luna's error shape.
2. **Client shim first** in `$.API` (`shared-utils.js`): on non-2xx, call the same callbacks with
   `{status: false, message, errors}`, and add `status: true` on 2xx, so the ~100 existing `!this.status`
   checks keep working while the server moves. Remove the shim (and the checks) at the end.
3. Migrate controller by controller, each with contract tests asserting status code + body shape, and the
   matching browser test still green. Suggested order: read endpoints and small controllers
   (`Auth`, `Setting`, `About`, `Notification`) → `Show`, `Event`, `User`, `Tag`, `ColorGroup` → `Post`,
   `Appearance` (largest: 44 and 38 failure sites).
4. HTML-in-message cases become structured fields (`{message, code}`), with the HTML built client-side.
5. Key-case migration to camelCase is done per endpoint alongside its contract test (many keys are already
   camelCase or single words); document any endpoint that has to keep a legacy key.

### Migration progress

Infrastructure (done): `Response::error($status, $message, $extra)`, `Response::ok($data, $status)`,
`Response::noContent()`, and an optional `status:` argument on `Response::fail()`/`dbError()` (passing it switches
that call to the new format; calls without it stay legacy until migrated). The `$.API` wrapper in
`shared-utils.js` is the client shim from step 2. `CoreUtils::notFound/noPerm/notAllowed` JSON branches use the
new error body. Shared `ErrorResponse`/`ValidationErrorResponse` schemas live in `APIController`'s docblock.

Contract tests live in `tests/Browser/Api/` (they reuse the browser suite's server/DB bootstrap, hence the
directory) and use `Tests\Browser\Helpers\ApiClient` (cookie jar + CSRF echo, `guest()`/`loggedInAs()`).

- [x] `AuthAPIController` (`/da-auth/status`, `/da-auth/sign-out`) — pilot; `retries_remaining` →
      `retriesRemaining`, sign-out is 204, errors 403/404/500 with `{message}`. Also fixed the guest
      `/da-auth/status` path calling `unsetData` on a null session.
- [x] `SettingAPIController` (`/setting/{key}`) — 401/403/404 (unknown key; used to fatal on an undefined
      index), 422 with `errors.value` for missing/invalid values. Also fixed: submitting an empty value (the
      documented way to reset a setting) 500'd on a `string` type error; it now resets and returns the
      effective value.
- [x] `AboutAPIController` (`/about/server`, `/about/upcoming`) — plain bodies; 500 `{message}` when git info
      is unavailable. `/about/upcoming` still returns rendered HTML in `html`.
- [x] `NotificationAPIController` (`/notif`, `/notif/{id}/mark-read`) — 401 for guests, 404 for unknown
      notifications (including other users' — ownership is enforced), mark-read is 204. `TestSeeder` seeds
      three unread `post-approved` notifications (`TestSeederConstants::NOTIFICATION_*`). `/notif` still
      returns rendered HTML in `list`.
- [x] `ShowAPIController` (`/show`, `/show/{id}` + `/posts`, `/vote`, `/guide-relations`, `/next`, `/prefill`) —
      201 `{id, url}` on create, 204 on update, `409` for duplicate season/episode and voting conflicts,
      `422` field errors, 401/403 via `Response::denied()`, hiatus/none-found as 404. `show` payload is camelCase
      (`postedBy`). Found and fixed two production 500s along the way: `/show/{id}/posts` (template needed
      `signed_in`, only the page context provided it) and `/show/{id}/guide-relations` GET (SQL syntax error
      when no appearance is pinned).
- [x] `EventAPIController` + `EventEntryAPIController` (`/event/...`, `/event/entry/...`) — event management,
      finalizing and entry submission are switched off in the app; they now answer 401/403 first and then `501`
      (previously a 200 `{status: false}`). Entry read/update/delete: 401/403/404, 422 field errors, delete is 204,
      `prev_src` → `prevSrc` and `entryhtml` → `entryHtml` in responses (request field names are unchanged).
      `TestSeeder` seeds three entries plus their Redis-cached deviation metadata (otherwise the event page asks the
      real DeviantArt oEmbed API about made-up IDs). Not covered: a successful PUT (needs the real DeviantArt link
      check) and the lazyload success body.
- [x] `UserAPIController` + `/users/me` (`/user/session/{id}`, `/user/{id}/role|email|contrib-cache|avatar-wrap`,
      `/user/password`, `/user/verify`, `/users/me`) — 401/403/404, 422 field errors (`current_password`,
      `new_email`, `hash`, `value`), 409 (password must be set first), 429 (confirmation e-mail sent recently), 503
      (mail not sent). Session delete and role change are 204 (`alreadyIn: true` with 200 when nothing changes).
      **Success bodies that the UI shows to the user keep a `message`** (password set, confirmation e-mail sent,
      verified/blocked, cache cleared) — a documented optional field, not the legacy envelope. Password/e-mail/
      verify are staff-only (`CoreUtils::roleGate`, 403) while the feature is under testing. `CoreUtils::noPerm()`'s
      JSON branch now goes through `Response::denied()` (401 for guests). Fixed: clearing a contribution cache that
      doesn't exist crashed on `unlink()`. Not covered: the e-mail flow past validation (needs a real domain with MX
      records, i.e. the network) and the do-not-send/verification success paths.
- [x] `TagAPIController` (`/cg/tags`, `/cg/tags/recount-uses`, `/cg/tag/{id}`, `/cg/tag/{id}/synonym`) — staff only
      (401/403); 404 for missing tags; 422 field errors (`name`, `type`, `target_id`, `tagids`, duplicate name+type
      is a 422 on `name`); create is 201 with the tag (`tags` HTML when `addto` succeeded, `warning` when it
      couldn't be added); delete is 204; deleting an in-use tag is `409 {message, uses}` until `sanitycheck` is sent
      (replaces the old `confirm: true` + HTML); an already-synonym tag is `409` with `synonymOf {id, name}` (replaces
      `undo: true` + HTML); tag/autocomplete keys are camelCase (`synonymOf`, `synonymTarget`; `tid` is not returned).
      `tag-list.js`/`manage.jsx` build the dialogs' HTML client-side now. Also fixed: creating/updating a tag
      without a `type` read an undefined index. The synonym/unsynonym dialogs are covered
      by `tests/Browser/Admin/TagSynonymTest.php`.
- [x] `ColorGroupAPIController` (`/cg/colorgroup`, `/cg/colorgroup/{id}`) — 401 for guests, 403 without permission on the
      appearance (regular users can't touch official-guide groups), 404 for missing groups/appearances, 422 field
      errors under `ponyid`, `label`, `reason` and `Colors` (the request field keeps its capital `C`; per-color
      problems are reported on it), create is 201 and update 200 with `{id, cgs, notes|cmList, update|changes}`
      (rendered HTML fragments), delete 204. GET is camelCase (`appearanceId`, `colors`). Fixed: the group was saved
      *before* its colors were validated, so a rejected request left an empty/renamed group behind; and the label's
      invalid-character check was a no-op (`returnError` was passed but the result never used). Personal-guide ownership is
      covered through the seeded personal appearance (owner and staff may manage its groups). Not covered: the
      major-change/`reason` flow when *creating* a change (the seed only provides existing changes).
- [x] `PostAPIController` (`/post/...`) — statuses only (bodies of the UI-oriented endpoints keep their HTML
      fragments: `li`, `section`, `pendingReservations`, `button`, `suggestion`, `message`): 401 signed out / 403 without
      the right role or ownership, 404 missing posts, 409 for state conflicts (already reserved — with the current `li`
      —, must unfinish first, not reserved/finished yet, locked, reservation limit, duplicate image or deviation,
      `retry: true` / `canForce: true` flags kept for the "continue anyway" dialogs), 422 field errors (`label`, `type`,
      `show_id`, `image_url`, `deviation`, `as`), create is 201 (`/post`, `/post/reservation`), edit/finish/delete are 204.
      GET `/post/{id}` is camelCase (`postedAt`, `reservedAt`, `finishedAt`). Fixed **a real authorization hole**:
      `_checkPostEditPermission()` joined its request and reservation clauses with `&&` (never both true), so it never
      denied anyone — signed-out visitors could `GET`/`PUT` any post, and a `PUT` with no fields blanked its label. It now
      requires sign-in and lets the requester edit an unreserved request, the reserver their reservation, and staff
      anything. Also fixed: `lazyload` on an unfinished post 500'd (now 409). Not covered (need the network): creating
      posts, setting images, finishing with a deviation, approval success; `reload` on posts without a deviation
      would mark the seeded posts broken (their images are `example.com` URLs), so the tests only reload the finished one.
      `TestSeeder` seeds posts 2 (deletable) and 3 (reserved and finished, with cached deviation metadata). Seeded post
      images point at `http://127.0.0.1:8765/img/blank-pixel.png` (served by the test server) rather than `example.com`, because
      opening a post page can trigger a network availability check that marks posts broken.
- [x] `AppearanceAPIController` (`/cg/appearance/...`) and the helpers behind it (`Appearance::checkCreatePermission`,
      `Image`, `CGUtils` uploads, `Appearances` reindexing) — 401/403/404; 422 field errors (`label`, `guide`, `cgs`,
      `cutiemarks`, `CMData`, `file`, `image_url`); 409 for state conflicts (pinned appearances can't be deleted,
      personal guides have no tags/relations/pins, not enough color groups to reorder, no slots left); create is 201
      (`{id, goto, message}`), delete/selective clear/tag update are 204, pin/unpin keep a `message` body the UI shows;
      ElasticSearch down is 503 (reindex). `keep_dialog` → `keepDialog`, `newurl` → `newUrl`. Regular users get 403 when
      creating personal appearances (the `a_pcgmake` preference is off by default) — the success path needs that
      preference and isn't covered. Not covered: sprite upload, template application, sanitize-svg, cutie mark saving
      (file/network heavy).
- [x] The rest of the API controllers: `AppearancesAPIController` (the public `/appearances` API — now `{message}` errors, 422 with
      `errors.guide`, 503 when ElasticSearch is down, 403 for private appearances, `createdAt`, no `status`/`cachedOn`/
      `cachedFor`; the cache key was bumped so old cached bodies aren't served), `AdminAPIController`,
      `PreferenceAPIController` + `UserPrefs`, `PersonalGuideAPIController`, `ColorGuideAPIController`,
      `DiscordAuthController` sync/unlink (429 when synced too recently, 409 when not linked), `DiscordMember` (409 +
      `segway`), the `ColorGuideController` search JSON (503 `unavail`, 404 no results), `UserController::contribLazyload`.
      **`AdminAPIController` had no authorization at all** — its staff check was lost in the API controllers refactor
      (like `ColorGroupAPIController`'s appearance state), so a *signed-out* visitor could create/edit/delete useful
      links (shown site-wide) and read log details: verified with an unauthenticated `POST /api/v0/admin/usefullinks`
      returning `{"status":true}`. It's now staff-only via a constructor check. Also fixed: uploading a non-image
      as a sprite was a 500.
- **CSRF failures are now `419`** (was `401`, which now means "not signed in"). `shared-utils.js` maps 419 to the CSRF
  dialog, and requests made through `$.API` with a callback are marked `apiHandled` so the global status dialogs
  (`$.ajaxSetup`) don't pile on top of the callback's own error handling.
- [x] HTML-in-message sweep: API messages are plain text now (no `<a>`, `<b>`, `<p>`, smileys or pre-escaped values). Where a
      message used to carry a link, the details are structured instead: `existingPost {id, kind, url}` (duplicate image or
      deviation), `reservedBy {id, name}`, `approved`/`notified` on finish. `Input` and `checkStringValidity` no longer
      HTML-escape the value they quote. The `$.API` shim escapes `message` when handing it to callers (they put it in dialogs
      as HTML) and keeps the plain text in `rawMessage`; the global status-code dialogs escape too. For 422 responses the shim
      builds `message` from **all** `errors` (one per line) instead of the generic "The given data was invalid." — without
      that the dialogs lost the specific reason (found by `tests/Browser/Admin/ErrorMessageTest.php`; it affected every
      endpoint migrated since `Input` started answering 422).
- [x] The `$.API` shim and the `this.status` checks are gone. `$.API.get/post/put/delete(url, data)` now return the jqXHR and
      callers use `.done(resp => ...)` (the resource itself; `undefined`/`{}` for 204) and `.fail($.API.fail(title))` — or
      `.fail($.API.failWith(body => ...))` for custom handling (`body.message` is escaped for HTML dialogs, `body.rawMessage`
      is the plain text, 422 field errors are joined into the message). Both mark the request `apiHandled` so the global
      `$.ajaxSetup` status dialogs stay quiet. ~100 call sites were converted with an AST codemod (espree) plus ~30 by hand;
      new UI tests cover the admin useful-links CRUD and casting a vote (`UsefulLinksTest`, `VoteTest`), next to the
      existing appearance/color group/tag synonym/sign-out flows. Untested conversions (worth a click-through before deploying):
      post reserve/finish/approve/unbreak on episode pages, event entry edit, personal guide points/slots, tag editing dialogs,
      cutie mark editor, sprite upload/remove, relations editors.
- [x] The page-level JSON views are API endpoints now, so every client-side data request goes through `$.API`:
      `GET /cg/full?guide&sort_by` (was `/cg/[guide]/full?ajax`; the page and the API share
      `ColorGuideController::getFullListData()`) and `GET /user/contrib/lazyload/{favme}` (was a page route on
      `UserController`). The dead JSON branches of the guide search ("I'm feeling lucky" is a plain redirect) are removed.
      Fixed on the way: `POST /cg/full/reorder` ignored the `guide` the client sent (it only read a route parameter that
      doesn't exist), so it re-rendered the list of *every* guide; it now requires `guide` (422 otherwise). Covered by
      `ColorGuideApiTest` plus UI tests for re-sorting the full list and lazy-loading deviations on a contributions page.
      Only `jquery.uploadzone.js` still makes its own ajax call (multipart uploads to API endpoints), with its own error handler.

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

### Why a plain dup + reimport is not enough

- **Files are not in the DB.** Sprites, cutie marks and `cm_source` live in `fs/`; Luna reads them via Spatie media
  library. `php artisan fs:migrate <fs folder> <uid>` (Luna) does the copy and must be part of the cutover.
- **Luna-owned data would be lost** if Luna's DB is overwritten: `media` (filled by `fs:migrate`),
  `personal_access_tokens`, `activity_log` and any users registered natively on Luna. Prod Luna is empty today.
- **Winterchilla on the new DB:** it uses Phinx (`phinxlog`) and expects its own schema. Extra Luna-only tables are
  fine, but Luna schema changes to shared tables would break it. Safest plan: keep the Winterchilla-schema DB as the
  source of truth and make Luna's migrations no-ops against it by pre-seeding Laravel's `migrations` table.

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
