<?php

namespace App\Controllers\API;

use App\Auth;
use App\CoreUtils;
use App\DB;
use App\Input;
use App\Logs;
use App\Models\Appearance;
use App\Models\Log;
use App\Models\UsefulLink;
use App\Permission;
use App\Response;

class AdminAPIController extends APIController {
  public function __construct() {
    parent::__construct();

    // Every admin endpoint is staff-only. This check went missing in the API controllers refactor, which left the log
    // details, useful links and notices open to signed-out visitors.
    if (Permission::insufficient('staff'))
      Response::denied();
  }

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

  public function pcgAppearanceList() {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    $size = $_GET['size'] ?? 10;
    if (!is_numeric($size) || $size < 1 || $size > 100)
      Response::invalid('size', 'The size must be between 1 and 100.');
    $size = (int)$size;
    $page = $_GET['page'] ?? 1;
    if (!is_numeric($page) || $page < 1)
      Response::invalid('page', 'The page must be at least 1.');
    $page = (int)$page;

    $where = ['conditions' => 'owner_id IS NOT NULL'];
    $total = Appearance::count($where);
    $appearances = Appearance::find('all', $where + ['order' => 'created_at desc, id desc', 'limit' => $size, 'offset' => ($page - 1) * $size]);

    Response::ok([
      'appearances' => array_map(fn(Appearance $a) => AppearancesAPIController::mapPreviewAppearance($a) + [
        'private' => (bool)$a->private,
        'createdAt' => gmdate('c', $a->created_at->getTimestamp()),
      ], $appearances),
      'pagination' => [
        'currentPage' => $page,
        'totalPages' => max(1, (int)ceil($total / $size)),
        'totalItems' => $total,
        'itemsPerPage' => $size,
      ],
    ]);
  }

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

    Response::ok(Logs::formatEntryDetails($main_entry, $main_entry->data) + ['data' => $main_entry->data]);
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

  public function statCacheApi() {
    if ($this->action !== 'DELETE')
      CoreUtils::notAllowed();

    clearstatcache();
    Response::noContent();
  }
}
