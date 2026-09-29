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
existing shortcut for authenticated flows — no route needs real DeviantArt/Discord OAuth to be tested
except the OAuth begin/end endpoints themselves (see Stage 6).

Route inventory source of truth: `config/routes/pages.php`.

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

Beyond `composer install`, `pnpm install && pnpm build`, a `.env` (with `TEST_MODE=true`) and running
Postgres/Redis: the PHP `pdo_sqlite` extension must be enabled (composer platform requirement), and
Playwright's own browser build must match the installed `playwright` npm version —
`pnpm exec playwright install chromium`. A mismatch makes every test fail instantly with
`PlaywrightOutdatedException` (no requests ever reach the server).

Diagnosing the timeout issue above took a long time because both failure modes are *silent* — no PHP fatal, no Pest error,
tests just report the standard 5s navigation timeout as if the app were slow. The fastest way to confirm
either bug in the future: put an unconditional `throw` at the very top of the file in question and see if
it actually aborts the run.

### Stage 0 — Baseline (done)

Already covered, no action needed unless a regression is found:
- Dialog component — `tests/Browser/Dialog/DialogTest.php` (20 tests, all dialog types/states)
- Auth via test-login — `tests/Browser/User/AuthTest.php`
- Admin panel (index, logs+filter, useful links, notices, pcg-appearances, discord page, 403-to-guest) — `tests/Browser/Admin/AdminTest.php`
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

### Stage 2 — UserController remaining (not started)

- [ ] `/users` — user browse/list page
- [ ] `/u/[uuid]` — profile by UUID
- [ ] `/users/[id]/contrib/[type]/[i]?` — contribution tabs (art/other)
- [ ] `/user/contrib/lazyload/[favme]` — ajax lazyload behavior
- [ ] `/users/verify` — email verification flow
- [ ] Legacy `@[username]` redirect routes (~8 routes in pages.php lines 101-111) — one representative
      test confirming redirect target, not full per-route coverage

Extend `tests/Browser/User/UserProfileTest.php` or split into a new file if it grows unwieldy.

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

### Stage 5 — Misc smoke coverage (not started)

Lower-traffic or non-page routes — smoke-test (loads, no fatal error, no JS error) rather than deep behavior:
- [ ] `/eqg/[id]` and `/eqg/[adi]` (EQGController) — redirect-only routes
- [ ] `/s/[thing]/[id]` (PostController) — share-link redirect
- [ ] `/about/browser/[session]?` (AboutController) — browser diagnostics page
- [ ] `/components` (ComponentsController) — confirm still user-facing before writing a test; may be dev-only
- [ ] `/docs` (DocsController)
- [ ] `/muffin-rating` (MuffinRatingController) — image endpoint, verify it returns a valid image response

### Stage 6 — OAuth edges (research spike, not started)

- [ ] `/da-auth/begin`, `/da-auth/end` (AuthController)
- [ ] `/discord-connect/begin`, `/discord-connect/end` (DiscordAuthController)

These hit a real external OAuth provider and can't be driven end-to-end without a stub/mock for
DeviantArt/Discord. Needs a decision on approach (fake provider server? recorded fixture?) before
tests can be written — don't attempt inline as part of another stage.

### Stage 7 — Closeout audit (not started)

- [ ] Diff the full test suite's covered routes against `config/routes/pages.php` one more time to confirm
      nothing was missed
- [x] Remove/merge `tests/Browser/User/PostsTest.php` duplication (done in Stage 4)
- [ ] Decide whether `public_api_v0.php` endpoints need direct coverage beyond what's exercised
      incidentally through page-level UI flows

## Working on this plan

- Update the relevant stage's checkboxes and flip its heading from "not started" → "in progress" →
  "done" as you go; commit `CLAUDE.md` in the same commit as the tests it tracks so the two never
  drift apart.
- Route inventory can shift as the app changes — if `config/routes/pages.php` gains/loses routes,
  reconcile this plan in the same PR.
