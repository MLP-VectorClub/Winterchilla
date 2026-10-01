<?php

namespace App\Controllers;

use App\Controllers\Traits\EventLoaderTrait;
use App\CoreUtils;
use App\Models\Event;
use OpenApi\Annotations as OA;

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
