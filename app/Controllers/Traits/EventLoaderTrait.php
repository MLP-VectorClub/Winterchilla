<?php

namespace App\Controllers\Traits;

use App\CoreUtils;
use App\Models\Event;

trait EventLoaderTrait {
  protected ?Event $event;

  protected function load_event($params) {
    if (empty($params['id']))
      CoreUtils::notFound();

    $this->event = Event::find($params['id']);
    if (empty($this->event))
      CoreUtils::notFound();
  }
}
