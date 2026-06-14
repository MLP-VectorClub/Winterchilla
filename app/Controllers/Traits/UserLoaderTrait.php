<?php

namespace App\Controllers\Traits;

use App\CoreUtils;
use App\Models\User;

trait UserLoaderTrait {
  /** @var User|null */
  private ?User $user;

  private function load_user($params) {
    if (!isset($params['id']))
      CoreUtils::notFound();

    $this->user = User::find($params['id']);

    if (empty($this->user))
      CoreUtils::notFound();
  }
}
