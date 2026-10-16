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

class EventAPIController extends APIController {
  use EventLoaderTrait;

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

  public function finalize() {
    if (Permission::insufficient('staff'))
      Response::denied();

    Response::error(501, "Events can't be finalized currently.");
  }

  /**
   * This method checks whether the current user can submit any more entries
   */
  public function checkEntries() {
    if (!Auth::$signed_in)
      Response::error(401);

    Response::error(501, "Events can't receive entries currently.");
  }

}
