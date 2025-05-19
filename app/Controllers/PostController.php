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

  public function lazyload($params) {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    $this->load_post($params, 'lazyload');

    if (empty($this->post))
      HTTP::statusCode(404, AND_DIE);

    Response::done(['html' => $this->post->getFinishedImage(array_key_exists('viewonly', $_GET))]);
  }

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
