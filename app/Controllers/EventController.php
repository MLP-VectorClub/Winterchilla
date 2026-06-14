<?php

namespace App\Controllers;

use App\Auth;
use App\CoreUtils;
use App\Models\Event;
use App\Permission;
use App\Response;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *   schema="Event",
 *   type="object",
 *   description="A community collaboration event",
 *   required={
 *     "id",
 *     "name",
 *     "entry_role",
 *     "vote_role",
 *     "starts_at",
 *     "ends_at",
 *     "added_by",
 *     "created_at",
 *   },
 *   additionalProperties=false,
 *   @OA\Property(property="id", ref="#/components/schemas/OneBasedId"),
 *   @OA\Property(property="max_entries", type="integer", nullable=true, description="Maximum number of entries a single user may submit"),
 *   @OA\Property(property="name", type="string"),
 *   @OA\Property(property="entry_role", type="string", description="Minimum role (or special role identifier) required to submit entries"),
 *   @OA\Property(property="vote_role", type="string", description="Minimum role (or special role identifier) required to vote on entries"),
 *   @OA\Property(property="starts_at", type="string", format="date-time"),
 *   @OA\Property(property="ends_at", type="string", format="date-time"),
 *   @OA\Property(property="added_by", ref="#/components/schemas/OneBasedId"),
 *   @OA\Property(property="created_at", type="string", format="date-time"),
 *   @OA\Property(property="desc_src", type="string", nullable=true, description="Source markdown of the event description"),
 *   @OA\Property(property="desc_rend", type="string", nullable=true, description="Rendered HTML of the event description"),
 *   @OA\Property(property="result_favme", type="string", nullable=true, description="fav.me ID of the winning entry's deviation, once finalized"),
 *   @OA\Property(property="finalized_by", type="integer", nullable=true, description="ID of the staff member who finalized the event"),
 *   @OA\Property(property="finalized_at", type="string", format="date-time", nullable=true),
 * )
 */
class EventController extends Controller {
  public function __construct() {
    parent::__construct();
  }

  protected ?Event $event;

  protected function load_event($params) {
    if (empty($params['id']))
      CoreUtils::notFound();

    $this->event = Event::find($params['id']);
    if (empty($this->event))
      CoreUtils::notFound();
  }

  public function view($params) {
    $this->load_event($params);

    $heading = $this->event->name;

    CoreUtils::fixPath($this->event->toURL());

    CoreUtils::loadPage(__METHOD__, [
      'heading' => $heading,
      'title' => "$heading - Collaboration Event",
      'css' => [true],
      'js' => [true],
      'import' => [
        'event' => $this->event,
      ],
    ]);
  }

  public function list() {
    CoreUtils::fixPath('/events');
    $heading = 'Events Archive';

    $events = Event::find('all');

    CoreUtils::loadPage(__METHOD__, [
      'title' => $heading,
      'heading' => $heading,
      'js' => ['paginate'],
      'css' => [true],
      'import' => [
        'events' => $events,
      ],
    ]);
  }

  /**
   * @OA\Get(
   *   path="/event/{id}",
   *   description="Fetch the details of an event. Requires staff permissions. Currently always fails. Requires the **staff** role.",
   *   tags={"events"},
   *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(
   *       allOf={
   *         @OA\Schema(ref="#/components/schemas/ServerResponse"),
   *         @OA\Schema(type="object", required={"event"}, additionalProperties=false,
   *           @OA\Property(property="event", ref="#/components/schemas/Event")
   *         )
   *       }
   *     )
   *   ),
   *   @OA\Response(response="403", description="Insufficient permissions, or fetching event details is currently disallowed",
   *     @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
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
   *   @OA\Response(response="200", description="OK", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="403", description="Insufficient permissions, or creating events is currently disallowed",
   *     @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
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
   *   @OA\Response(response="200", description="OK", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="403", description="Insufficient permissions, or editing events is currently disallowed",
   *     @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="404", description="Event not found", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   * )
   * @OA\Delete(
   *   path="/event/{id}",
   *   description="Delete an existing event. Requires staff permissions. Currently always fails. Requires the **staff** role.",
   *   tags={"events"},
   *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\Response(response="200", description="OK", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="403", description="Insufficient permissions, or deleting events is currently disallowed",
   *     @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="404", description="Event not found", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   * )
   */
  public function api($params) {
    if (Permission::insufficient('staff'))
      Response::fail();

    switch ($this->action){
      case 'GET':
        Response::fail('Fetching event details is currently not allowed.');
      break;
      case 'POST':
      case 'PUT':
        Response::fail(($this->creating ? 'Creating new' : 'Editing existing').' events is currently not allowed.');
      break;
      case 'DELETE':
        Response::fail('Deleting events is currently not allowed.');
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
   *   @OA\Response(response="200", description="OK", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="403", description="Insufficient permissions, or finalizing events is currently disallowed",
   *     @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   * )
   */
  public function finalize() {
    if (Permission::insufficient('staff'))
      Response::fail();

    Response::fail("Events can't be finalized currently.");
  }

  /**
   * This method checks whether the current user can submit any more entries
   *
   * @OA\Get(
   *   path="/event/{id}/check-entries",
   *   description="Check whether the currently logged in user can submit any more entries to this event. Currently always fails.",
   *   tags={"events"},
   *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\Response(response="200", description="OK", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="403", description="Not signed in, or receiving entries is currently disallowed for this event",
   *     @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   * )
   */
  public function checkEntries() {
    if (!Auth::$signed_in)
      Response::fail();

    Response::fail("Events can't receive entries currently.");
  }

}
