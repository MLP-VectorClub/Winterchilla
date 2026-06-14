<?php

namespace App\Controllers\API;

use App\Auth;
use App\CoreUtils;
use App\DB;
use App\Input;
use App\Logs;
use App\Models\Log;
use App\Models\Notice;
use App\Models\UsefulLink;
use App\Permission;
use App\Response;
use OpenApi\Annotations as OA;

class AdminAPIController extends APIController {
  /**
   * @OA\Get(
   *   path="/admin/logs/details/{id}",
   *   description="Get the details of a log entry. Requires staff role",
   *   tags={"admin"},
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
   *           required={"details"},
   *           @OA\Property(
   *             property="details",
   *             type="array",
   *             description="A list of [label, value] pairs describing the log entry. Values may be strings, booleans, or other scalar types depending on the entry type",
   *             @OA\Items(type="array", @OA\Items())
   *           )
   *         )
   *       }
   *     )
   *   ),
   *   @OA\Response(response="403", description="Insufficient permissions", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="default", description="Entry not found, or has no details to show", @OA\JsonContent(ref="#/components/schemas/ServerResponse"))
   * )
   */
  public function logDetail($params) {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    if (!isset($params['id']) || !is_numeric($params['id']))
      Response::fail('Entry ID is missing or invalid');

    /** @var Log|null $main_entry */
    $main_entry = Log::find($params['id']);
    if ($main_entry === null)
      Response::fail('Log entry does not exist');
    if ($main_entry->data === null)
      Response::fail('There are no details to show', ['unclickable' => true]);

    Response::done(Logs::formatEntryDetails($main_entry, $main_entry->data));
  }

  /**
   * @var null|UsefulLink
   */
  private $usefulLink;

  private function load_useful_link($params) {
    if (empty($params['id']))
      CoreUtils::notFound();
    $linkid = (int)$params['id'];
    $this->usefulLink = UsefulLink::find($linkid);
    if (empty($this->usefulLink))
      Response::fail('The specified link does not exist');
  }

  /**
   * @OA\Schema(
   *   schema="UsefulLink",
   *   type="object",
   *   required={"label","url","title","minrole"},
   *   additionalProperties=false,
   *   @OA\Property(property="label", type="string", minLength=3, maxLength=35),
   *   @OA\Property(property="url", type="string", format="uri", minLength=3, maxLength=255),
   *   @OA\Property(property="title", type="string", maxLength=255),
   *   @OA\Property(property="minrole", ref="#/components/schemas/UserRole")
   * )
   * @OA\Schema(
   *   schema="UsefulLinkInput",
   *   type="object",
   *   description="Used to create or update a useful link. 'title' is optional and defaults to an empty string if omitted",
   *   required={"label","url","minrole"},
   *   additionalProperties=false,
   *   @OA\Property(property="label", type="string", minLength=3, maxLength=35),
   *   @OA\Property(property="url", type="string", format="uri", minLength=3, maxLength=255),
   *   @OA\Property(property="title", type="string", maxLength=255),
   *   @OA\Property(property="minrole", ref="#/components/schemas/UserRole")
   * )
   * @OA\Get(
   *   path="/admin/usefullinks/{id}",
   *   description="Get the details of a useful link. Requires staff role",
   *   tags={"admin"},
   *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(allOf={
   *       @OA\Schema(ref="#/components/schemas/ServerResponse"),
   *       @OA\Schema(ref="#/components/schemas/UsefulLink")
   *     })
   *   ),
   *   @OA\Response(response="default", description="Link does not exist", @OA\JsonContent(ref="#/components/schemas/ServerResponse"))
   * )
   * @OA\Post(
   *   path="/admin/usefullinks",
   *   description="Create a new useful link. Requires staff role",
   *   tags={"admin"},
   *   @OA\RequestBody(@OA\JsonContent(ref="#/components/schemas/UsefulLinkInput")),
   *   @OA\Response(response="200", description="OK", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="default", description="Validation error", @OA\JsonContent(ref="#/components/schemas/ServerResponse"))
   * )
   * @OA\Put(
   *   path="/admin/usefullinks/{id}",
   *   description="Update an existing useful link. Requires staff role",
   *   tags={"admin"},
   *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\RequestBody(@OA\JsonContent(ref="#/components/schemas/UsefulLinkInput")),
   *   @OA\Response(response="200", description="OK", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="default", description="Validation error or link does not exist", @OA\JsonContent(ref="#/components/schemas/ServerResponse"))
   * )
   * @OA\Delete(
   *   path="/admin/usefullinks/{id}",
   *   description="Delete a useful link. Requires staff role",
   *   tags={"admin"},
   *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\Response(response="200", description="OK", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="default", description="Link does not exist", @OA\JsonContent(ref="#/components/schemas/ServerResponse"))
   * )
   */
  public function usefulLinksApi($params) {
    if (!$this->creating)
      $this->load_useful_link($params);

    switch ($this->action){
      case 'GET':
        Response::done([
          'label' => $this->usefulLink->label,
          'url' => $this->usefulLink->url,
          'title' => $this->usefulLink->title,
          'minrole' => $this->usefulLink->minrole,
        ]);
      break;
      case 'DELETE':
        if (!DB::$instance->where('id', $this->usefulLink->id)->delete('useful_links'))
          Response::dbError();

        Response::done();
      break;
      case 'POST':
      case 'PUT':
        $data = [];

        $label = (new Input('label', 'string', [
          Input::IN_RANGE => [3, 35],
          Input::CUSTOM_ERROR_MESSAGES => [
            Input::ERROR_MISSING => 'Link label is missing',
            Input::ERROR_RANGE => 'Link label must be between @min and @max characters long',
          ],
        ]))->out();
        if ($this->creating || $this->usefulLink->label !== $label){
          CoreUtils::checkStringValidity($label, 'Link label');
          $data['label'] = $label;
        }

        $url = (new Input('url', 'url', [
          Input::IN_RANGE => [3, 255],
          Input::CUSTOM_ERROR_MESSAGES => [
            Input::ERROR_MISSING => 'Link URL is missing',
            Input::ERROR_RANGE => 'Link URL must be between @min and @max characters long',
          ],
        ]))->out();
        if ($this->creating || $this->usefulLink->url !== $url)
          $data['url'] = $url;

        $title = (new Input('title', 'string', [
          Input::IS_OPTIONAL => true,
          Input::IN_RANGE => [3, 255],
          Input::CUSTOM_ERROR_MESSAGES => [
            Input::ERROR_RANGE => 'Link title must be between @min and @max characters long',
          ],
        ]))->out();
        if (!isset($title))
          $data['title'] = '';
        else if ($this->creating || $this->usefulLink->title !== $title){
          CoreUtils::checkStringValidity($title, 'Link title');
          $data['title'] = $title;
        }

        $minrole = (new Input('minrole', function ($value) {
          if (empty(Permission::ROLES_ASSOC[$value]) || Permission::insufficient('guest', $value))
            Response::fail();
        }, [
          Input::CUSTOM_ERROR_MESSAGES => [
            Input::ERROR_MISSING => 'Minimum role is missing',
            Input::ERROR_INVALID => 'Minimum role (@value) is invalid',
          ],
        ]))->out();
        if ($this->creating || $this->usefulLink->minrole !== $minrole)
          $data['minrole'] = $minrole;

        if (empty($data))
          Response::fail('Nothing was changed');
        $query = $this->creating
          ? UsefulLink::create($data)
          : $this->usefulLink->update_attributes($data);
        if (!$query)
          Response::dbError();

        Response::done();
      break;
      default:
        CoreUtils::notAllowed();
    }
  }

  /**
   * @OA\Post(
   *   path="/admin/usefullinks/reorder",
   *   description="Reorder useful links. Requires staff role",
   *   tags={"admin"},
   *   @OA\RequestBody(
   *     required=true,
   *     @OA\MediaType(
   *       mediaType="application/x-www-form-urlencoded",
   *       @OA\Schema(
   *         required={"list"},
   *         @OA\Property(property="list", type="string", description="Comma-separated list of useful link IDs in their new order", example="3,1,2")
   *       )
   *     )
   *   ),
   *   @OA\Response(response="200", description="OK", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="default", description="Validation error", @OA\JsonContent(ref="#/components/schemas/ServerResponse"))
   * )
   */
  public function reorderUsefulLinks() {
    if ($this->action !== 'POST')
      CoreUtils::notAllowed();

    $list = (new Input('list', 'int[]', [
      Input::CUSTOM_ERROR_MESSAGES => [
        Input::ERROR_MISSING => 'Missing ordering information',
      ],
    ]))->out();
    $order = 1;
    foreach ($list as $id){
      if (!UsefulLink::find($id)->update_attributes(['order' => $order++]))
        Response::fail("Updating link #$id failed, process halted");
    }

    Response::done();
  }

  private ?Notice $notice;

  private function load_notice($params) {
    if (empty($params['id']))
      CoreUtils::notFound();
    $this->notice = Notice::find($params['id']);

    if (!$this->creating && empty($this->notice))
      Response::fail('The specified notice does not exist');
  }

  public function noticesApi($params) {
    # TODO Implement notice editing on the client side
    CoreUtils::notFound();

    $this->load_notice($params);

    switch ($this->action){
      case 'GET':
        Response::done($this->notice->to_array());
      break;
      case 'POST':
      case 'PUT':
        if ($this->creating){
          $this->notice = new Notice([
            'posted_by' => Auth::$user->id,
          ]);
        }

        $message_html = (new Input('message_html', 'string', [
          Input::IN_RANGE => [null, 500],
          Input::CUSTOM_ERROR_MESSAGES => [
            Input::ERROR_MISSING => 'Message is missing',
            Input::ERROR_INVALID => 'Message is invalid',
            Input::ERROR_RANGE => 'Message cannot be longer than @max chars',
          ],
        ]))->out();
        CoreUtils::checkStringValidity($message_html, INVERSE_PRINTABLE_ASCII_PATTERN, 'Message');
        $this->notice->message_html = CoreUtils::sanitizeHtml($message_html);

        $hide_after = (new Input('hide_after', 'timestamp', [
          Input::IN_RANGE => [time(), null],
          Input::CUSTOM_ERROR_MESSAGES => [
            Input::ERROR_MISSING => 'Hide after date is missing',
            Input::ERROR_INVALID => 'Hide after date is invalid',
            Input::ERROR_RANGE => 'Hide after date cannot be in the past',
          ],
        ]))->out();
        $this->notice->hide_after = $hide_after;

        # TODO Validate notice type
        $this->notice->type = (new Input('type', 'string'))->out();

        $this->notice->save();
        Response::done(['notice' => $this->notice->to_array()]);
      break;
      case 'DELETE':
        $this->notice->delete();

        Response::done();
      break;
      default:
        CoreUtils::notAllowed();
    }
  }

  /**
   * @OA\Delete(
   *   path="/admin/stat-cache",
   *   description="Clear the PHP stat cache. Requires staff role",
   *   tags={"admin"},
   *   @OA\Response(response="200", description="OK", @OA\JsonContent(ref="#/components/schemas/ServerResponse"))
   * )
   */
  public function statCacheApi() {
    if ($this->action !== 'DELETE')
      CoreUtils::notAllowed();

    clearstatcache();
    Response::done();
  }
}
