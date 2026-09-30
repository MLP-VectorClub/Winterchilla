<?php

namespace App\Controllers\API;

use App\CoreUtils;
use App\Permission;
use App\Regexes;
use App\Response;
use App\ShowHelper;
use App\Tags;
use OpenApi\Annotations as OA;

class ConfigAPIController extends APIController {
  /**
   * @OA\Schema(
   *   schema="RegexPattern",
   *   type="object",
   *   description="A regular expression in the form JavaScript's RegExp constructor takes it",
   *   required={"source", "flags"},
   *   additionalProperties=false,
   *   @OA\Property(property="source", type="string"),
   *   @OA\Property(property="flags", type="string", example="i")
   * )
   * @OA\Get(
   *   path="/config",
   *   description="Constants, validation patterns and client settings that the front end needs before it can render forms. The same for every visitor.",
   *   security={},
   *   tags={"configuration"},
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(
   *       type="object",
   *       required={"tagTypes", "roles", "showTypes", "maxUploadSize", "patterns", "wsServerHost", "discordInviteLink"},
   *       additionalProperties=false,
   *       @OA\Property(property="tagTypes", type="object", additionalProperties=@OA\AdditionalProperties(type="string"), description="Tag type key to label"),
   *       @OA\Property(property="roles", type="object", additionalProperties=@OA\AdditionalProperties(type="string"), description="Role key to label"),
   *       @OA\Property(property="showTypes", type="object", additionalProperties=@OA\AdditionalProperties(type="string"), description="Show type key to label"),
   *       @OA\Property(property="maxUploadSize", type="string", example="2MB"),
   *       @OA\Property(
   *         property="patterns",
   *         type="object",
   *         required={"printableAscii", "hexColor", "username", "episodeTitle"},
   *         additionalProperties=false,
   *         @OA\Property(property="printableAscii", ref="#/components/schemas/RegexPattern"),
   *         @OA\Property(property="hexColor", ref="#/components/schemas/RegexPattern"),
   *         @OA\Property(property="username", ref="#/components/schemas/RegexPattern"),
   *         @OA\Property(property="episodeTitle", ref="#/components/schemas/RegexPattern")
   *       ),
   *       @OA\Property(property="wsServerHost", type="string", nullable=true),
   *       @OA\Property(property="discordInviteLink", type="string")
   *     )
   *   )
   * )
   */
  public function get():void {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    header('Cache-Control: public, max-age=300');
    Response::ok([
      'tagTypes' => Tags::TAG_TYPES,
      'roles' => Permission::ROLES_ASSOC,
      'showTypes' => ShowHelper::VALID_TYPES,
      'maxUploadSize' => CoreUtils::getMaxUploadSize(),
      'patterns' => [
        'printableAscii' => ['source' => PRINTABLE_ASCII_PATTERN, 'flags' => ''],
        'hexColor' => Regexes::$hex_color->toJs(),
        'username' => Regexes::$username->toJs(),
        'episodeTitle' => Regexes::$ep_title->toJs(),
      ],
      'wsServerHost' => CoreUtils::env('TEST_MODE') ? null : CoreUtils::env('WS_SERVER_HOST'),
      'discordInviteLink' => DISCORD_INVITE_LINK,
    ]);
  }
}
