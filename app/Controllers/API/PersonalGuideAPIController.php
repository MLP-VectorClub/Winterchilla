<?php

namespace App\Controllers\API;

use App\Appearances;
use App\Auth;
use App\Controllers\Traits\UserLoaderTrait;
use App\CoreUtils;
use App\Input;
use App\JSON;
use App\Models\Appearance;
use App\Models\PCGPointGrant;
use App\Models\PCGSlotHistory;
use App\Pagination;
use App\Permission;
use App\Response;
use App\UserPrefs;
use OpenApi\Annotations as OA;

class PersonalGuideAPIController extends APIController {
  use UserLoaderTrait;

  /**
   * @OA\Get(
   *   path="/users/{id}/personal-guide/appearances",
   *   security={},
   *   description="List the appearances of a user's personal guide, in their display order. Private appearances are listed to everyone but only carry their ID, label and `private: true` unless the visitor is the owner or staff.",
   *   tags={"personal guide"},
   *   @OA\Parameter(in="path", name="id", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\Parameter(in="query", name="page", @OA\Schema(type="integer", minimum=1, default=1)),
   *   @OA\Parameter(in="query", name="size", @OA\Schema(type="integer", minimum=1, maximum=50), description="Defaults to the visitor's items-per-page preference"),
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(
   *       type="object",
   *       required={"appearances", "pagination", "canManage"},
   *       @OA\Property(property="appearances", type="array", @OA\Items(anyOf={@OA\Schema(allOf={@OA\Schema(ref="#/components/schemas/Appearance"), @OA\Schema(type="object", required={"private"}, @OA\Property(property="private", type="boolean"))}), @OA\Schema(type="object", required={"id", "label", "private"}, @OA\Property(property="id", ref="#/components/schemas/OneBasedId"), @OA\Property(property="label", type="string"), @OA\Property(property="private", type="boolean", enum={true}))})),
   *       @OA\Property(property="pagination", ref="#/components/schemas/Pagination"),
   *       @OA\Property(property="canManage", type="boolean", description="Whether the visitor may add and edit appearances in this guide")
   *     )
   *   ),
   *   @OA\Response(response="401", description="The owner keeps the guide private and the visitor is signed out", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="403", description="The owner keeps the guide private", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="404", description="User not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="422", description="Invalid query", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse"))
   * )
   */
  public function appearances($params) {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    $this->load_user($params);

    if (!$this->user->canVisitorSeePCG())
      Response::denied();

    $size = $_GET['size'] ?? UserPrefs::get('cg_itemsperpage');
    if (!is_numeric($size) || $size < 1 || $size > 50)
      Response::invalid('size', 'The size must be between 1 and 50.');
    $size = (int)$size;
    $page = $_GET['page'] ?? 1;
    if (!is_numeric($page) || $page < 1)
      Response::invalid('page', 'The page must be at least 1.');
    $page = (int)$page;

    $total = $this->user->getPCGAppearanceCount();
    $pagination = (new Pagination('', $size))->forcePage($page)->calcMaxPages($total);
    $can_manage = Auth::$signed_in && ($this->user->id === Auth::$user->id || Permission::sufficient('staff'));

    Response::ok([
      'appearances' => array_map(
        fn(Appearance $a) => $a->private && !$can_manage
          ? ['id' => $a->id, 'label' => $a->label, 'private' => true]
          : AppearancesAPIController::mapAppearance($a, false) + ['private' => (bool)$a->private],
        $this->user->getPCGAppearances($pagination) ?? []
      ),
      'pagination' => [
        'currentPage' => $page,
        'totalPages' => max(1, (int)ceil($total / $size)),
        'totalItems' => $total,
        'itemsPerPage' => $size,
      ],
      'canManage' => $can_manage,
    ]);
  }

  /**
   * @OA\Get(
   *   path="/users/{id}/personal-guide/point-history",
   *   description="The personal guide point history of a user, newest first. Only the user and staff may see it. The history is built on the first request if it does not exist yet.",
   *   tags={"personal guide"},
   *   @OA\Parameter(in="path", name="id", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\Parameter(in="query", name="page", @OA\Schema(type="integer", minimum=1, default=1)),
   *   @OA\Parameter(in="query", name="size", @OA\Schema(type="integer", minimum=1, maximum=100, default=20)),
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(
   *       type="object",
   *       required={"entries", "pagination"},
   *       @OA\Property(
   *         property="entries",
   *         type="array",
   *         @OA\Items(
   *           type="object",
   *           required={"id", "changeType", "reason", "amount", "data", "createdAt"},
   *           @OA\Property(property="id", ref="#/components/schemas/OneBasedId"),
   *           @OA\Property(property="changeType", type="string", example="post_approved"),
   *           @OA\Property(property="reason", type="string", example="Post approved"),
   *           @OA\Property(property="amount", type="number", description="Points gained (positive) or lost (negative)"),
   *           @OA\Property(property="data", type="object", nullable=true, additionalProperties=true, description="Details depending on the change type (post or appearance references, manual grant comment). `by` is only sent to staff."),
   *           @OA\Property(property="createdAt", type="string", format="date-time")
   *         )
   *       ),
   *       @OA\Property(property="pagination", ref="#/components/schemas/Pagination")
   *     )
   *   ),
   *   @OA\Response(response="401", description="Not signed in", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="403", description="Not the user or staff", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="404", description="User not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="422", description="Invalid query", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse"))
   * )
   */
  public function pointHistory($params) {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    if (!Auth::$signed_in)
      Response::error(401);

    $this->load_user($params);

    if ($this->user->id !== Auth::$user->id && Permission::insufficient('staff'))
      Response::denied();

    $size = $_GET['size'] ?? 20;
    if (!is_numeric($size) || $size < 1 || $size > 100)
      Response::invalid('size', 'The size must be between 1 and 100.');
    $size = (int)$size;
    $page = $_GET['page'] ?? 1;
    if (!is_numeric($page) || $page < 1)
      Response::invalid('page', 'The page must be at least 1.');
    $page = (int)$page;

    $total = $this->user->getPCGSlotHistoryEntryCount();
    if ($total === 0) {
      // The history is derived data: like the page does, build it the first time somebody asks for it
      $this->user->recalculatePCGSlotHistroy();
      $total = $this->user->getPCGSlotHistoryEntryCount();
    }
    $pagination = (new Pagination('', $size))->forcePage($page)->calcMaxPages($total);
    $is_staff = Permission::sufficient('staff');

    $entries = array_map(function (PCGSlotHistory $e) use ($is_staff) {
      $data = $e->change_data === null ? null : JSON::decode($e->change_data);
      if (is_array($data) && !$is_staff)
        unset($data['by']);

      return [
        'id' => $e->id,
        'changeType' => $e->change_type,
        'reason' => PCGSlotHistory::CHANGE_DESC[$e->change_type] ?? $e->change_type,
        'amount' => (float)$e->change_amount,
        'data' => $data,
        'createdAt' => gmdate('c', $e->created_at->getTimestamp()),
      ];
    }, $this->user->getPCGSlotHistoryEntries($pagination));

    Response::ok([
      'entries' => $entries,
      'pagination' => [
        'currentPage' => $page,
        'totalPages' => max(1, (int)ceil($total / $size)),
        'totalItems' => $total,
        'itemsPerPage' => $size,
      ],
    ]);
  }

  /**
   * @OA\Post(
   *   path="/users/{id}/personal-guide/point-history/recalculation",
   *   description="Recalculates the Personal Color Guide point history for the specified user. Requires developer permission.",
   *   tags={"personal color guide"},
   *   @OA\Parameter(
   *     name="id",
   *     in="path",
   *     required=true,
   *     @OA\Schema(ref="#/components/schemas/OneBasedId")
   *   ),
   *   @OA\Response(
   *     response="204",
   *     description="OK"
   *   ),
   *   @OA\Response(response="401", description="Not signed in", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(
   *     response="403",
   *     description="Insufficient permission (developer required)",
   *     @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
   *   ),
   *   @OA\Response(
   *     response="404",
   *     description="The specified user does not exist",
   *     @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
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

    Response::noContent();
  }

  /**
   * @OA\Get(
   *   path="/users/{id}/personal-guide/slots",
   *   description="Checks whether the specified user is eligible to add a new Personal Color Guide appearance. Fails if PCG appearance creation is disabled for the user, or if they have fewer than 10 available slots.",
   *   tags={"personal color guide"},
   *   security={},
   *   @OA\Parameter(
   *     name="id",
   *     in="path",
   *     required=true,
   *     @OA\Schema(ref="#/components/schemas/OneBasedId")
   *   ),
   *   @OA\Response(
   *     response="204",
   *     description="The user is allowed to add a new PCG appearance"
   *   ),
   *   @OA\Response(response="401", description="Not signed in", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(
   *     response="403",
   *     description="PCG appearance creation is disabled for this user",
   *     @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
   *   ),
   *   @OA\Response(
   *     response="409",
   *     description="The user has no available slots left",
   *     @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
   *   ),
   *   @OA\Response(
   *     response="404",
   *     description="The specified user does not exist",
   *     @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
   *   )
   * )
   */
  public function slotsApi($params) {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    switch ($this->action){
      case 'GET':
        if (!Auth::$signed_in)
          Response::error(401);

        $this->load_user($params);

        if (!UserPrefs::get('a_pcgmake', $this->user))
          Response::error(403, Appearances::PCG_APPEARANCE_MAKE_DISABLED);

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
          Response::error(409, "$You $nave no available slots left$cont to get more, or delete/edit ones $you've added already.");
        }
        Response::noContent();
      break;
      default:
        CoreUtils::notAllowed();
    }
  }

  /**
   * @OA\Get(
   *   path="/users/{id}/personal-guide/points",
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
   *           type="object",
   *           required={"amount"},
   *           @OA\Property(property="amount", type="integer", description="The number of points available to be granted to or taken from the user")
   *         )
   *   ),
   *   @OA\Response(response="401", description="Not signed in", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(
   *     response="403",
   *     description="Insufficient permission (staff required)",
   *     @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
   *   ),
   *   @OA\Response(
   *     response="404",
   *     description="The specified user does not exist",
   *     @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
   *   )
   * )
   * @OA\Post(
   *   path="/users/{id}/personal-guide/points",
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
   *     response="201",
   *     description="The points were successfully given or taken",
   *     @OA\JsonContent(type="object", required={"message"}, @OA\Property(property="message", type="string"))
   *   ),
   *   @OA\Response(
   *     response="422",
   *     description="The specified amount is 0, invalid, or would cause the user's points to go below 10, or the comment is invalid",
   *     @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
   *   ),
   *   @OA\Response(response="401", description="Not signed in", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(
   *     response="403",
   *     description="Insufficient permission (staff required)",
   *     @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
   *   ),
   *   @OA\Response(
   *     response="404",
   *     description="The specified user does not exist",
   *     @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
   *   )
   * )
   */
  public function pointsApi($params) {
    if (Permission::insufficient('staff'))
      Response::denied();

    $this->load_user($params);

    switch ($this->action){
      case 'GET':
        Response::ok(['amount' => $this->user->getPCGAvailablePoints(false) - 10]);
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
          Response::invalid('amount', "You have to enter an integer that isn't 0");

        $availableSlots = $this->user->getPCGAvailablePoints(false);
        if ($availableSlots + $amount < 10)
          Response::invalid('amount', 'This would cause the users points to go below 10');

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
        Response::ok(['message' => "You've successfully $given $nPoints $to {$this->user->name}"], 201);
      break;
      default:
        CoreUtils::notAllowed();
    }
  }
}
