<?php

namespace App\Controllers\Traits;

use App\Auth;
use App\CGUtils;
use App\CoreUtils;
use App\Models\Appearance;
use App\Models\User;
use App\Response;

trait ColorGuideAccessTrait {
  /** @var bool */
  protected bool $appearance_page = false;
  /** @var string|null Guide identifier or null for personal color guides */
  protected ?string $guide = 'pony';

  protected ?User $owner = null;
  protected bool $is_owner = false;

  protected ?Appearance $appearance;

  protected function _initAppearancePageState():void {
    $this->appearance_page = isset($_REQUEST['APPEARANCE_PAGE']);
    if (isset($_REQUEST['owner_id']))
      $this->guide = null;
  }

  protected function _initialize($params):void {
    if (!empty($params['guide']) && isset(CGUtils::GUIDE_MAP[$params['guide']])) {
      $this->guide = $params['guide'];
    }
    $user_id_set = isset($params['user_id']);

    if ($user_id_set){
      $this->owner = User::find($params['user_id']);
      if (empty($this->owner))
        CoreUtils::notFound();
      $this->guide = null;
    }
    $this->is_owner = $user_id_set ? (Auth::$signed_in && Auth::$user->id === $this->owner->id) : false;

    if ($user_id_set)
      $this->path = "{$this->owner->toURL()}/cg";
    else $this->path = rtrim("/cg/{$this->guide}", '/');
  }

  public function load_appearance($params, bool $set_properties = true):void {
    if (!isset($params['id']))
      Response::fail('Missing appearance ID');
    $this->appearance = Appearance::find($params['id']);
    if (empty($this->appearance))
      CoreUtils::notFound();
    if (!$set_properties)
      return;

    if ($this->appearance->owner_id !== null) {
      $this->guide = null;
      $this->owner = $this->appearance->owner;
    }
    if ($this->guide === null){
      $owner_path = $this->appearance->owner->toURL();
      $this->path = "$owner_path/cg";
      $this->is_owner = Auth::$signed_in && ($this->appearance->owner_id === Auth::$user->id);
    }
    else if ($this->guide !== $this->appearance->guide){
      $this->guide = $this->appearance->guide;
      $this->path = '/cg/eqg';
    }
  }
}
