<?php

namespace App\Controllers\API;

use OpenApi\Annotations as OA;

// Shared OpenAPI definitions (info, tags, security scheme and common schemas). They live on a class of their own, not on
// APIController: swagger-php merges a parent class's schema annotations into every subclass that declares a schema, which used
// to make `Tag`, `EventEntry`, `ValueOfUser`, … inherit a legacy envelope with a required `status`. Keep prose out of the
// docblock below: swagger-php would use it as the description of the first annotation.
/**
 * @OA\OpenApi(
 *   @OA\Info(
 *     title="MLP Vector Club API",
 *     version="0.1",
 *     description="A temporary API which allows programmatic access to some existing features of the [MLPVector.Club](https://mlpvector.club) website. Will be superseded by the [next version](https://api.mlpvector.club) whenever its development is finished.",
 *     @OA\License(name="MIT"),
 *     @OA\Contact(name="WentTheFox", url="https://went.tf"),
 *   ),
 *   @OA\Server(url="/api/v0", description="Unstable API"),
 *   @OA\Tag(name="authentication", description="Endpoints related to getting a user logged in or out, as well as checking logged in status"),
 *   @OA\Tag(name="color guide", description="Endpoints related to the color guide section of the site"),
 *   @OA\Tag(name="appearances", description="Working with entries in the color guide"),
 *   @OA\Tag(name="tags", description="Working with color guide tags"),
 *   @OA\Tag(name="color groups", description="Working with color groups belonging to color guide appearances"),
 *   @OA\Tag(name="shows", description="Working with shows/episodes and their associated posts"),
 *   @OA\Tag(name="posts", description="Working with art posts, requests and reservations"),
 *   @OA\Tag(name="events", description="Working with community events and their entries"),
 *   @OA\Tag(name="users", description="Working with user accounts, sessions and preferences"),
 *   @OA\Tag(name="personal color guide", description="Endpoints related to the Personal Color Guide (PCG) program"),
 *   @OA\Tag(name="notifications", description="Endpoints related to user notifications"),
 *   @OA\Tag(name="settings", description="Endpoints related to site-wide settings"),
 *   @OA\Tag(name="admin", description="Endpoints restricted to staff members for administrative purposes"),
 *   @OA\Tag(name="server info", description="For diagnostic or informational data"),
 *   security={
 *     {"SessionCookie": {}}
 *   }
 * )
 * @OA\SecurityScheme(
 *   securityScheme="SessionCookie",
 *   type="apiKey",
 *   in="cookie",
 *   name="access",
 *   description="A long-lived (1 year), HTTP-only session token issued after signing in via [DeviantArt OAuth](#tag/authentication). It is sent automatically by browsers as a cookie; it cannot be supplied as a header. Endpoints that don't require an authenticated user will work without this cookie, and will treat the request as coming from a guest."
 * )
 * @OA\SecurityScheme(
 *   securityScheme="CSRFToken",
 *   type="apiKey",
 *   in="query",
 *   name="CSRF_TOKEN",
 *   description="Required for all non-GET requests. The server sets a `CSRF_TOKEN` cookie on every response; its value must be echoed back as a `CSRF_TOKEN` request parameter (query string or body) on subsequent state-changing (POST/PUT/DELETE) requests, or the request will fail with `419` (CSRF token mismatch). This is purely an anti-CSRF measure and is unrelated to user authentication."
 * )
 * @OA\Schema(
 *   schema="Pagination",
 *   type="object",
 *   required={"currentPage", "totalPages", "totalItems", "itemsPerPage"},
 *   additionalProperties=false,
 *   @OA\Property(property="currentPage", type="integer", minimum=1),
 *   @OA\Property(property="totalPages", type="integer", minimum=1),
 *   @OA\Property(property="totalItems", type="integer", minimum=0),
 *   @OA\Property(property="itemsPerPage", type="integer", minimum=1)
 * )
 * @OA\Schema(
 *   schema="ErrorResponse",
 *   type="object",
 *   required={"message"},
 *   additionalProperties=true,
 *   @OA\Property(property="message", type="string", description="An error message describing what caused the request to fail", example="The given data was invalid.")
 * )
 * @OA\Schema(
 *   schema="ValidationErrorResponse",
 *   allOf={
 *     @OA\Schema(
 *       type="object",
 *       required={"errors"},
 *       @OA\Property(
 *         property="errors",
 *         type="object",
 *         description="A map containing error messages for each field that did not pass validation",
 *         minProperties=1,
 *         @OA\AdditionalProperties(type="array", minItems=1, @OA\Items(type="string"))
 *       )
 *     ),
 *     @OA\Schema(ref="#/components/schemas/ErrorResponse")
 *   }
 * )
 * @OA\Schema(
 *   schema="PageNumber",
 *   type="integer",
 *   minimum=1,
 *   default=1,
 *   description="A query parameter used for specifying which page is currently being displayed"
 * )
 * @OA\Schema(
 *   schema="File",
 *   type="string",
 *   format="binary",
 * )
 * @OA\Schema(
 *   schema="SVGFile",
 *   type="string",
 *   format="svg",
 * )
 * @OA\Schema(
 *   schema="QueryString",
 *   type="string",
 *   default=""
 * )
 * @OA\Schema(
 *   schema="OneBasedId",
 *   type="integer",
 *   minimum=1,
 *   example=1
 * )
 * @OA\Schema(
 *   schema="ZeroBasedId",
 *   type="integer",
 *   minimum=0,
 *   example=1
 * )
 * @OA\Schema(
 *   schema="UserPrefs",
 *   type="object",
 *   description="The effective preference values (every key is optional when `keys[]` limits the result). The flags are booleans; defaults are in brackets in the descriptions below.",
 *   additionalProperties=false,
 *   @OA\Property(property="cg_itemsperpage", type="integer", minimum=7, maximum=20, description="Appearances per page in the color guide [7]"),
 *   @OA\Property(property="cg_hidesynon", type="boolean"),
 *   @OA\Property(property="cg_hideclrinfo", type="boolean"),
 *   @OA\Property(property="cg_fulllstprev", type="boolean"),
 *   @OA\Property(property="cg_nutshell", type="boolean"),
 *   @OA\Property(property="p_hidediscord", type="boolean"),
 *   @OA\Property(property="p_hidepcg", type="boolean"),
 *   @OA\Property(property="p_homelastep", type="boolean"),
 *   @OA\Property(property="ep_noappprev", type="boolean"),
 *   @OA\Property(property="ep_revstepbtn", type="boolean"),
 *   @OA\Property(property="a_pcgearn", type="boolean"),
 *   @OA\Property(property="a_pcgmake", type="boolean"),
 *   @OA\Property(property="a_pcgsprite", type="boolean"),
 *   @OA\Property(property="a_postreq", type="boolean"),
 *   @OA\Property(property="a_postres", type="boolean"),
 *   @OA\Property(property="a_reserve", type="boolean"),
 *   @OA\Property(property="p_vectorapp", type="string", description="The vector app shown next to the user's name, empty for none"),
 *   @OA\Property(property="cg_defaultguide", type="string", nullable=true, enum={"pony", "eqg", null}, description="Preferred color guide [null]"),
 *   @OA\Property(property="pcg_slots", type="integer", nullable=true, description="Personal guide slots granted by staff [null]")
 * )
 */
final class ApiSchemas {
}
