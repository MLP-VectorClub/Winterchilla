<?php

namespace App\Controllers;

use App\Controllers\Traits\EventLoaderTrait;
use App\CoreUtils;
use App\Models\Event;
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
  use EventLoaderTrait;

  public function __construct() {
    parent::__construct();
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
}
