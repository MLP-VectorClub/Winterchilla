<?php

namespace App\Controllers\API;

use App\Auth;
use App\Controllers\Traits\EventLoaderTrait;
use App\CoreUtils;
use App\Permission;
use App\Response;
use App\Models\Event;
use App\Models\EventEntry;
use App\Models\User;
use OpenApi\Annotations as OA;

class EventAPIController extends APIController {
  use EventLoaderTrait;

  /**
   * @OA\Schema(
   *   schema="EventItem",
   *   type="object",
   *   description="A community collaboration event",
   *   required={"id", "name", "startsAt", "endsAt", "maxEntries", "entryRole", "voteRole", "resultFavMe", "finalizedAt"},
   *   additionalProperties=false,
   *   @OA\Property(property="id", ref="#/components/schemas/OneBasedId"),
   *   @OA\Property(property="name", type="string"),
   *   @OA\Property(property="startsAt", type="string", format="date-time"),
   *   @OA\Property(property="endsAt", type="string", format="date-time"),
   *   @OA\Property(property="maxEntries", type="integer", nullable=true, description="Maximum number of entries a single user may submit"),
   *   @OA\Property(property="entryRole", type="string", nullable=true, description="Minimum role (or special role identifier) required to submit entries"),
   *   @OA\Property(property="voteRole", type="string", nullable=true, description="Minimum role (or special role identifier) required to vote"),
   *   @OA\Property(property="resultFavMe", type="string", nullable=true, description="fav.me ID of the winning entry's deviation, once finalized"),
   *   @OA\Property(property="finalizedAt", type="string", format="date-time", nullable=true)
   * )
   * @OA\Schema(
   *   schema="EventDetails",
   *   type="object",
   *   allOf={
   *     @OA\Schema(ref="#/components/schemas/EventItem"),
   *     @OA\Schema(
   *       type="object",
   *       required={"descriptionSrc", "addedBy", "createdAt", "canEnter", "canVote", "ongoing", "ended", "entries"},
   *       @OA\Property(property="descriptionSrc", type="string", nullable=true, description="Markdown source of the description"),
   *       @OA\Property(property="addedBy", ref="#/components/schemas/PostUser"),
   *       @OA\Property(property="createdAt", type="string", format="date-time"),
   *       @OA\Property(property="canEnter", type="boolean"),
   *       @OA\Property(property="canVote", type="boolean"),
   *       @OA\Property(property="ongoing", type="boolean"),
   *       @OA\Property(property="ended", type="boolean"),
   *       @OA\Property(
   *         property="entries",
   *         type="array",
   *         @OA\Items(
   *           type="object",
   *           required={"id", "title", "submittedBy", "submissionProvider", "submissionId", "previewUrl", "fullUrl", "createdAt"},
   *           @OA\Property(property="id", ref="#/components/schemas/OneBasedId"),
   *           @OA\Property(property="title", type="string"),
   *           @OA\Property(property="submittedBy", ref="#/components/schemas/PostUser"),
   *           @OA\Property(property="submissionProvider", type="string", description="fav.me or sta.sh"),
   *           @OA\Property(property="submissionId", type="string"),
   *           @OA\Property(property="previewUrl", type="string", nullable=true),
   *           @OA\Property(property="fullUrl", type="string", nullable=true),
   *           @OA\Property(property="createdAt", type="string", format="date-time")
   *         )
   *       )
   *     )
   *   }
   * )
   * @OA\Get(
   *   path="/events",
   *   description="List events, newest first",
   *   tags={"events"},
   *   security={},
   *   @OA\Parameter(in="query", name="page", @OA\Schema(type="integer", minimum=1, default=1)),
   *   @OA\Parameter(in="query", name="size", @OA\Schema(type="integer", minimum=1, maximum=50, default=20)),
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(
   *       type="object",
   *       required={"events", "pagination"},
   *       @OA\Property(property="events", type="array", @OA\Items(ref="#/components/schemas/EventItem")),
   *       @OA\Property(property="pagination", ref="#/components/schemas/Pagination")
   *     )
   *   ),
   *   @OA\Response(response="422", description="Invalid query", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse"))
   * )
   */
  public function list() {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    $size = $_GET['size'] ?? 20;
    if (!is_numeric($size) || $size < 1 || $size > 50)
      Response::invalid('size', 'The size must be between 1 and 50.');
    $size = (int)$size;
    $page = $_GET['page'] ?? 1;
    if (!is_numeric($page) || $page < 1)
      Response::invalid('page', 'The page must be at least 1.');
    $page = (int)$page;

    $total = Event::count();
    $events = Event::find('all', ['order' => 'starts_at desc, id desc', 'limit' => $size, 'offset' => ($page - 1) * $size]);

    Response::ok([
      'events' => array_map(fn(Event $e) => self::mapEvent($e), $events),
      'pagination' => [
        'currentPage' => $page,
        'totalPages' => max(1, (int)ceil($total / $size)),
        'totalItems' => $total,
        'itemsPerPage' => $size,
      ],
    ]);
  }

  static function mapEvent(Event $e):array {
    return [
      'id' => $e->id,
      'name' => $e->name,
      'startsAt' => gmdate('c', $e->starts_at->getTimestamp()),
      'endsAt' => gmdate('c', $e->ends_at->getTimestamp()),
      'maxEntries' => $e->max_entries,
      'entryRole' => $e->entry_role,
      'voteRole' => $e->vote_role,
      'resultFavMe' => $e->result_favme,
      'finalizedAt' => $e->finalized_at === null ? null : gmdate('c', $e->finalized_at->getTimestamp()),
    ];
  }

  private static function mapUser(?User $u):?array {
    return $u === null ? null : ['id' => $u->id, 'name' => $u->name];
  }

  /**
   * @OA\Get(
   *   path="/events/{id}",
   *   description="Fetch an event with its entries",
   *   tags={"events"},
   *   security={},
   *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\Response(response="200", description="OK", @OA\JsonContent(ref="#/components/schemas/EventDetails")),
   *   @OA\Response(response="404", description="Event not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
   * )
   * @OA\Post(
   *   path="/events",
   *   description="Create a new event. Requires staff permissions. Currently always fails. Requires the **staff** role.",
   *   tags={"events"},
   *   @OA\RequestBody(
   *     required=true,
   *     @OA\JsonContent(
   *       type="object",
   *       @OA\Property(property="name", type="string"),
   *       @OA\Property(property="entryRole", type="string"),
   *       @OA\Property(property="voteRole", type="string"),
   *       @OA\Property(property="startsAt", type="string", format="date-time"),
   *       @OA\Property(property="endsAt", type="string", format="date-time"),
   *       @OA\Property(property="maxEntries", type="integer", nullable=true),
   *       @OA\Property(property="descSrc", type="string", nullable=true),
   *     )
   *   ),
   *   @OA\Response(response="401", description="Not signed in", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="403", description="Insufficient permissions", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="501", description="creating events is currently disabled", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   * )
   * @OA\Put(
   *   path="/events/{id}",
   *   description="Edit an existing event. Requires staff permissions. Currently always fails. Requires the **staff** role.",
   *   tags={"events"},
   *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\RequestBody(
   *     required=true,
   *     @OA\JsonContent(
   *       type="object",
   *       @OA\Property(property="name", type="string"),
   *       @OA\Property(property="entryRole", type="string"),
   *       @OA\Property(property="voteRole", type="string"),
   *       @OA\Property(property="startsAt", type="string", format="date-time"),
   *       @OA\Property(property="endsAt", type="string", format="date-time"),
   *       @OA\Property(property="maxEntries", type="integer", nullable=true),
   *       @OA\Property(property="descSrc", type="string", nullable=true),
   *     )
   *   ),
   *   @OA\Response(response="401", description="Not signed in", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="403", description="Insufficient permissions", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="501", description="editing events is currently disabled", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="404", description="Event not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   * )
   * @OA\Delete(
   *   path="/events/{id}",
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
    if ($this->action === 'GET') {
      $this->load_event($params);

      Response::ok(self::mapEvent($this->event) + [
        'descriptionSrc' => $this->event->desc_src,
        'addedBy' => self::mapUser($this->event->creator),
        'createdAt' => gmdate('c', $this->event->created_at->getTimestamp()),
        'canEnter' => $this->event->checkCanEnter(),
        'canVote' => $this->event->checkCanVote(),
        'ongoing' => $this->event->isOngoing(),
        'ended' => $this->event->hasEnded(),
        'entries' => array_map(fn(EventEntry $e) => [
          'id' => $e->id,
          'title' => $e->title,
          'submittedBy' => self::mapUser($e->submitter),
          'submissionProvider' => $e->sub_prov,
          'submissionId' => $e->sub_id,
          'previewUrl' => $e->prev_thumb,
          'fullUrl' => $e->prev_full,
          'createdAt' => gmdate('c', $e->created_at->getTimestamp()),
        ], $this->event->entries),
      ]);
    }

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
   *   path="/events/{id}/finalize",
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
   * @OA\Post(
   *   path="/events/{id}/entries/check",
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
