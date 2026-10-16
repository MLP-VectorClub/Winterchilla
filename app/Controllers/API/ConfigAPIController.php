<?php

namespace App\Controllers\API;

use App\CoreUtils;
use App\Permission;
use App\Regexes;
use App\Response;
use App\ShowHelper;
use App\Tags;

class ConfigAPIController extends APIController {
  public function get():void {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    header('Cache-Control: public, max-age=300');
    Response::ok([
      'tagTypes' => Tags::TAG_TYPES,
      'roles' => Permission::ROLES_ASSOC,
      'showTypes' => ShowHelper::VALID_TYPES,
      'vectorApps' => CoreUtils::VECTOR_APPS,
      'maxUploadSize' => CoreUtils::getMaxUploadSize(),
      'patterns' => [
        'printableAscii' => ['source' => PRINTABLE_ASCII_PATTERN, 'flags' => ''],
        'hexColor' => Regexes::$hex_color->toJs(),
        'username' => Regexes::$username->toJs(),
        'episodeTitle' => Regexes::$ep_title->toJs(),
      ],
      'wsServerHost' => CoreUtils::wsServerHost(),
      'discordInviteLink' => DISCORD_INVITE_LINK,
      'readOnly' => CoreUtils::readOnly(),
    ]);
  }
}
