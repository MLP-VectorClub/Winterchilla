# API path alignment with Luna (decided 2026-09-30)

**Decision:** Winterchilla's write API moves to Luna's resource-oriented paths. The old paths stay as aliases while both front ends
run, and the Winterchilla client moves to the canonical paths last. Canonical paths are what the OpenAPI spec documents (and so what
Celestia's generated types are named after); aliases are undocumented and get removed once Winterchilla's own client is retired.

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
share a path by method. Canonical paths collide with Luna-mirroring ones (`GET /appearances` is the public list, `POST /appearances`
creates; `GET /appearances/{id}/sprite` is public, `POST`/`DELETE` manage). The registration helper therefore takes an optional
method list — `$api_endpoint($path, $target, methods: 'GET')` — and aliases are registered next to their canonical route
(`aliases: ['/cg/tag/[i:id]?']`). A method with no route answers the router's 404 instead of the controllers' 405; the contract tests
pin what is expected. Canonical `[i:id]?` optional-parameter routes are split into collection and item routes so the docs can describe
them separately.

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
| `GET /cg/full` *ui* | — (`GET /appearances/all` is the data equivalent) |
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
| `GET /about/server` | unchanged (Luna has `/about/connection`; see "open") |
| `GET /about/upcoming` *ui* | — |

## Order of work

1. Router helper with methods + aliases; a generated test that every old path still answers like its canonical twin.
2. Move resource by resource (appearances → color groups/tags → posts/events/show → users/site): canonical route, docblock `path=`,
   contract tests on the canonical path, alias for the old path. One commit per resource, suite green each time.
3. Close the read gap (see CLAUDE.md "Next") on the new paths.
4. Request-body naming (snake_case → camelCase: `image_url`→`imageUrl`, `show_id`→`showId`, `ponyid`→`appearanceId`, `Colors`→`colors`,
   `CMData`→`cutieMarks`, `APPEARANCE_PAGE` flags removed with the HTML fragments). Accept both spellings during the transition.
5. Move Winterchilla's own client to the canonical paths; later remove the aliases.

## Open

- `/about/server` vs Luna's `/about/connection` (same purpose, different payloads): align when Celestia's connection page is ported.
- Whether `/show` should also answer as `/shows`.
- `GET /posts/{id}/location` (was `POST .../locate`, read-only) needs a contract note: it changes the verb.
