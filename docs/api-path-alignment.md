# API path alignment with Luna (decided 2026-09-30)

**Decision:** Winterchilla's write API moves to Luna's resource-oriented paths. Winterchilla's own client is the only
consumer and deploys together with the API, so paths are **renamed in place**: no aliases, no transition state. Each resource
group changes its route, docblock `path=`, contract tests and the client calls in one commit. The OpenAPI spec documents the
canonical paths (Celestia's generated types are named after them).

## Conventions (taken from `Luna/routes/api.php`)

- Plural, kebab-case resource nouns: `/appearances`, `/appearances/{id}`, `/users/{id}`, `/color-groups`, `/useful-links`.
  Two existing singular exceptions stay because Celestia already uses them: `/show` (+ `/show/{id}`) and `/color-guide`.
- Sub-resources for owned data: `/appearances/{id}/color-groups`, `/appearances/{id}/sprite`.
- HTTP verbs carry the action: `POST` create, `PUT` replace/update, `DELETE` remove. Verbs in the path only where Luna does
  (`/users/signout`) or for genuine commands with no resource (`/tags/recount-uses`, `/color-guide/reindex`).
- `GET` never changes state (Winterchilla's `GET /post/{id}/unbreak` becomes `POST /posts/{id}/unbreak`).
- Requests and responses are camelCase. (Request field renames are a separate step — see the bottom.)

## Router change this needs

`config/routes/public_api_v0.php` maps a path to one controller for *every* method (`POST|GET|PUT|DELETE`), so two controllers can't
share a path. Canonical paths do collide (`GET /tags` is the list, `POST /tags` creates), so `$api_endpoint($path, $target, $methods)`
takes an optional method list. A method with no route answers the router's 404 instead of the controller's 405. Optional-parameter
routes (`[i:id]?`) are split into collection and item routes so the docs can describe them separately.

## Mapping (old → canonical)

Endpoints marked *ui* return rendered HTML for Winterchilla's own pages; they keep their old path, get no canonical twin and are excluded
from what Luna implements.

### Appearances
| Old | Canonical |
|---|---|
| `GET /cg/appearance/{id}` (editable fields) | `GET /appearances/{id}/metadata` |
| `POST /cg/appearance` | `POST /appearances` |
| `PUT /cg/appearance/{id}` | `PUT /appearances/{id}` |
| `DELETE /cg/appearance/{id}` | `DELETE /appearances/{id}` |
| `POST /cg/appearance/{id}/template` | `POST /appearances/{id}/template` |
| `DELETE /cg/appearance/{id}/selective` | `DELETE /appearances/{id}/contents` |
| `GET,PUT /cg/appearance/{id}/colorgroups` (order) | `GET,PUT /appearances/{id}/color-groups/order` |
| `POST,DELETE /cg/appearance/{id}/sprite` | `POST,DELETE /appearances/{id}/sprite` |
| `GET,PUT /cg/appearance/{id}/relations` | `GET,PUT /appearances/{id}/relations` |
| `GET,PUT /cg/appearance/{id}/cutiemarks` | `GET,PUT /appearances/{id}/cutie-marks` |
| `GET,PUT /cg/appearance/{id}/tagged` | `GET,PUT /appearances/{id}/tags` |
| `POST /cg/appearance/{id}/sanitize-svg` | `POST /appearances/{id}/sanitize-svg` |
| `GET,PUT /cg/appearance/{id}/guide-relations` | `GET,PUT /appearances/{id}/shows` |
| `POST,DELETE /cg/appearance/{id}/pin` | `POST,DELETE /appearances/{id}/pin` |
| `GET /cg/appearances` (autocomplete) | `GET /appearances/autocomplete` (Luna has it) |
| `GET /cg/full` *ui* | — (`GET /appearances/full` is the data equivalent) |
| `POST /cg/full/reorder` | `PUT /appearances/order` |

### Color groups, tags, color guide
| Old | Canonical |
|---|---|
| `POST /cg/colorgroup` | `POST /color-groups` |
| `GET,PUT,DELETE /cg/colorgroup/{id}` | `GET,PUT,DELETE /color-groups/{id}` |
| `GET /cg/tags` | `GET /tags` (+ `GET /tags/autocomplete` for the `s` search) |
| `POST /cg/tag` | `POST /tags` |
| `GET,PUT,DELETE /cg/tag/{id}` | `GET,PUT,DELETE /tags/{id}` |
| `PUT,DELETE /cg/tag/{id}/synonym` | `PUT,DELETE /tags/{id}/synonym` |
| `POST /cg/tags/recount-uses` | `POST /tags/recount-uses` |
| `GET /cg/export` | `GET /color-guide/export` |
| `POST /cg/reindex` | `POST /color-guide/reindex` |

### Shows, events, posts
| Old | Canonical |
|---|---|
| `POST /show`, `GET,PUT,DELETE /show/{id}` | unchanged (`/show` is Luna's) |
| `GET /show/{id}/posts`, `/vote`, `/next`, `/prefill` | unchanged |
| `GET,POST /show/{id}/guide-relations` | `GET,PUT /show/{id}/appearances` |
| `POST /event`, `GET,PUT,DELETE /event/{id}` | `POST /events`, `GET,PUT,DELETE /events/{id}` |
| `POST /event/{id}/finalize` | `POST /events/{id}/finalize` |
| `GET /event/{id}/check-entries` | `POST /events/{id}/entries/check` |
| `GET,PUT,DELETE /event/entry/{id}` | `GET,PUT,DELETE /event-entries/{id}` |
| `GET /event/entry/{id}/lazyload` *ui* | — |
| `POST /post` | `POST /posts` |
| `GET,PUT /post/{id}` | `GET,PUT /posts/{id}` |
| `POST,DELETE /post/{id}/reservation` | `POST,DELETE /posts/{id}/reservation` |
| `POST,DELETE /post/{id}/approval` | `POST,DELETE /posts/{id}/approval` |
| `PUT,DELETE /post/{id}/finish` | `PUT,DELETE /posts/{id}/finish` |
| `PUT /post/{id}/image` | `PUT /posts/{id}/image` |
| `POST /post/{id}/locate` | `GET /posts/{id}/location` |
| `GET /post/{id}/unbreak` | `POST /posts/{id}/unbreak` |
| `GET /post/{id}/reload`, `/lazyload` *ui* | — |
| `POST /post/check-image` | `POST /posts/check-image` |
| `POST /post/reservation` | `POST /posts/reservations` |
| `DELETE /post/request/{id}` | `DELETE /posts/requests/{id}` |
| `GET /post/request/suggestion` | `GET /posts/requests/suggestion` |

### Users, sessions, preferences
| Old | Canonical |
|---|---|
| `GET /users/me` | unchanged (Luna's) |
| `POST /da-auth/sign-out` | `POST /users/signout` (Luna's) |
| `GET /da-auth/status` | `GET /users/session/status` |
| `DELETE /user/session/{id}` | `DELETE /users/sessions/{id}` |
| `PUT /user/{id}/role` | `PUT /users/{id}/role` |
| `POST /user/password` | `PUT /users/me/password` |
| `POST /user/{id}/email` | `POST /users/{id}/email-changes` |
| `POST /user/verify` | `POST /users/email/verify` |
| `DELETE /user/{id}/contrib-cache` | `DELETE /users/{id}/contributions/cache` |
| `GET,PUT /user/{id}/preference/{key}` | `GET,PUT /users/{id}/preferences/{key}` |
| `GET /user/{id}/pcg/slots` | `GET /users/{id}/personal-guide/slots` |
| `GET,POST /user/{id}/pcg/points` | `GET,POST /users/{id}/personal-guide/points` |
| `POST /user/{id}/pcg/point-history/recalc` | `POST /users/{id}/personal-guide/point-history/recalculation` |
| `GET /user/{id}/avatar-wrap`, `/user/contrib/lazyload/{favme}` *ui* | — |
| `POST /discord-connect/sync/{id}`, `/unlink/{id}` | `POST /users/{id}/discord/sync`, `DELETE /users/{id}/discord` |

### Site
| Old | Canonical |
|---|---|
| `GET,PUT /setting/{key}` | `GET,PUT /settings/{key}` |
| `GET /notif` | `GET /notifications` |
| `POST /notif/{id}/mark-read` | `POST /notifications/{id}/read` |
| `GET /admin/logs/details/{id}` | `GET /admin/logs/{id}` |
| `POST /admin/usefullinks`, `GET,PUT,DELETE /admin/usefullinks/{id}` | `POST /useful-links`, `GET,PUT,DELETE /useful-links/{id}` |
| `POST /admin/usefullinks/reorder` | `PUT /useful-links/order` |
| `DELETE /admin/stat-cache` | unchanged |
| `GET /about/server` | `GET /about/connection` (Luna's payload; the `git` wrapper is gone) |
| `GET /about/upcoming` *ui* | — |

## Order of work

1. Router helper with a method list (done).
2. Move resource by resource (all groups are done — settings, notifications, tags, color groups, color-guide, appearances, posts, events, show relations, users, sessions, preferences, personal guide, Discord, useful links; deviations from the table above are listed under "As built"; before that
   posts/events/show → users/site): route, docblock `path=`, contract tests and client calls in one commit, suite green each time.
3. Close the read gap (see CLAUDE.md "Next") on the new paths.
4. Request-body naming (snake_case → camelCase: `image_url`→`imageUrl`, `show_id`→`showId`, `ponyid`→`appearanceId`, `Colors`→`colors`,
   `CMData`→`cutieMarks`, `APPEARANCE_PAGE` flags removed with the HTML fragments). Renamed in place, client in the same commit.

## Open

- Whether `/show` should also answer as `/shows`.
- `GET /posts/{id}/location` (was `POST .../locate`, read-only) needs a contract note: it changes the verb.

## As built (differences from the tables above)

- `POST /users/me/password` stays a POST (the table said PUT); `POST /users/email/verify` and `POST /users/{id}/email-changes` as planned.
- `DELETE /users/{id}/discord` (unlink) and `POST /users/{id}/discord/sync` have no OpenAPI docblocks yet (the controller never had any).
- `PUT /useful-links/order` replaces `POST /admin/usefullinks/reorder`; `/admin/notices` and `/admin/stat-cache` are unchanged.
- UI-only fragment endpoints got the same prefix rename (`/posts/{id}/lazyload`, `/event-entries/{id}/lazyload`, `/users/{id}/avatar-wrap`,
  `/users/contributions/lazyload/{favme}`) but remain Winterchilla-UI details; `/cg/full` and `/about/upcoming` kept their paths.
- Request field names are camelCase now too (step 4, done in place); `sort_by` stays snake_case because the `/cg/.../full` page shares it.

## Handoff notes (2026-10-01, after the first Luna/Celestia plan reviews)

- **Spec size:** `public/dist/api.json` lists 142+ method+path pairs, one operation each (operation IDs are unique, `tests/ApiSchemaTest.php`
  checks it); older notes that say 135 counted an earlier state.
- **`x-internal` operations** (`CoreUtils::INTERNAL_OPERATIONS`, enforced by `ApiSchemaTest`): the HTML-only endpoints (`/about/upcoming`,
  `/cg/full`, `*/lazyload`, `/posts/{id}/reload`, `/show/{id}/posts`, `/posts/requests/suggestion`, `/notifications`,
  `/users/{id}/avatar-wrap`, `DELETE /users/{id}/contributions/cache`), Winterchilla's own session handling (`GET /users/session/status`,
  `DELETE /users/sessions/{id}`; Luna has tokens and `/users/me`), `DELETE /admin/stat-cache`, and the staff-only e-mail/password flows
  under testing (`POST /users/me/password`, `/users/email/verify`, `/users/{id}/email-changes`: they really are `roleGate('staff')`, not a
  copy-paste error; Luna's signed-link verification and resend flow wins). Luna and Celestia need not implement these.
- **Auth is not in the contract.** Sign-in, token issuing, `/sanctum/csrf-cookie`, `/users/signin`, `/users/oauth/signin/{provider}`, `POST /users`,
  `/users/tokens` and `/about/sleep` belong to Luna (Winterchilla signs in through DeviantArt OAuth pages, not the API). `POST /users/signout`
  is the one auth endpoint both have. The spec has a global default `security: SessionCookie` and public endpoints say `security={}`; read
  "SessionCookie" as "signed in" and map it to Sanctum.
- **`GET /appearances/full`** replaces `/appearances/all` (the path Luna and Celestia call). The Discord sync/unlink endpoints are in the
  spec now (the generator did not scan `DiscordAuthController`).
- **Request bodies:** `application/json` is accepted next to form-encoded (JSON lists of scalars are read as comma lists, nested values
  as JSON strings; `tests/Browser/Api/JsonBodyApiTest.php`). The shared OpenAPI schemas live on `ApiSchemas` now; `Tag`, `EventEntry`,
  `ValueOfUser`, `PreferenceValue`, … no longer inherit a legacy `status` + `pagination` envelope.
- **Added for the Celestia plan:** `relatedAppearances`/`relatedShows` on `GET /appearances/{id}`, `GET /useful-links` (staff), `GET /show/latest`,
  `season`/`episode` filters on `GET /show`, a `post` (`PostItem`) next to `li` in the post write responses, int `id` (+ `idString`) from
  `POST /posts` and `/posts/reservations`, camelCase cutie mark and event docs, `resetPrivKey`, `POST`→`PUT` fixed in the docs of
  `/show/{id}/appearances`.
- **Not provided, on purpose:** a login sessions list (Luna's `/users/tokens`) and a data endpoint for the browser-recognition page (`/about/browser`).
  The goal is a full reimplementation, so features Celestia does not render yet are in the contract anyway: appearance palettes
  (`GET /appearances/{id}/palette?format=json|gpl`) and images (`GET /appearances/{id}/image?type=palette|sprite|preview|facing&format=png|svg`),
  the cutie mark download (`GET /appearances/{id}/cutie-marks/{cutieMarkId}/download`, `?source` is staff only), the staff list of personal guide
  appearances (`GET /admin/pcg-appearances`), tag change history (`GET /appearances/{id}/tag-changes`, staff only; the old page was an unfinished stub, this
  returns the real data) and the profile by DeviantArt UUID (`GET /users/da-uuid/{uuid}`, developer only).
- **Running the contract tests against another server:** see `tests/Browser/Helpers/ApiClient.php` (`CONTRACT_BASE_URL`, `CONTRACT_AUTH=bearer`,
  `CONTRACT_LOGIN_URL`, …) and `scripts/dump-contract-seed.sh` (the seeded data as INSERTs; the cutie mark file and the Redis-cached
  deviations are not in the dump, so the tests that need them will fail on another server until it provides equivalents; Luna should write its
  own tests for the network-dependent flows).
