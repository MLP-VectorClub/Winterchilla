<?php

namespace App\Controllers\API;

use App\Auth;
use App\Controllers\Traits\EventLoaderTrait;
use App\CoreUtils;
use App\Permission;
use App\Response;
use OpenApi\Annotations as OA;

class EventAPIController extends APIController {
  use EventLoaderTrait;

  /**
   * @OA\Get(
   *   path="/event/{id}",
   *   description="Fetch the details of an event. Requires staff permissions. Currently always fails. Requires the **staff** role.",
   *   tags={"events"},
   *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\Response(response="401", description="Not signed in", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="403", description="Insufficient permissions", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="501", description="Fetching event details is currently disabled", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   * )
   * @OA\Post(
   *   path="/event",
   *   description="Create a new event. Requires staff permissions. Currently always fails. Requires the **staff** role.",
   *   tags={"events"},
   *   @OA\RequestBody(
   *     required=true,
   *     @OA\JsonContent(
   *       type="object",
   *       @OA\Property(property="name", type="string"),
   *       @OA\Property(property="entry_role", type="string"),
   *       @OA\Property(property="vote_role", type="string"),
   *       @OA\Property(property="starts_at", type="string", format="date-time"),
   *       @OA\Property(property="ends_at", type="string", format="date-time"),
   *       @OA\Property(property="max_entries", type="integer", nullable=true),
   *       @OA\Property(property="desc_src", type="string", nullable=true),
   *     )
   *   ),
   *   @OA\Response(response="401", description="Not signed in", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="403", description="Insufficient permissions", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="501", description="creating events is currently disabled", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   * )
   * @OA\Put(
   *   path="/event/{id}",
   *   description="Edit an existing event. Requires staff permissions. Currently always fails. Requires the **staff** role.",
   *   tags={"events"},
   *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\RequestBody(
   *     required=true,
   *     @OA\JsonContent(
   *       type="object",
   *       @OA\Property(property="name", type="string"),
   *       @OA\Property(property="entry_role", type="string"),
   *       @OA\Property(property="vote_role", type="string"),
   *       @OA\Property(property="starts_at", type="string", format="date-time"),
   *       @OA\Property(property="ends_at", type="string", format="date-time"),
   *       @OA\Property(property="max_entries", type="integer", nullable=true),
   *       @OA\Property(property="desc_src", type="string", nullable=true),
   *     )
   *   ),
   *   @OA\Response(response="401", description="Not signed in", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="403", description="Insufficient permissions", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="501", description="editing events is currently disabled", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="404", description="Event not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   * )
   * @OA\Delete(
   *   path="/event/{id}",
   *   description="Delete an existing event. Requires staff permissions. Currently always fails. Requires the **staff** role.",
   *   tags={"events"},
   *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\Response(response="401", description="Not signed in", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="403", description="Insufficient permissions", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="501", description="deleting events is currently disabled", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="404", description="Event not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   * )
   */
  public function api($params) {
    if (Permission::insufficient('staff'))
      Response::denied();

    // These operations are disabled for now, hence 501 rather than a permission error
    switch ($this->action){
      case 'GET':
        Response::error(501, 'Fetching event details is currently not allowed.');
      break;
      case 'POST':
      case 'PUT':
        Response::error(501, ($this->creating ? 'Creating new' : 'Editing existing').' events is currently not allowed.');
      break;
      case 'DELETE':
        Response::error(501, 'Deleting events is currently not allowed.');
      break;
      default:
        CoreUtils::notAllowed();
    }
  }

  /**
   * @OA\Post(
   *   path="/event/{id}/finalize",
   *   description="Finalize an event, locking in the winning entry. Requires staff permissions. Currently always fails. Requires the **staff** role.",
   *   tags={"events"},
   *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\Response(response="401", description="Not signed in", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="403", description="Insufficient permissions", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="501", description="finalizing events is currently disabled", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   * )
   */
  public function finalize() {
    if (Permission::insufficient('staff'))
      Response::denied();

    Response::error(501, "Events can't be finalized currently.");
  }

  /**
   * This method checks whether the current user can submit any more entries
   *
   * @OA\Get(
   *   path="/event/{id}/check-entries",
   *   description="Check whether the currently logged in user can submit any more entries to this event. Currently always fails.",
   *   tags={"events"},
   *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\Response(response="401", description="Not signed in", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="403", description="Insufficient permissions", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="501", description="receiving entries is currently disabled", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   * )
   */
  public function checkEntries() {
    if (!Auth::$signed_in)
      Response::error(401);

    Response::error(501, "Events can't receive entries currently.");
  }

}
