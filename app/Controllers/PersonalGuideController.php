<?php

namespace App\Controllers;

use App\Appearances;
use App\Auth;
use App\CGUtils;
use App\CoreUtils;
use App\Input;
use App\Models\PCGPointGrant;
use App\Pagination;
use App\Permission;
use App\Regexes;
use App\Response;
use App\UserPrefs;
use OpenApi\Annotations as OA;
use function count;

class PersonalGuideController extends ColorGuideController {
  use UserLoaderTrait;

  public function list($params) {
    $this->_initialize($params);

    if (!$this->owner->canVisitorSeePCG())
      CoreUtils::noPerm();

    $AppearancesPerPage = UserPrefs::get('cg_itemsperpage');
    $_EntryCount = $this->owner->getPCGAppearanceCount();

    $pagination = new Pagination($this->path, $AppearancesPerPage, $_EntryCount);
    $appearances = $this->owner->getPCGAppearances($pagination);

    CoreUtils::fixPath($pagination->toURI());
    $heading = CoreUtils::posess($this->owner->name).' Personal Color Guide';
    $title = "Page {$pagination->getPage()} - $heading";

    $is_owner = $this->is_owner;
    $owner_or_staff = $is_owner || Permission::sufficient('staff');

    $settings = [
      'title' => $title,
      'heading' => $heading,
      'css' => ['pages/colorguide/guide'],
      'js' => ['jquery.ctxmenu', 'pages/colorguide/guide', 'paginate'],
      'import' => [
        'appearances' => $appearances,
        'pagination' => $pagination,
        'user' => $this->owner,
        'is_owner' => $is_owner,
        'owner_or_staff' => $owner_or_staff,
        'max_upload_size' => CoreUtils::getMaxUploadSize(),
      ],
    ];
    if ($owner_or_staff){
      self::_appendManageAssets($settings);
      $settings['import']['hex_color_regex'] = Regexes::$hex_color;
    }
    CoreUtils::loadPage('UserController::colorguide', $settings);
  }

  public function pointHistory($params) {
    $this->_initialize($params);

    if (!$this->is_owner && Permission::insufficient('staff'))
      CoreUtils::noPerm();

    $EntriesPerPage = 20;
    $_EntryCount = $this->owner->getPCGSlotHistoryEntryCount();

    $pagination = new Pagination("{$this->path}/point-history", $EntriesPerPage, $_EntryCount);
    $entries = $this->owner->getPCGSlotHistoryEntries($pagination);
    if (count($entries) === 0){
      $this->owner->recalculatePCGSlotHistroy();
      $entries = $this->owner->getPCGSlotHistoryEntries($pagination);
    }

    CoreUtils::fixPath($pagination->toURI());
    $heading = ($this->is_owner ? 'Your' : CoreUtils::posess($this->owner->name)).' Point History';
    $title = "Page {$pagination->getPage()} - $heading";

    $js = ['paginate'];
    if (Permission::sufficient('staff'))
      $js[] = true;
    CoreUtils::loadPage('UserController::pcgSlots', [
      'title' => $title,
      'heading' => $heading,
      'css' => [true],
      'js' => $js,
      'import' => [
        'entries' => $entries,
        'pagination' => $pagination,
        'user' => $this->owner,
        'is_owner' => $this->is_owner,
        'pcg_slot_history' => CGUtils::getPCGSlotHistoryHTML($entries),
      ],
    ]);
  }

  /**
   * @OA\Post(
   *   path="/user/{id}/pcg/point-history/recalc",
   *   description="Recalculates the Personal Color Guide point history for the specified user. Requires developer permission.",
   *   tags={"personal color guide"},
   *   @OA\Parameter(
   *     name="id",
   *     in="path",
   *     required=true,
   *     @OA\Schema(ref="#/components/schemas/OneBasedId")
   *   ),
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(ref="#/components/schemas/ServerResponse")
   *   ),
   *   @OA\Response(
   *     response="403",
   *     description="Insufficient permission (developer required)",
   *     @OA\JsonContent(ref="#/components/schemas/ServerResponse")
   *   ),
   *   @OA\Response(
   *     response="404",
   *     description="The specified user does not exist",
   *     @OA\JsonContent(ref="#/components/schemas/ServerResponse")
   *   )
   * )
   */
  public function pointRecalc($params) {
    if ($this->action !== 'POST')
      CoreUtils::notAllowed();

    if (Permission::insufficient('developer'))
      CoreUtils::noPerm();

    $this->load_user($params);

    $this->user->recalculatePCGSlotHistroy();

    Response::done();
  }

  /**
   * @OA\Get(
   *   path="/user/{id}/pcg/slots",
   *   description="Checks whether the specified user is eligible to add a new Personal Color Guide appearance. Fails if PCG appearance creation is disabled for the user, or if they have fewer than 10 available slots.",
   *   tags={"personal color guide"},
   *   @OA\Parameter(
   *     name="id",
   *     in="path",
   *     required=true,
   *     @OA\Schema(ref="#/components/schemas/OneBasedId")
   *   ),
   *   @OA\Response(
   *     response="200",
   *     description="The user is allowed to add a new PCG appearance",
   *     @OA\JsonContent(ref="#/components/schemas/ServerResponse")
   *   ),
   *   @OA\Response(
   *     response="400",
   *     description="PCG appearance creation is disabled for this user, or the user has no available slots left",
   *     @OA\JsonContent(ref="#/components/schemas/ServerResponse")
   *   ),
   *   @OA\Response(
   *     response="404",
   *     description="The specified user does not exist",
   *     @OA\JsonContent(ref="#/components/schemas/ServerResponse")
   *   )
   * )
   */
  public function slotsApi($params) {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    switch ($this->action){
      case 'GET':
        $this->load_user($params);

        if (!UserPrefs::get('a_pcgmake', $this->user))
          Response::fail(Appearances::PCG_APPEARANCE_MAKE_DISABLED);

        $avail = $this->user->getPCGAvailablePoints(false);
        if ($avail < 10){
          $sameUser = $this->user->id === Auth::$user->id;
          $You = $sameUser ? 'You' : $this->user->name;
          $nave = $sameUser ? 'have' : 'has';
          $you = $sameUser ? 'you' : 'they';
          $cont = Permission::sufficient('member', $this->user->role)
            ? ", but $you can always fulfill some requests"
            : '. '.(
            $sameUser
              ? 'Consider joining the group and fulfilling some requests on our site'
              : 'They should join the group and fulfill some requests on our site'
            );
          Response::fail("$You $nave no available slots left$cont to get more, or delete/edit ones $you've added already.");
        }
        Response::done();
      break;
      default:
        CoreUtils::notAllowed();
    }
  }

  /**
   * @OA\Get(
   *   path="/user/{id}/pcg/points",
   *   description="Gets the number of Personal Color Guide slot points the specified user has available to grant (their available points minus the 10 they need to keep). Requires staff permission.",
   *   tags={"personal color guide"},
   *   @OA\Parameter(
   *     name="id",
   *     in="path",
   *     required=true,
   *     @OA\Schema(ref="#/components/schemas/OneBasedId")
   *   ),
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(
   *       allOf={
   *         @OA\Schema(ref="#/components/schemas/ServerResponse"),
   *         @OA\Schema(
   *           type="object",
   *           required={"amount"},
   *           @OA\Property(property="amount", type="integer", description="The number of points available to be granted to or taken from the user")
   *         )
   *       }
   *     )
   *   ),
   *   @OA\Response(
   *     response="403",
   *     description="Insufficient permission (staff required)",
   *     @OA\JsonContent(ref="#/components/schemas/ServerResponse")
   *   ),
   *   @OA\Response(
   *     response="404",
   *     description="The specified user does not exist",
   *     @OA\JsonContent(ref="#/components/schemas/ServerResponse")
   *   )
   * )
   * @OA\Post(
   *   path="/user/{id}/pcg/points",
   *   description="Grants or takes Personal Color Guide slot points from the specified user, recording the change with a comment. Requires staff permission. The resulting available point total cannot go below 10.",
   *   tags={"personal color guide"},
   *   @OA\Parameter(
   *     name="id",
   *     in="path",
   *     required=true,
   *     @OA\Schema(ref="#/components/schemas/OneBasedId")
   *   ),
   *   @OA\RequestBody(
   *     required=true,
   *     @OA\MediaType(
   *       mediaType="application/x-www-form-urlencoded",
   *       @OA\Schema(
   *         required={"amount"},
   *         @OA\Property(property="amount", type="integer", description="The (non-zero) number of points to grant (positive) or take (negative)"),
   *         @OA\Property(property="comment", type="string", minLength=2, maxLength=140, description="An optional comment explaining the point change")
   *       )
   *     )
   *   ),
   *   @OA\Response(
   *     response="200",
   *     description="The points were successfully given or taken",
   *     @OA\JsonContent(ref="#/components/schemas/ServerResponse")
   *   ),
   *   @OA\Response(
   *     response="400",
   *     description="The specified amount is 0, invalid, or would cause the user's points to go below 10, or the comment is invalid",
   *     @OA\JsonContent(ref="#/components/schemas/ServerResponse")
   *   ),
   *   @OA\Response(
   *     response="403",
   *     description="Insufficient permission (staff required)",
   *     @OA\JsonContent(ref="#/components/schemas/ServerResponse")
   *   ),
   *   @OA\Response(
   *     response="404",
   *     description="The specified user does not exist",
   *     @OA\JsonContent(ref="#/components/schemas/ServerResponse")
   *   )
   * )
   */
  public function pointsApi($params) {
    if (Permission::insufficient('staff'))
      Response::fail();

    $this->load_user($params);

    switch ($this->action){
      case 'GET':
        Response::done(['amount' => $this->user->getPCGAvailablePoints(false) - 10]);
      break;
      case 'POST':
        $amount = (new Input('amount', 'int', [
          Input::CUSTOM_ERROR_MESSAGES => [
            Input::ERROR_MISSING => 'Amount of slots to give is missing',
            Input::ERROR_INVALID => 'Amount of slots to give (@value) is invalid',
            Input::ERROR_RANGE => 'Amount of slots to give must be between @min and @max',
          ],
        ]))->out();
        if ($amount === 0)
          Response::fail("You have to enter an integer that isn't 0");

        $availableSlots = $this->user->getPCGAvailablePoints(false);
        if ($availableSlots + $amount < 10)
          Response::fail('This would cause the users points to go below 10');

        $comment = (new Input('comment', 'string', [
          Input::IS_OPTIONAL => true,
          Input::IN_RANGE => [2, 140],
          Input::CUSTOM_ERROR_MESSAGES => [
            Input::ERROR_INVALID => 'Comment (@value) is invalid',
            Input::ERROR_RANGE => 'Comment must be between @min and @max chars',
          ],
        ]))->out();
        CoreUtils::checkStringValidity($comment, 'Comment');

        PCGPointGrant::record($this->user->id, Auth::$user->id, $amount, $comment);

        $nPoints = CoreUtils::makePlural('point', abs($amount), PREPEND_NUMBER);
        $given = $amount > 0 ? 'given' : 'taken';
        $to = $amount > 0 ? 'to' : 'from';
        Response::success("You've successfully $given $nPoints $to {$this->user->name}");
      break;
      default:
        CoreUtils::notAllowed();
    }
  }
}
