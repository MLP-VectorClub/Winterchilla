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
  public function __construct() {
    parent::__construct();

    // Every admin endpoint is staff-only. This check went missing in the API controllers refactor, which left the log
    // details, useful links and notices open to signed-out visitors.
    if (Permission::insufficient('staff'))
      Response::denied();
  }

  /**
   * @OA\Schema(
   *   schema="LogItem",
   *   type="object",
   *   required={"id", "type", "typeLabel", "initiator", "ip", "createdAt", "hasDetails"},
   *   additionalProperties=false,
   *   @OA\Property(property="id", ref="#/components/schemas/OneBasedId"),
   *   @OA\Property(property="type", type="string", example="rolechange"),
   *   @OA\Property(property="typeLabel", type="string", example="User group change"),
   *   @OA\Property(property="initiator", nullable=true, ref="#/components/schemas/PostUser", description="Null when the web server itself made the change"),
   *   @OA\Property(property="ip", type="string", nullable=true),
   *   @OA\Property(property="createdAt", type="string", format="date-time"),
   *   @OA\Property(property="hasDetails", type="boolean", description="Whether GET /admin/logs/{id} has anything to show")
   * )
   * @OA\Get(
   *   path="/admin/logs",
   *   description="List log entries, newest first. Staff only.",
   *   tags={"admin"},
   *   @OA\Parameter(in="query", name="type", @OA\Schema(type="string"), description="Only entries of this type"),
   *   @OA\Parameter(in="query", name="initiatorId", @OA\Schema(type="integer", minimum=0), description="Only entries made by this user; 0 selects the ones made by the web server"),
   *   @OA\Parameter(in="query", name="page", @OA\Schema(type="integer", minimum=1, default=1)),
   *   @OA\Parameter(in="query", name="size", @OA\Schema(type="integer", minimum=1, maximum=100, default=20)),
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(
   *       type="object",
   *       required={"entries", "pagination"},
   *       @OA\Property(property="entries", type="array", @OA\Items(ref="#/components/schemas/LogItem")),
   *       @OA\Property(property="pagination", ref="#/components/schemas/Pagination")
   *     )
   *   ),
   *   @OA\Response(response="401", description="Not signed in", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="403", description="Insufficient permissions", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="422", description="Invalid query", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse"))
   * )
   */
  public function logList() {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    $conditions = [];
    $args = [];
    $type = $_GET['type'] ?? null;
    if ($type !== null) {
      if (!is_string($type) || !isset(Logs::LOG_DESCRIPTION[$type]))
        Response::invalid('type', 'The log entry type is invalid.');
      $conditions[] = 'entry_type = ?';
      $args[] = $type;
    }
    $initiator = $_GET['initiatorId'] ?? null;
    if ($initiator !== null) {
      if (!is_string($initiator) || !ctype_digit($initiator))
        Response::invalid('initiatorId', 'The initiator ID must be a non-negative integer.');
      if ((int)$initiator === 0)
        $conditions[] = 'initiator IS NULL';
      else {
        $conditions[] = 'initiator = ?';
        $args[] = (int)$initiator;
      }
    }
    $size = $_GET['size'] ?? 20;
    if (!is_numeric($size) || $size < 1 || $size > 100)
      Response::invalid('size', 'The size must be between 1 and 100.');
    $size = (int)$size;
    $page = $_GET['page'] ?? 1;
    if (!is_numeric($page) || $page < 1)
      Response::invalid('page', 'The page must be at least 1.');
    $page = (int)$page;

    $where = empty($conditions) ? [] : ['conditions' => array_merge([implode(' AND ', $conditions)], $args)];
    $total = Log::count($where);
    $entries = Log::find('all', $where + ['order' => 'created_at desc, id desc', 'limit' => $size, 'offset' => ($page - 1) * $size]);

    Response::ok([
      'entries' => array_map(fn(Log $l) => [
        'id' => $l->id,
        'type' => $l->entry_type,
        'typeLabel' => Logs::LOG_DESCRIPTION[$l->entry_type] ?? $l->entry_type,
        'initiator' => $l->actor === null ? null : ['id' => $l->actor->id, 'name' => $l->actor->name],
        'ip' => $l->ip,
        'createdAt' => gmdate('c', $l->created_at->getTimestamp()),
        'hasDetails' => $l->data !== null,
      ], $entries),
      'pagination' => [
        'currentPage' => $page,
        'totalPages' => max(1, (int)ceil($total / $size)),
        'totalItems' => $total,
        'itemsPerPage' => $size,
      ],
    ]);
  }

  /**
   * @OA\Get(
   *   path="/admin/logs/{id}",
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
   *           type="object",
   *           required={"details"},
   *           @OA\Property(
   *             property="details",
   *             type="array",
   *             description="A list of [label, value] pairs describing the log entry. Values may be strings, booleans, or other scalar types depending on the entry type",
   *             @OA\Items(type="array", @OA\Items())
   *           )
   *         )
   *   ),
   *   @OA\Response(response="401", description="Not signed in", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="403", description="Insufficient permissions", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="404", description="Not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="409", description="The entry has no details to show", @OA\JsonContent(type="object", required={"message","unclickable"}, @OA\Property(property="message", type="string"), @OA\Property(property="unclickable", type="boolean")))
   * )
   */
  public function logDetail($params) {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    if (!isset($params['id']) || !is_numeric($params['id']))
      Response::error(404, 'Entry ID is missing or invalid');

    /** @var Log|null $main_entry */
    $main_entry = Log::find($params['id']);
    if ($main_entry === null)
      Response::error(404, 'Log entry does not exist');
    if ($main_entry->data === null)
      Response::error(409, 'There are no details to show', ['unclickable' => true]);

    Response::ok(Logs::formatEntryDetails($main_entry, $main_entry->data));
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
      Response::error(404, 'The specified link does not exist');
  }

  /**
   * @OA\Schema(
   *   schema="UsefulLink",
   *   type="object",
   *   required={"label","url","title","minRole"},
   *   additionalProperties=false,
   *   @OA\Property(property="label", type="string", minLength=3, maxLength=35),
   *   @OA\Property(property="url", type="string", format="uri", minLength=3, maxLength=255),
   *   @OA\Property(property="title", type="string", maxLength=255),
   *   @OA\Property(property="minRole", ref="#/components/schemas/UserRole")
   * )
   * @OA\Schema(
   *   schema="UsefulLinkInput",
   *   type="object",
   *   description="Used to create or update a useful link. 'title' is optional and defaults to an empty string if omitted",
   *   required={"label","url","minRole"},
   *   additionalProperties=false,
   *   @OA\Property(property="label", type="string", minLength=3, maxLength=35),
   *   @OA\Property(property="url", type="string", format="uri", minLength=3, maxLength=255),
   *   @OA\Property(property="title", type="string", maxLength=255),
   *   @OA\Property(property="minRole", ref="#/components/schemas/UserRole")
   * )
   * @OA\Get(
   *   path="/useful-links/{id}",
   *   description="Get the details of a useful link. Requires staff role",
   *   tags={"admin"},
   *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(ref="#/components/schemas/UsefulLink")
   *   ),
   *   @OA\Response(response="404", description="Not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
   * )
   * @OA\Post(
   *   path="/useful-links",
   *   description="Create a new useful link. Requires staff role",
   *   tags={"admin"},
   *   @OA\RequestBody(@OA\JsonContent(ref="#/components/schemas/UsefulLinkInput")),
   *   @OA\Response(response="201", description="Created", @OA\JsonContent(type="object", required={"id"}, @OA\Property(property="id", ref="#/components/schemas/OneBasedId"))),
   *   @OA\Response(response="422", description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse"))
   * )
   * @OA\Put(
   *   path="/useful-links/{id}",
   *   description="Update an existing useful link. Requires staff role",
   *   tags={"admin"},
   *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\RequestBody(@OA\JsonContent(ref="#/components/schemas/UsefulLinkInput")),
   *   @OA\Response(response="204", description="Updated (or nothing needed changing)"),
   *   @OA\Response(response="404", description="Not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="422", description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse"))
   * )
   * @OA\Delete(
   *   path="/useful-links/{id}",
   *   description="Delete a useful link. Requires staff role",
   *   tags={"admin"},
   *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\Response(response="204", description="Deleted"),
   *   @OA\Response(response="404", description="Not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
   * )
   */
  public function usefulLinksApi($params) {
    if (!$this->creating)
      $this->load_useful_link($params);

    switch ($this->action){
      case 'GET':
        Response::ok([
          'label' => $this->usefulLink->label,
          'url' => $this->usefulLink->url,
          'title' => $this->usefulLink->title,
          'minRole' => $this->usefulLink->minrole,
        ]);
      break;
      case 'DELETE':
        if (!DB::$instance->where('id', $this->usefulLink->id)->delete('useful_links'))
          Response::dbError(status: 500);

        Response::noContent();
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

        $minrole = (new Input('minRole', function ($value) {
          if (empty(Permission::ROLES_ASSOC[$value]) || Permission::insufficient('guest', $value))
            return Input::ERROR_INVALID;
        }, [
          Input::CUSTOM_ERROR_MESSAGES => [
            Input::ERROR_MISSING => 'Minimum role is missing',
            Input::ERROR_INVALID => 'Minimum role (@value) is invalid',
          ],
        ]))->out();
        if ($this->creating || $this->usefulLink->minrole !== $minrole)
          $data['minrole'] = $minrole;

        if (empty($data))
          Response::noContent();
        $query = $this->creating
          ? UsefulLink::create($data)
          : $this->usefulLink->update_attributes($data);
        if (!$query)
          Response::dbError(status: 500);

        if ($this->creating)
          Response::ok(['id' => $query->id], 201);
        Response::noContent();
      break;
      default:
        CoreUtils::notAllowed();
    }
  }

  /**
   * @OA\Put(
   *   path="/useful-links/order",
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
   *   @OA\Response(response="204", description="Reordered"),
   *   @OA\Response(response="422", description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse"))
   * )
   */
  public function reorderUsefulLinks() {
    if ($this->action !== 'PUT')
      CoreUtils::notAllowed();

    $list = (new Input('list', 'int[]', [
      Input::CUSTOM_ERROR_MESSAGES => [
        Input::ERROR_MISSING => 'Missing ordering information',
      ],
    ]))->out();
    $order = 1;
    foreach ($list as $id){
      if (!UsefulLink::find($id)->update_attributes(['order' => $order++]))
        Response::error(500, "Updating link #$id failed, process halted");
    }

    Response::noContent();
  }

  private ?Notice $notice;

  private function load_notice($params) {
    if (empty($params['id']))
      CoreUtils::notFound();
    $this->notice = Notice::find($params['id']);

    if (!$this->creating && empty($this->notice))
      Response::error(404, 'The specified notice does not exist');
  }

  public function noticesApi($params) {
    # TODO Implement notice editing on the client side
    CoreUtils::notFound();

    $this->load_notice($params);

    switch ($this->action){
      case 'GET':
        Response::ok(CoreUtils::camelKeys($this->notice->to_array()));
      break;
      case 'POST':
      case 'PUT':
        if ($this->creating){
          $this->notice = new Notice([
            'posted_by' => Auth::$user->id,
          ]);
        }

        $message_html = (new Input('messageHtml', 'string', [
          Input::IN_RANGE => [null, 500],
          Input::CUSTOM_ERROR_MESSAGES => [
            Input::ERROR_MISSING => 'Message is missing',
            Input::ERROR_INVALID => 'Message is invalid',
            Input::ERROR_RANGE => 'Message cannot be longer than @max chars',
          ],
        ]))->out();
        CoreUtils::checkStringValidity($message_html, INVERSE_PRINTABLE_ASCII_PATTERN, 'Message');
        $this->notice->message_html = CoreUtils::sanitizeHtml($message_html);

        $hide_after = (new Input('hideAfter', 'timestamp', [
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
        Response::ok(['notice' => CoreUtils::camelKeys($this->notice->to_array())], $this->creating ? 201 : 200);
      break;
      case 'DELETE':
        $this->notice->delete();

        Response::noContent();
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
   *   @OA\Response(response="204", description="Cleared")
   * )
   */
  public function statCacheApi() {
    if ($this->action !== 'DELETE')
      CoreUtils::notAllowed();

    clearstatcache();
    Response::noContent();
  }
}
