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

### Open questions

- Versioning: keep evolving `/api/v0` (documented as unstable) or cut `/api/v1` for the Celestia/Luna
  contract? Leaning `/api/v1` for read endpoints so the contract can freeze while `v0` keeps changing.
- Auth for Celestia/Luna: session cookie only (today) vs token auth. They are separate apps, so cookies
  from another origin won't work — needs a decision before Phase 1 endpoints are declared stable.
- Which projects call this API server-to-server vs from browsers (CORS)?
- Write endpoints: the plan above covers reads; existing mutation endpoints need the same contract-test
  and docs treatment — in scope for this round?
  (SEO/first-paint questions are Celestia/Luna's to answer, not Winterchilla's.)

## Working on this plan

- Update the relevant stage's checkboxes and flip its heading from "not started" → "in progress" →
  "done" as you go; commit `CLAUDE.md` in the same commit as the tests it tracks so the two never
  drift apart.
- Route inventory can shift as the app changes — if `config/routes/pages.php` gains/loses routes,
  reconcile this plan in the same PR.
