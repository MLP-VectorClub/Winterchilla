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

Known cleanup item: `tests/Browser/User/PostsTest.php` duplicates `EpisodeTest.php` almost entirely —
fold it in or delete it as part of whichever stage touches episodes next.

### Stage 1 — PersonalGuideController (not started)

Entire controller is currently untested. Routes (`config/routes/pages.php` lines ~112-116):
- [ ] `/users/[id]/[cg]/[guide]?/[v]` and `/users/[id]/[cg]/[i]?` — personal guide list
- [ ] `/users/[id]/[cg]/slot-history/[i]?` — slot history
- [ ] `/users/[id]/[cg]/point-history/[i]?` — point history

New file: `tests/Browser/User/PersonalGuideTest.php`.

### Stage 2 — UserController remaining (not started)

- [ ] `/users` — user browse/list page
- [ ] `/u/[uuid]` — profile by UUID
- [ ] `/users/[id]/contrib/[type]/[i]?` — contribution tabs (art/other)
- [ ] `/user/contrib/lazyload/[favme]` — ajax lazyload behavior
- [ ] `/users/verify` — email verification flow
- [ ] Legacy `@[username]` redirect routes (~8 routes in pages.php lines 101-111) — one representative
      test confirming redirect target, not full per-route coverage

Extend `tests/Browser/User/UserProfileTest.php` or split into a new file if it grows unwieldy.

### Stage 3 — ColorGuide/Appearance remaining (not started)

- [ ] `/[cg]/blending-reverse` — reverse blending tool (forward already covered)
- [ ] `/[cg]/preferred` — preferred-guide redirect
- [ ] `/[cg]/[guide]/tag-changes/[id][adi]?` — tag-changes page
- [ ] `/[cg]/cutiemark/[id].svg` — cutiemark SVG render
- [ ] `/[cg]/cutiemark/download/[id][adi]?` — cutiemark download

Extend `tests/Browser/User/ColorGuideTest.php`.

### Stage 4 — ShowController movies/generic (not started)

Only the episode side of this controller is tested.
- [ ] `/movies` — movie list
- [ ] `/show` — generic show index
- [ ] `/[st]/[id][adi]?` — viewById for movie type specifically (episode/pony side already covered)

Extend `tests/Browser/User/EpisodeTest.php` or rename to reflect broader "show" scope once both sides are covered.

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
- [ ] Remove/merge `tests/Browser/User/PostsTest.php` duplication (see Stage 0 note)
- [ ] Decide whether `public_api_v0.php` endpoints need direct coverage beyond what's exercised
      incidentally through page-level UI flows

## Working on this plan

- Update the relevant stage's checkboxes and flip its heading from "not started" → "in progress" →
  "done" as you go; commit `CLAUDE.md` in the same commit as the tests it tracks so the two never
  drift apart.
- Route inventory can shift as the app changes — if `config/routes/pages.php` gains/loses routes,
  reconcile this plan in the same PR.
