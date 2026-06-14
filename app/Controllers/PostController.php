<?php

namespace App\Controllers;

use App\Auth;
use App\CoreUtils;
use App\DB;
use App\DeviantArt;
use App\Exceptions\UnsupportedProviderException;
use App\HTTP;
use App\ImageProvider;
use App\Input;
use App\Logs;
use App\Models\BrokenPost;
use App\Models\LegacyPostMapping;
use App\Models\LockedPost;
use App\Models\Notification;
use App\Models\PCGSlotHistory;
use App\Models\Post;
use App\Models\Show;
use App\Models\User;
use App\Permission;
use App\Posts;
use App\Response;
use App\ShowHelper;
use App\UserPrefs;
use App\Users;
use Exception;
use function in_array;
use function intval;
use function is_object;
use function is_string;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *   schema="Post",
 *   type="object",
 *   description="Represents an art post (request or reservation)",
 *   required={"label"},
 *   additionalProperties=false,
 *   @OA\Property(property="label", type="string", nullable=true, description="Display label for the post"),
 *   @OA\Property(property="type", type="string", enum={"chr","obj","bg"}, description="Request type, only present for requests"),
 *   @OA\Property(property="reserved_at", type="string", format="date-time", description="Date the request was reserved, or an empty string if not set. Only present for developers viewing a reserved request"),
 *   @OA\Property(property="posted_at", type="string", format="date-time", description="Only present for developers"),
 *   @OA\Property(property="finished_at", type="string", format="date-time", description="Date the post was finished, or an empty string if not set. Only present for developers when the post is reserved and finished"),
 * )
 */
class PostController extends Controller {
  public static string $CONTRIB_THANKS;

  public function __construct() {
    parent::__construct();

    self::$CONTRIB_THANKS = 'Thank you for your contribution!'.CoreUtils::responseSmiley(';)');
  }

  public function _authorize() {
    if (!Auth::$signed_in)
      Response::fail();
  }

  public function _authorizeMember() {
    $this->_authorize();

    if (Permission::insufficient('member'))
      Response::fail();
  }

  /**
   * @OA\Get(
   *   path="/post/{id}/reload",
   *   description="Reload a post's list item, checking whether its image is still available and merging the broken image with a Derpibooru match if possible. Marks the post as broken if its image cannot be found.",
   *   tags={"posts"},
   *   security={},
   *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\Parameter(name="from", in="query", required=false, @OA\Schema(type="string", enum={"profile"}), description="If set to 'profile', renders the list item for the profile page context"),
   *   @OA\Parameter(name="cache", in="query", required=false, @OA\Schema(type="string"), description="If present, cached data may be used when rendering the list item"),
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(
   *       allOf={
   *         @OA\Schema(ref="#/components/schemas/ServerResponse"),
   *         @OA\Schema(
   *           type="object",
   *           additionalProperties=false,
   *           @OA\Property(property="broken", type="boolean", description="True if the post's image became unavailable and the user lacks staff permission to see the updated list item"),
   *           @OA\Property(property="li", type="string", description="Rendered HTML for the post's list item"),
   *           @OA\Property(property="section", type="string", description="CSS selector for the section the list item belongs in")
   *         )
   *       }
   *     )
   *   ),
   *   @OA\Response(response="404", description="Post not found", @OA\JsonContent(ref="#/components/schemas/ServerResponse"))
   * )
   */
  public function reload($params) {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    $this->load_post($params, 'view');

    if ($this->post->deviation_id === null){
      $original_fullsize = $this->post->fullsize;
      $original_preview = $this->post->preview;
      $response_code = null;
      $failing_url = $original_fullsize;

      // See if both images are still available
      $images_available = DeviantArt::isImageAvailable($failing_url, [404], $response_code);
      if ($images_available){
        $failing_url = $original_preview;
        $images_available = DeviantArt::isImageAvailable($failing_url, [404], $response_code);
      }

      // Check for merged image on Derpibooru
      if (!$images_available){
        try {
          $fullsize_provider = ImageProvider::getProvider($original_fullsize);
        }
        catch (UnsupportedProviderException $e){ /* Ignore */ }
        if (isset($fullsize_provider) && $fullsize_provider->name === 'derpibooru'){
          $new_source = Posts::checkImage($original_fullsize);
          if (!empty($new_source->fullsize) && !empty($new_source->preview)){
            $images_available = true;
            $this->post->fullsize = $new_source->fullsize;
            $this->post->preview = $new_source->preview;
            $this->post->save();

            Logs::logAction('derpimerge', [
              'post_id' => $this->post->id,
              'original_fullsize' => $original_fullsize,
              'original_preview' => $original_preview,
              'new_fullsize' => $this->post->fullsize,
              'new_preview' => $this->post->preview,
            ]);
          }
        }
      }

      // Houston we have a problem
      if (!$images_available){
        $update = ['broken' => 1];
        if ($this->post->is_request && $this->post->reserved_by !== null){
          $old_reserver = $this->post->reserved_by;
          $update['reserved_by'] = null;
        }
        $this->post->update_attributes($update);
        BrokenPost::record($this->post->id, $response_code, $failing_url, $old_reserver ?? $this->post->reserved_by);

        if (Permission::insufficient('staff'))
          Response::done(['broken' => true]);
      }
    }

    if ($this->post->is_request && !$this->post->finished){
      $section = "#group-{$this->post->type}";
    }
    else {
      $un = $this->post->finished ? '' : 'un';
      $section = "#{$this->post->kind}s .{$un}finished";
    }
    $section .= ' > ul';

    $from_profile = isset($_REQUEST['from']) ? $_REQUEST['from'] === 'profile' : false;
    Response::done([
      'li' => $this->post->getLi($from_profile, !isset($_REQUEST['cache'])),
      'section' => $section,
    ]);
  }

  public function _checkPostEditPermission() {
    if (
      ($this->post->is_request && ($this->post->reserved_by !== null || $this->post->requested_by !== Auth::$user->id))
      && ($this->post->is_reservation && $this->post->reserved_by !== Auth::$user->id)
      && Permission::insufficient('staff')
    )
      Response::fail();
  }

  /**
   * @OA\Post(
   *   path="/post/{id}/reservation",
   *   description="Reserve a request, or take over an overdue reservation from another user. Requires member permission and signed in user.",
   *   tags={"posts"},
   *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\Parameter(name="from", in="query", required=false, @OA\Schema(type="string", enum={"suggestion","profile"}), description="Affects which fields are returned in the response"),
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(
   *       allOf={
   *         @OA\Schema(ref="#/components/schemas/ServerResponse"),
   *         @OA\Schema(
   *           type="object",
   *           additionalProperties=false,
   *           @OA\Property(property="li", type="string", description="Rendered HTML for the post's list item (when `from` is not 'suggestion')"),
   *           @OA\Property(property="button", type="string", description="Rendered HTML for the reserve button (when `from=suggestion`)"),
   *           @OA\Property(property="pendingReservations", type="string", description="Rendered HTML for the user's pending reservations (when `from=suggestion`)")
   *         )
   *       }
   *     )
   *   ),
   *   @OA\Response(response="401", description="Not signed in or insufficient permissions", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(
   *     response="400",
   *     description="Not a request, already reserved, broken, or reservation limit reached",
   *     @OA\JsonContent(
   *       allOf={
   *         @OA\Schema(ref="#/components/schemas/ServerResponse"),
   *         @OA\Schema(
   *           type="object",
   *           additionalProperties=false,
   *           @OA\Property(property="li", type="string", description="Rendered HTML for the post's list item (set if already reserved by the current user, or by someone else and not overdue)")
   *         )
   *       }
   *     )
   *   ),
   *   @OA\Response(response="404", description="Post not found", @OA\JsonContent(ref="#/components/schemas/ServerResponse"))
   * )
   * @OA\Delete(
   *   path="/post/{id}/reservation",
   *   description="Remove a reservation from a post (un-reserve a request, or delete a manually added reservation). Requires member permission and signed in user; staff may act on any post.",
   *   tags={"posts"},
   *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\Parameter(name="from", in="query", required=false, @OA\Schema(type="string", enum={"profile"}), description="If set to 'profile', includes the user's pending reservations HTML in the response"),
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(
   *       allOf={
   *         @OA\Schema(ref="#/components/schemas/ServerResponse"),
   *         @OA\Schema(
   *           type="object",
   *           additionalProperties=false,
   *           @OA\Property(property="li", type="string", description="Rendered HTML for the post's list item, for requests"),
   *           @OA\Property(property="pendingReservations", type="string", description="Rendered HTML for the user's pending reservations (when `from=profile`)")
   *         )
   *       }
   *     )
   *   ),
   *   @OA\Response(response="401", description="Not signed in or insufficient permissions", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="400", description="Reservation cannot be removed (must unfinish first)", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="404", description="Post not found", @OA\JsonContent(ref="#/components/schemas/ServerResponse"))
   * )
   */
  public function reservationApi($params) {
    $this->_authorizeMember();

    $this->load_post($params, 'reservation');
    $from = $_REQUEST['from'] ?? null;
    $suggested = $from === 'suggestion';
    $from_profile = $from === 'profile';

    switch ($this->action){
      case 'POST':
        if (!$this->post->is_request)
          Response::fail('This endpoint only acts on requests');

        $old_reserver = $this->post->reserved_by;
        $is_new_reserver = $old_reserver === null;
        if ($is_new_reserver){
          if (!UserPrefs::get('a_reserve', Auth::$user))
            Response::fail('You are not allowed to reserve requests');

          if ($this->post->broken)
            Response::fail('Broken posts cannot be reserved. The image must be updated'.(Permission::sufficient('staff')
                ? ' or the broken status cleared' : '').' via the edit menu to make the post reservable.');

          Users::checkReservationLimitReached();

          $this->post->reserved_by = Auth::$user->id;
          Posts::checkReserveAs($this->post);
          $this->post->reserved_at = date('c');
          if (Permission::sufficient('developer')){
            $reserved_at = Posts::validateReservedAt();
            if (isset($reserved_at))
              $this->post->reserved_at = date('c', $reserved_at);
          }
        }
        else {
          if ($this->is_user_reserver)
            Response::fail("You've already reserved this request", ['li' => $this->post->getLi()]);
          if (!$this->post->isOverdue())
            Response::fail('This request has already been reserved by '.$this->post->reserver->toAnchor(), ['li' => $this->post->getLi()]);
          $overdue = [
            'reserved_by' => $this->post->reserved_by,
            'reserved_at' => $this->post->reserved_at,
            'id' => $this->post->id,
          ];
          $this->post->reserved_by = Auth::$user->id;
          Posts::checkReserveAs($this->post);
          $this->post->reserved_at = date('c');
        }

        if (!$this->post->save())
          Response::dbError();

        $response = [];

        if (!$is_new_reserver){
          Logs::logAction('res_overtake', $overdue);
        }

        if ($suggested){
          $response['button'] = Posts::getPostReserveButton($this->post->reserver, false);
          $response['pendingReservations'] = User::find($suggested ? $this->post->reserved_by : $old_reserver)->getPendingReservationsHTML($suggested
            ? true : $this->is_user_reserver);
        }
        else $response['li'] = $this->post->getLi();

        Response::done($response);
      break;
      case 'DELETE':
        $can_delete = $this->is_user_reserver || Permission::sufficient('staff');
        if ($this->post->is_request){
          if ($this->post->reserved_by === null)
            Response::done(['li' => $this->post->getLi()]);

          if (!$can_delete)
            Response::fail();

          if ($this->post->deviation_id !== null)
            Response::fail('You must unfinish this request before unreserving it.');

          $old_reserver = $this->post->reserved_by;
          $this->post->reserved_by = null;
          $this->post->reserved_at = null;

          if (!$this->post->save())
            Response::dbError();

          $response = ['li' => $this->post->getLi()];
          if ($from_profile)
            $response['pendingReservations'] = User::find($old_reserver)->getPendingReservationsHTML($this->is_user_reserver);

          Response::done($response);
        }
        else {
          if (!$can_delete)
            Response::fail();

          if ($this->post->deviation_id !== null)
            Response::fail('You must unfinish this reservation before deleting it.');

          if (!$this->post->delete())
            Response::dbError();

          Response::done();
        }
      break;
      default:
        CoreUtils::notAllowed();
    }
  }

  /**
   * @OA\Post(
   *   path="/post/{id}/approval",
   *   description="Approve a finished post, marking it as locked. Requires member permission, the post to be reserved and finished, and the deviation to be in the club gallery.",
   *   tags={"posts"},
   *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(
   *       allOf={
   *         @OA\Schema(ref="#/components/schemas/ServerResponse"),
   *         @OA\Schema(
   *           type="object",
   *           required={"li"},
   *           additionalProperties=false,
   *           @OA\Property(property="message", type="string"),
   *           @OA\Property(property="li", type="string", description="Rendered HTML for the post's list item")
   *         )
   *       }
   *     )
   *   ),
   *   @OA\Response(response="401", description="Not signed in or insufficient permissions", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="400", description="Post not reserved/finished, or not in the club gallery", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="404", description="Post not found", @OA\JsonContent(ref="#/components/schemas/ServerResponse"))
   * )
   * @OA\Delete(
   *   path="/post/{id}/approval",
   *   description="Revoke approval of a previously approved post (unlock it). Requires staff permission, and developer permission if the deviation is still in the club gallery.",
   *   tags={"posts"},
   *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\Response(response="200", description="OK", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="401", description="Insufficient permissions", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="400", description="Post not approved, or still in the club gallery and user is not a developer", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="404", description="Post not found", @OA\JsonContent(ref="#/components/schemas/ServerResponse"))
   * )
   */
  public function approvalApi($params) {
    $this->_authorizeMember();

    $this->load_post($params, 'approval');

    switch ($this->action){
      case 'POST':
        if ($this->post->reserved_by === null)
          Response::fail('This post has not been reserved by anypony yet');

        if (empty($this->post->deviation_id))
          Response::fail('Only finished posts can be approved');

        CoreUtils::checkDeviationInClub($this->post->deviation_id);

        $this->post->approve();

        $response = [
          'message' => 'The image appears to be in the group gallery and as such it is now marked as approved.',
          'li' => $this->post->getLi()
        ];
        if ($this->is_user_reserver)
          $response['message'] .= ' '.self::$CONTRIB_THANKS;

        Response::done($response);
      break;
      case 'DELETE':
        if (Permission::insufficient('staff'))
          Response::fail();

        if (!$this->post->lock)
          Response::fail('This post has not been approved yet');

        if (Permission::insufficient('developer') && CoreUtils::isDeviationInClub($this->post->deviation_id) === true)
          Response::fail("<a href='http://fav.me/{$this->post->deviation_id}' target='_blank' rel='noopener'>This deviation</a> is part of the group gallery, which prevents the post from being unlocked.");

        $this->post->lock = false;
        $this->post->save();

        // Only deduct points if the reserver isn't also the requester
        if ($this->post->reserved_by !== $this->post->requested_by)
          PCGSlotHistory::record($this->post->reserved_by, 'post_unapproved', null, [
            'id' => $this->post->id,
          ]);

        Response::done();
      break;
      default:
        CoreUtils::notAllowed();
    }
  }

  /**
   * @OA\Get(
   *   path="/post/{id}",
   *   description="Get information about a single post for editing purposes. The user must have permission to edit the post (be the requester/reserver, or staff).",
   *   tags={"posts"},
   *   security={},
   *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(
   *       allOf={
   *         @OA\Schema(ref="#/components/schemas/ServerResponse"),
   *         @OA\Schema(ref="#/components/schemas/Post")
   *       }
   *     )
   *   ),
   *   @OA\Response(response="401", description="Insufficient permissions", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="404", description="Post not found", @OA\JsonContent(ref="#/components/schemas/ServerResponse"))
   * )
   * @OA\Post(
   *   path="/post",
   *   description="Create a new request or reservation post. Requires the user to be signed in and have permission to post the given kind; reservations additionally require member permission and an available reservation slot.",
   *   tags={"posts"},
   *   @OA\RequestBody(
   *     required=true,
   *     @OA\JsonContent(
   *       type="object",
   *       required={"kind","show_id"},
   *       @OA\Property(property="kind", type="string", enum={"request","reservation"}),
   *       @OA\Property(property="show_id", type="integer", description="ID of the show entry this post belongs to"),
   *       @OA\Property(property="postas", type="string", description="Developer-only: post on behalf of another user (by DA username)"),
   *       @OA\Property(property="allow_nonmember", type="boolean", description="Developer-only: allow posting a reservation on behalf of a non-member")
   *     )
   *   ),
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(
   *       allOf={
   *         @OA\Schema(ref="#/components/schemas/ServerResponse"),
   *         @OA\Schema(
   *           type="object",
   *           required={"id","kind"},
   *           additionalProperties=false,
   *           @OA\Property(property="id", type="string", description="Base36-encoded ID of the newly created post"),
   *           @OA\Property(property="kind", type="string", enum={"request","reservation"})
   *         )
   *       }
   *     )
   *   ),
   *   @OA\Response(response="401", description="Not signed in or insufficient permissions", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(
   *     response="400",
   *     description="Validation error",
   *     @OA\JsonContent(
   *       allOf={
   *         @OA\Schema(ref="#/components/schemas/ServerResponse"),
   *         @OA\Schema(
   *           type="object",
   *           additionalProperties=false,
   *           @OA\Property(property="canforce", type="boolean", description="If true, the request can be retried with allow_nonmember set to post a reservation on behalf of a non-member")
   *         )
   *       }
   *     )
   *   )
   * )
   * @OA\Put(
   *   path="/post/{id}",
   *   description="Update an existing post's details (image, label, etc). The user must have permission to edit the post (be the requester/reserver, or staff).",
   *   tags={"posts"},
   *   security={},
   *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\RequestBody(
   *     @OA\JsonContent(
   *       type="object",
   *       description="Fields are only updated if present and changed from the post's current value",
   *       @OA\Property(property="label", type="string", minLength=3, maxLength=255, nullable=true, description="Description for the post (required for requests)"),
   *       @OA\Property(property="type", type="string", enum={"chr","obj","bg"}, description="Request type, only applicable to requests"),
   *       @OA\Property(property="posted_at", type="string", format="date-time", description="Developer-only: when the post was originally posted/reserved"),
   *       @OA\Property(property="reserved_at", type="string", format="date-time", nullable=true, description="Developer-only: when the request was reserved (requests only)"),
   *       @OA\Property(property="finished_at", type="string", format="date-time", nullable=true, description="Developer-only: when the post was marked finished")
   *     )
   *   ),
   *   @OA\Response(response="200", description="OK (returns success message if nothing was changed)", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="401", description="Insufficient permissions", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="404", description="Post not found", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="400", description="Validation error", @OA\JsonContent(ref="#/components/schemas/ServerResponse"))
   * )
   */
  public function api($params) {
    if (!$this->creating)
      $this->load_post($params, 'manage');

    switch ($this->action){
      case 'GET':
        $this->_checkPostEditPermission();

        $response = [
          'label' => $this->post->label,
        ];
        if ($this->post->is_request){
          $response['type'] = $this->post->type;

          if (Permission::sufficient('developer') && !empty($this->post->reserved_by))
            $response['reserved_at'] = !empty($this->post->reserved_at) ? date('c', strtotime($this->post->reserved_at)) : '';
        }
        if (Permission::sufficient('developer')){
          $response['posted_at'] = date('c', strtotime($this->post->posted_at));
          if (!empty($this->post->reserved_by) && !empty($this->post->deviation_id))
            $response['finished_at'] = !empty($this->post->finished_at) ? date('c', strtotime($this->post->finished_at)) : '';
        }
        Response::done($response);
      break;
      case 'POST':
        $this->_authorize();

        $kind = (new Input('kind', function ($value) {
          if (!in_array($value, Post::KINDS, true))
            return Input::ERROR_INVALID;
        }, [
          Input::CUSTOM_ERROR_MESSAGES => [
            Input::ERROR_INVALID => 'Post type (@value) is invalid',
          ],
        ]))->out();

        $pref = 'a_post'.mb_substr($kind, 0, 3);
        if (!UserPrefs::get($pref, Auth::$user))
          Response::fail("You are not allowed to post {$kind}s");

        $is_reservation = $kind === 'reservation';
        if ($is_reservation){
          if (Permission::insufficient('member'))
            Response::fail();
          Users::checkReservationLimitReached();
        }

        $Image = $this->_checkImage();
        if (!is_object($Image)){
          CoreUtils::logError("Getting post image failed\n".var_export($Image, true));
          Response::fail('Getting post image failed. If this persists, please <a class="send-feedback">let us know</a>.');
        }

        $post = new Post();
        $post->preview = $Image->preview;
        $post->fullsize = $Image->fullsize;

        $show_id = (new Input('show_id', 'int', [
          Input::IS_OPTIONAL => false,
          Input::CUSTOM_ERROR_MESSAGES => [
            Input::ERROR_MISSING => 'Show entry ID is missing',
            Input::ERROR_INVALID => 'Show entry ID (@value) is invalid',
          ],
        ]))->out();
        $show = Show::find($show_id);
        if (empty($show))
          Response::fail('The specified show entry does not exist');
        $post->show_id = $show_id;

        $by_id = Auth::$user->id;
        if (Permission::sufficient('developer')){
          $username = Posts::validatePostAs();
          if ($username !== null){
            $post_as = Users::getDA($username, 'name');

            if (empty($post_as))
              Response::fail('The user you wanted to post as does not exist');

            if ($kind === 'reservation' && Permission::insufficient('member', $post_as->role) && !isset($_POST['allow_nonmember']))
              Response::fail('The user you wanted to post as is not a club member, do you want to post as them anyway?', ['canforce' => true]);

            $by_id = $post_as->id;
          }
        }

        $post->{$is_reservation ? 'reserved_by' : 'requested_by'} = $by_id;
        Posts::checkPostDetails($post->is_request, $post);

        if (!$post->save())
          Response::dbError();

        Response::done(['id' => $post->getIdString(), 'kind' => $kind]);
      break;
      case 'PUT':
        $this->_checkPostEditPermission();

        $update = [];
        Posts::checkPostDetails($this->post->is_request, $update, $this->post);

        if (empty($update))
          Response::success('Nothing was changed');

        if (!$this->post->update_attributes($update))
          Response::dbError();

        Response::done();
      break;
      default:
        CoreUtils::notAllowed();
    }
  }

  /**
   * @OA\Put(
   *   path="/post/{id}/finish",
   *   description="Mark a post as finished by attaching a deviation as its finished image. Requires member permission, the post to be reserved, and the user to be the reserver or staff.",
   *   tags={"posts"},
   *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\RequestBody(
   *     required=true,
   *     @OA\JsonContent(
   *       type="object",
   *       required={"deviation"},
   *       @OA\Property(property="deviation", type="string", format="uri", description="URL of the finished deviation"),
   *       @OA\Property(property="allow_overwrite_reserver", type="boolean", description="If set, allows the reserver to be changed to the deviation's author even if it differs from the current reserver"),
   *       @OA\Property(property="finished_at", type="string", format="date-time", description="Developer-only: overrides the finished timestamp")
   *     )
   *   ),
   *   @OA\Response(response="200", description="OK", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="401", description="Insufficient permissions", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(
   *     response="400",
   *     description="Post not reserved, or validation error",
   *     @OA\JsonContent(
   *       allOf={
   *         @OA\Schema(ref="#/components/schemas/ServerResponse"),
   *         @OA\Schema(
   *           type="object",
   *           additionalProperties=false,
   *           @OA\Property(property="retry", type="boolean", description="If true, the request can be retried with allow_overwrite_reserver set")
   *         )
   *       }
   *     )
   *   ),
   *   @OA\Response(response="404", description="Post not found", @OA\JsonContent(ref="#/components/schemas/ServerResponse"))
   * )
   * @OA\Delete(
   *   path="/post/{id}/finish",
   *   description="Unmark a finished post (remove its finished image), or delete the reservation entirely if `unbind` is set. Requires member permission and the user to be the reserver or staff.",
   *   tags={"posts"},
   *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\Parameter(name="unbind", in="query", required=false, @OA\Schema(type="string"), description="If present, removes the reservation from the post entirely (or deletes the post if it's a manually added reservation)"),
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(
   *       allOf={
   *         @OA\Schema(ref="#/components/schemas/ServerResponse"),
   *         @OA\Schema(
   *           type="object",
   *           additionalProperties=false,
   *           @OA\Property(property="remove", type="boolean", description="True if the post was deleted entirely")
   *         )
   *       }
   *     )
   *   ),
   *   @OA\Response(response="401", description="Insufficient permissions", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="400", description="Cannot unfinish manually added reservation without unbind", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="404", description="Post not found", @OA\JsonContent(ref="#/components/schemas/ServerResponse"))
   * )
   */
  public function finishApi($params) {
    $this->_authorizeMember();

    $this->load_post($params, 'finish');

    switch ($this->action){
      case 'PUT':
        if ($this->post->reserved_by === null)
          Response::fail('This post has not been reserved by anypony yet');

        if (!$this->is_user_reserver && Permission::insufficient('staff'))
          Response::fail();

        $update = Posts::checkPostFinishingImage($this->post->reserved_by);

        $finished_at = Permission::sufficient('developer') ? Posts::validateFinishedAt() : null;
        $update['finished_at'] = $finished_at !== null ? date('c', $finished_at) : date('c');

        if (!$this->post->update_attributes($update))
          Response::dbError();

        $postdata = [
          'id' => $this->post->id,
        ];
        $message = '';
        if (isset($update['lock'])){
          $message .= '<p>';

          LockedPost::record($this->post->id);
          if ($this->is_user_reserver)
            $message .= self::$CONTRIB_THANKS.' ';
          else Notification::send($this->post->reserved_by, 'post-approved', $postdata);

          $message .= "The post has been approved automatically because it's already in the club gallery.</p>";
        }
        if ($this->post->is_request && $this->post->requested_by !== Auth::$user->id){
          $notifSent = Notification::send($this->post->requester->id, 'post-finished', $postdata);
          $message .= "<p><strong>{$this->post->requester->name}</strong> ".($notifSent === 0 ? 'has been notified'
              : 'will receive a notification shortly').'.</p>'.(is_string($notifSent)
              ? "<div class='notice fail'><strong>Error:</strong> $notifSent</div>" : '');
        }

        if (!empty($message))
          Response::success($message);
        Response::done();
      break;
      case 'DELETE':
        if (!$this->is_user_reserver && Permission::insufficient('staff'))
          Response::fail();

        if (isset($_REQUEST['unbind'])){
          if ($this->post->is_reservation){
            if (!$this->post->delete())
              Response::dbError();

            Response::success('Reservation deleted', ['remove' => true]);
          }
          else if ($this->post->is_request && !$this->is_user_reserver && Permission::insufficient('staff'))
            Response::fail('You cannot remove the reservation from this post');

          $update = [
            'reserved_by' => null,
            'reserved_at' => null,
          ];
        }
        else if ($this->post->is_reservation && empty($this->post->preview))
          Response::fail('This reservation was added directly and cannot be marked unfinished. To remove it, check the unbind from user checkbox.');

        $update['deviation_id'] = null;
        $update['finished_at'] = null;

        if (!$this->post->update_attributes($update))
          Response::dbError();

        Response::done();
      break;
      default:
        CoreUtils::notAllowed();
    }
  }

  /**
   * @OA\Post(
   *   path="/post/{id}/locate",
   *   description="Locate a post's page/section given its ID, for use with old shortlink-style URLs. Returns either a redirect target (castle/show info) or a `refresh` instruction if the post belongs to the currently viewed show.",
   *   tags={"posts"},
   *   security={},
   *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\Parameter(name="show_id", in="query", required=false, @OA\Schema(ref="#/components/schemas/OneBasedId"), description="ID of the show currently being viewed, to check whether the post belongs to it"),
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(
   *       allOf={
   *         @OA\Schema(ref="#/components/schemas/ServerResponse"),
   *         @OA\Schema(
   *           type="object",
   *           additionalProperties=false,
   *           @OA\Property(property="refresh", type="string", enum={"request","reservation"}, description="Set if the post belongs to the show specified by show_id"),
   *           @OA\Property(
   *             property="castle",
   *             type="object",
   *             description="Set if the post belongs to a different show than show_id",
   *             additionalProperties=false,
   *             @OA\Property(property="name", type="string", description="Formatted title of the post's show"),
   *             @OA\Property(property="url", type="string", format="uri", description="URL of the post")
   *           )
   *         )
   *       }
   *     )
   *   ),
   *   @OA\Response(response="400", description="Post not found or broken", @OA\JsonContent(ref="#/components/schemas/ServerResponse"))
   * )
   */
  public function locate($params) {
    $this->load_post($params, 'locate');

    if (empty($this->post) || $this->post->broken)
      Response::fail("The post you were linked to has either been deleted or didn't exist in the first place. Sorry.".CoreUtils::responseSmiley(':\\'));

    if (isset($_REQUEST['show_id']) && $this->post->show->id === (int)$_REQUEST['show_id'])
      Response::done([
        'refresh' => $this->post->kind,
      ]);

    Response::done([
      'castle' => [
        'name' => $this->post->show->formatTitle(),
        'url' => $this->post->toURL(),
      ],
    ]);
  }

  /**
   * @OA\Get(
   *   path="/post/{id}/unbreak",
   *   description="Clear the broken status of a post after verifying its preview and fullsize images are reachable again, restoring the previous reserver if known. Requires staff permission.",
   *   tags={"posts"},
   *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(
   *       allOf={
   *         @OA\Schema(ref="#/components/schemas/ServerResponse"),
   *         @OA\Schema(
   *           type="object",
   *           required={"li"},
   *           additionalProperties=false,
   *           @OA\Property(property="li", type="string", description="Rendered HTML for the post's list item")
   *         )
   *       }
   *     )
   *   ),
   *   @OA\Response(response="401", description="Insufficient permissions", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="400", description="One of the images is still unavailable", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="404", description="Post not found", @OA\JsonContent(ref="#/components/schemas/ServerResponse"))
   * )
   */
  public function unbreak($params) {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    if (Permission::insufficient('staff'))
      Response::fail();

    $this->load_post($params, 'finish');

    foreach (['preview', 'fullsize'] as $key){
      $link = $this->post->{$key};

      if (!DeviantArt::isImageAvailable($link))
        Response::fail("The $key image appears to be unavailable. Please make sure <a href='$link'>this link</a> works and try again. If it doesn't, you will need to replace the image.");
    }

    // We fetch the last log entry and restore the reserver from when the post was still up (if applicable)

    /** @var BrokenPost $broken_post */
    $broken_post = DB::$instance->where('post_id', $this->post->id)->orderBy('created_at', 'DESC')->getOne('broken_posts');
    $this->post->broken = false;
    if (isset($broken_post->reserved_by))
      $this->post->reserved_by = $broken_post->reserved_by;

    $this->post->save();

    Logs::logAction('post_fix', [
      'id' => $this->post->id,
      'reserved_by' => $this->post->reserved_by,
    ]);

    Response::done(['li' => $this->post->getLi()]);
  }

  /**
   * @return ImageProvider
   */
  private function _checkImage() {
    return Posts::checkImage(Posts::validateImageURL());
  }

  /**
   * @OA\Post(
   *   path="/post/check-image",
   *   description="Validate an image URL (deviation or supported external image provider) and return its preview image and title. Requires the user to be signed in.",
   *   tags={"posts"},
   *   @OA\RequestBody(
   *     required=true,
   *     @OA\JsonContent(
   *       type="object",
   *       required={"url"},
   *       @OA\Property(property="url", type="string", format="uri", description="URL of the image/deviation to check")
   *     )
   *   ),
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(
   *       allOf={
   *         @OA\Schema(ref="#/components/schemas/ServerResponse"),
   *         @OA\Schema(
   *           type="object",
   *           required={"preview","title"},
   *           additionalProperties=false,
   *           @OA\Property(property="preview", type="string", format="uri"),
   *           @OA\Property(property="title", type="string", nullable=true)
   *         )
   *       }
   *     )
   *   ),
   *   @OA\Response(response="401", description="Not signed in", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="400", description="Invalid or unsupported image URL", @OA\JsonContent(ref="#/components/schemas/ServerResponse"))
   * )
   */
  public function checkImage() {
    if ($this->action !== 'POST')
      CoreUtils::notAllowed();

    $this->_authorize();

    $Image = $this->_checkImage();

    Response::done([
      'preview' => $Image->preview,
      'title' => $Image->title,
    ]);
  }

  /** @var Post */
  private $post;
  /** @var bool */
  private $is_user_reserver = false;

  public function load_post($params, $action) {
    $id = (int)$params['id'];
    $this->post = Post::find($id);
    if ($action === 'locate')
      return;

    if (empty($this->post))
      Response::fail("There's no post with the ID $id");

    if ($this->post->lock === true && Permission::insufficient('developer') && !in_array($action, ['unlock', 'lazyload', 'locate'], true))
      Response::fail('This post has been approved and cannot be edited or removed.');

    $this->is_user_reserver = Auth::$signed_in && $this->post->reserved_by === Auth::$user->id;
  }

  /**
   * @OA\Delete(
   *   path="/post/request/{id}",
   *   description="Delete a request post. Requires the user to be signed in and either be the original requester (provided it hasn't been reserved yet) or have staff permission.",
   *   tags={"posts"},
   *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\Response(response="200", description="OK", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="401", description="Not signed in or insufficient permissions", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="400", description="Not a request, or already reserved", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="404", description="Post not found", @OA\JsonContent(ref="#/components/schemas/ServerResponse"))
   * )
   */
  public function deleteRequest($params) {
    if ($this->action !== 'DELETE')
      CoreUtils::notAllowed();

    $this->_authorize();

    $this->load_post($params, 'delete');

    if (!$this->post->is_request)
      Response::fail('Only requests can be deleted using this endpoint');

    if (Permission::insufficient('staff')){
      if (!Auth::$signed_in || $this->post->requested_by !== Auth::$user->id)
        Response::fail();

      if (!empty($this->post->reserved_by))
        Response::fail('You cannot delete a request that has already been reserved by a group member');
    }

    if (!$this->post->delete())
      Response::dbError();

    Logs::logAction('req_delete', [
      'show_id' => $this->post->show_id,
      'id' => $this->post->id,
      'label' => $this->post->label,
      'type' => $this->post->type,
      'requested_by' => $this->post->requested_by,
      'requested_at' => $this->post->requested_at,
      'reserved_by' => $this->post->reserved_by,
      'deviation_id' => $this->post->deviation_id,
      'lock' => $this->post->lock,
    ]);

    Response::done();
  }

  /**
   * @OA\Put(
   *   path="/post/{id}/image",
   *   description="Change the image (preview/fullsize) of a post. Requires the user to be signed in, the post to not be locked, and either be the poster (and, if a request, not yet reserved) or have staff permission.",
   *   tags={"posts"},
   *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\RequestBody(
   *     required=true,
   *     @OA\JsonContent(
   *       type="object",
   *       required={"image_url"},
   *       @OA\Property(property="image_url", type="string", format="uri", description="New image URL (deviation or supported external image provider)")
   *     )
   *   ),
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(
   *       allOf={
   *         @OA\Schema(ref="#/components/schemas/ServerResponse"),
   *         @OA\Schema(
   *           type="object",
   *           additionalProperties=false,
   *           @OA\Property(property="li", type="string", description="Rendered HTML for the post's list item (if the post was previously broken)"),
   *           @OA\Property(property="preview", type="string", format="uri", description="New preview image URL (if the post was not previously broken)")
   *         )
   *       }
   *     )
   *   ),
   *   @OA\Response(response="401", description="Not signed in or insufficient permissions", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="400", description="Post is locked, already reserved, or the image is unavailable", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="404", description="Post not found", @OA\JsonContent(ref="#/components/schemas/ServerResponse"))
   * )
   */
  public function setImage($params) {
    if ($this->action !== 'PUT')
      CoreUtils::notAllowed();

    $this->_authorize();

    $this->load_post($params, 'view');
    if ($this->post->lock)
      Response::fail('This post is locked, its image cannot be changed.');

    if (Permission::insufficient('staff')){
      if ($this->post->posted_by !== Auth::$user->id)
        Response::fail();

      if ($this->post->is_request && $this->post->reserved_by !== null)
        Response::fail('You cannot change the image of a request that has already been reserved.');
    }

    $image_url = (new Input('image_url', 'string', [
      Input::CUSTOM_ERROR_MESSAGES => [
        Input::ERROR_MISSING => 'Image URL is missing',
      ],
    ]))->out();
    $Image = Posts::checkImage($image_url, $this->post);

    // Check image availability
    if (!DeviantArt::isImageAvailable($Image->preview))
      Response::fail("<p class='align-center'>The specified image doesn't seem to exist. Please verify that you can reach the URL below and try again.<br><a href='{$Image->preview}' target='_blank' rel='noopener'>{$Image->preview}</a></p>");

    $old = [
      'preview' => $this->post->preview,
      'fullsize' => $this->post->fullsize,
      'broken' => $this->post->broken,
    ];
    $this->post->preview = $Image->preview;
    $this->post->fullsize = $Image->fullsize;
    $this->post->broken = false;
    if (!$this->post->save())
      Response::dbError();

    Logs::logAction('img_update', [
      'id' => $this->post->id,
      'oldpreview' => $old['preview'],
      'oldfullsize' => $old['fullsize'],
      'newpreview' => $this->post->preview,
      'newfullsize' => $this->post->fullsize,
    ]);

    Response::done($old['broken'] ? ['li' => $this->post->getLi()] : ['preview' => $Image->preview]);
  }

  /**
   * @OA\Get(
   *   path="/post/{id}/lazyload",
   *   description="Get the rendered HTML for a post's finished image, for lazy loading on the page",
   *   tags={"posts"},
   *   security={},
   *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\Parameter(name="viewonly", in="query", required=false, @OA\Schema(type="string"), description="If present, renders the image in a view-only context (without editing controls)"),
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(
   *       allOf={
   *         @OA\Schema(ref="#/components/schemas/ServerResponse"),
   *         @OA\Schema(
   *           type="object",
   *           required={"html"},
   *           additionalProperties=false,
   *           @OA\Property(property="html", type="string", description="Rendered HTML for the post's finished image")
   *         )
   *       }
   *     )
   *   ),
   *   @OA\Response(response="404", description="Post not found", @OA\JsonContent(ref="#/components/schemas/ServerResponse"))
   * )
   */
  public function lazyload($params) {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    $this->load_post($params, 'lazyload');

    if (empty($this->post))
      HTTP::statusCode(404, AND_DIE);

    Response::done(['html' => $this->post->getFinishedImage(array_key_exists('viewonly', $_GET))]);
  }

  /**
   * @OA\Post(
   *   path="/post/reservation",
   *   description="Add a finished reservation directly on behalf of a user. Requires staff permission.",
   *   tags={"posts"},
   *   @OA\RequestBody(
   *     required=true,
   *     @OA\JsonContent(
   *       type="object",
   *       required={"show_id","deviation"},
   *       @OA\Property(property="show_id", type="integer", description="ID of the show entry this reservation belongs to"),
   *       @OA\Property(property="deviation", type="string", format="uri", description="URL of the finished deviation"),
   *       @OA\Property(property="allow_overwrite_reserver", type="boolean", description="If set, allows the reserver to be changed to the deviation's author even if it differs from the current user")
   *     )
   *   ),
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(
   *       allOf={
   *         @OA\Schema(ref="#/components/schemas/ServerResponse"),
   *         @OA\Schema(
   *           type="object",
   *           required={"id"},
   *           additionalProperties=false,
   *           @OA\Property(property="id", type="string", description="Base36-encoded ID of the newly created reservation")
   *         )
   *       }
   *     )
   *   ),
   *   @OA\Response(response="401", description="Not signed in or insufficient permissions", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="400", description="Validation error or show entry does not exist", @OA\JsonContent(ref="#/components/schemas/ServerResponse"))
   * )
   */
  public function addReservation() {
    if ($this->action !== 'POST')
      CoreUtils::notAllowed();

    $this->_authorize();

    if (Permission::insufficient('staff'))
      Response::fail();

    $_POST['allow_overwrite_reserver'] = true;
    $insert = Posts::checkPostFinishingImage();
    if (empty($insert['reserved_by']))
      $insert['reserved_by'] = Auth::$user->id;

    $show_id = (new Input('show_id', 'int', [
      Input::CUSTOM_ERROR_MESSAGES => [
        Input::ERROR_MISSING => 'Show ID is missing',
        Input::ERROR_INVALID => 'Show ID (@value) is invalid',
      ],
    ]))->out();
    if (!DB::$instance->where('id', $show_id)->has(Show::$table_name))
      Response::fail('The specified show entry does not exist');
    $insert['show_id'] = $show_id;

    $insert['finished_at'] = date('c');

    $reservation = new Post($insert);
    if (!$reservation->save())
      Response::dbError();

    if (!empty($insert['lock']))
      LockedPost::record($reservation->id);

    Response::success('Reservation added', ['id' => $reservation->getIdString()]);
  }

  public const SHARE_TYPE = [
    'req' => 'request',
    'res' => 'reservation',
  ];

  public function share($params) {
    if (!empty($params['thing'])){
      if (!array_key_exists($params['thing'], self::SHARE_TYPE))
        CoreUtils::notFound();

      $type = self::SHARE_TYPE[$params['thing']];
      $old_id = (int)$params['id'];
      $linked_post = LegacyPostMapping::lookup($old_id, $type);
    }
    else {
      $id = intval($params['id'], 36);

      if ($id > POSTGRES_INTEGER_MAX || $id < 1)
        CoreUtils::notFound();

      $linked_post = Post::find($id);
    }

    if ($linked_post === NULL)
      CoreUtils::notFound();

    ShowHelper::loadPage($linked_post->show, $linked_post);
  }

  /**
   * @OA\Get(
   *   path="/post/request/suggestion",
   *   description="Suggest a random unfinished, unreserved (or long-overdue) request the user could work on. Requires the user to be signed in.",
   *   tags={"posts"},
   *   @OA\Parameter(
   *     name="already_loaded",
   *     in="query",
   *     required=false,
   *     description="List of post IDs already shown to the user, to exclude from the suggestion",
   *     @OA\Schema(type="array", @OA\Items(ref="#/components/schemas/OneBasedId"))
   *   ),
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(
   *       allOf={
   *         @OA\Schema(ref="#/components/schemas/ServerResponse"),
   *         @OA\Schema(
   *           type="object",
   *           required={"suggestion"},
   *           additionalProperties=false,
   *           @OA\Property(property="suggestion", type="string", description="Rendered HTML for the suggested request's list item")
   *         )
   *       }
   *     )
   *   ),
   *   @OA\Response(response="401", description="Not signed in", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="400", description="No more requests available", @OA\JsonContent(ref="#/components/schemas/ServerResponse"))
   * )
   */
  public function suggestRequest() {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    if (Permission::insufficient('user'))
      Response::fail('You must be signed in to use this feature.');

    $already_loaded = (new Input('already_loaded', 'int[]', [
      Input::IS_OPTIONAL => true,
      Input::CUSTOM_ERROR_MESSAGES => [
        Input::ERROR_INVALID => 'List of already loaded image IDs is invalid',
      ],
    ]))->out();

    $query = "SELECT id FROM posts WHERE requested_by IS NOT NULL AND deviation_id IS NULL AND (reserved_by IS NULL OR reserved_at < NOW() - INTERVAL '3 WEEK')";
    if ($already_loaded !== null)
      $query .= ' AND id NOT IN ('.implode(',', $already_loaded).')';

    $postIDs = DB::$instance->query($query);
    if (empty($postIDs))
      Response::fail(($already_loaded !== null ? "You've gone through all" : 'There are no').' available requests, check back later.');
    $drawArray = [];
    foreach ($postIDs as $post)
      $drawArray[] = $post['id'];
    $chosen = $drawArray[array_rand($drawArray)];
    /** @var $Request Post */
    $Request = Post::find($chosen);
    Response::done(['suggestion' => Posts::getSuggestionLi($Request)]);
  }
}
