<?php

namespace App\Controllers\API;

use App\Auth;
use App\CoreUtils;
use App\Input;
use App\Models\Notice;
use App\Permission;
use App\Response;
use OpenApi\Annotations as OA;

class NoticesAPIController extends APIController {
  /**
   * @OA\Schema(
   *   schema="Notice",
   *   type="object",
   *   description="A site-wide notice shown until it is hidden",
   *   required={"id", "type", "messageHtml", "hideAfter", "postedBy", "createdAt"},
   *   additionalProperties=false,
   *   @OA\Property(property="id", ref="#/components/schemas/OneBasedId"),
   *   @OA\Property(property="type", type="string", enum={"info", "success", "fail", "warn", "caution"}),
   *   @OA\Property(property="messageHtml", type="string", description="Sanitized HTML of the message"),
   *   @OA\Property(property="hideAfter", type="string", format="date-time"),
   *   @OA\Property(property="postedBy", type="integer", nullable=true),
   *   @OA\Property(property="createdAt", type="string", format="date-time")
   * )
   */
  static function mapNotice(Notice $n):array {
    return [
      'id' => (int)$n->id,
      'type' => $n->type,
      'messageHtml' => $n->getMessage(),
      'hideAfter' => gmdate('c', $n->hide_after->getTimestamp()),
      'postedBy' => $n->posted_by,
      'createdAt' => gmdate('c', $n->created_at->getTimestamp()),
    ];
  }

  private function staffOnly():void {
    if (Permission::insufficient('staff'))
      Response::denied();
  }

  private function load(array $params):Notice {
    $notice = Notice::find((int)$params['id']);
    if ($notice === null)
      Response::error(404, 'The specified notice does not exist');

    return $notice;
  }

  /**
   * @OA\Get(
   *   path="/notices/current",
   *   description="The notices that are currently shown on the site",
   *   tags={"notices"},
   *   security={},
   *   @OA\Response(response="200", description="OK", @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Notice")))
   * )
   */
  public function current():void {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    Response::ok(array_map(fn(Notice $n) => self::mapNotice($n), Notice::list()));
  }

  /**
   * @OA\Get(
   *   path="/notices",
   *   description="List all notices, including hidden ones. Staff only.",
   *   tags={"notices"},
   *   @OA\Parameter(in="query", name="page", @OA\Schema(type="integer", minimum=1, default=1)),
   *   @OA\Parameter(in="query", name="size", @OA\Schema(type="integer", minimum=1, maximum=100, default=25)),
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(
   *       type="object",
   *       required={"notices", "pagination"},
   *       @OA\Property(property="notices", type="array", @OA\Items(ref="#/components/schemas/Notice")),
   *       @OA\Property(property="pagination", ref="#/components/schemas/Pagination")
   *     )
   *   ),
   *   @OA\Response(response="401", description="Not signed in", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="403", description="Insufficient permissions", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="422", description="Invalid query", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse"))
   * )
   */
  public function list():void {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();
    $this->staffOnly();

    $size = $_GET['size'] ?? 25;
    if (!is_numeric($size) || $size < 1 || $size > 100)
      Response::invalid('size', 'The size must be between 1 and 100.');
    $size = (int)$size;
    $page = $_GET['page'] ?? 1;
    if (!is_numeric($page) || $page < 1)
      Response::invalid('page', 'The page must be at least 1.');
    $page = (int)$page;

    $total = Notice::count();
    $notices = Notice::find('all', ['order' => 'created_at desc, id desc', 'limit' => $size, 'offset' => ($page - 1) * $size]);

    Response::ok([
      'notices' => array_map(fn(Notice $n) => self::mapNotice($n), $notices),
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
   *   path="/notices/{id}",
   *   description="Get a notice. Staff only.",
   *   tags={"notices"},
   *   @OA\Parameter(in="path", name="id", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\Response(response="200", description="OK", @OA\JsonContent(ref="#/components/schemas/Notice")),
   *   @OA\Response(response="401", description="Not signed in", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="403", description="Insufficient permissions", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="404", description="Notice not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
   * )
   * @OA\Post(
   *   path="/notices",
   *   description="Create a notice. Staff only.",
   *   tags={"notices"},
   *   @OA\RequestBody(
   *     required=true,
   *     @OA\JsonContent(
   *       type="object",
   *       required={"messageHtml", "hideAfter", "type"},
   *       @OA\Property(property="messageHtml", type="string", maxLength=500, description="Printable ASCII only; unsafe HTML is stripped"),
   *       @OA\Property(property="hideAfter", type="string", format="date-time", description="Must be in the future"),
   *       @OA\Property(property="type", type="string", enum={"info", "success", "fail", "warn", "caution"})
   *     )
   *   ),
   *   @OA\Response(response="201", description="Created", @OA\JsonContent(ref="#/components/schemas/Notice")),
   *   @OA\Response(response="401", description="Not signed in", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="403", description="Insufficient permissions", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="422", description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse"))
   * )
   * @OA\Put(
   *   path="/notices/{id}",
   *   description="Replace a notice's message, end time and type. Staff only.",
   *   tags={"notices"},
   *   @OA\Parameter(in="path", name="id", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\RequestBody(
   *     required=true,
   *     @OA\JsonContent(
   *       type="object",
   *       required={"messageHtml", "hideAfter", "type"},
   *       @OA\Property(property="messageHtml", type="string", maxLength=500),
   *       @OA\Property(property="hideAfter", type="string", format="date-time"),
   *       @OA\Property(property="type", type="string", enum={"info", "success", "fail", "warn", "caution"})
   *     )
   *   ),
   *   @OA\Response(response="200", description="Updated", @OA\JsonContent(ref="#/components/schemas/Notice")),
   *   @OA\Response(response="401", description="Not signed in", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="403", description="Insufficient permissions", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="404", description="Notice not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="422", description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse"))
   * )
   * @OA\Delete(
   *   path="/notices/{id}",
   *   description="Delete a notice. Staff only.",
   *   tags={"notices"},
   *   @OA\Parameter(in="path", name="id", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\Response(response="204", description="Deleted"),
   *   @OA\Response(response="401", description="Not signed in", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="403", description="Insufficient permissions", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="404", description="Notice not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
   * )
   * @param array $params
   */
  public function api(array $params = []):void {
    $this->staffOnly();
    $creating = empty($params['id']);

    switch ($this->action) {
      case 'GET':
        Response::ok(self::mapNotice($this->load($params)));
      break;
      case 'POST':
      case 'PUT':
        $notice = $creating ? new Notice(['posted_by' => Auth::$user->id]) : $this->load($params);

        $message_html = (new Input('messageHtml', 'string', [
          Input::IN_RANGE => [null, 500],
          Input::CUSTOM_ERROR_MESSAGES => [
            Input::ERROR_MISSING => 'Message is missing',
            Input::ERROR_INVALID => 'Message is invalid',
            Input::ERROR_RANGE => 'Message cannot be longer than @max chars',
          ],
        ]))->out();
        CoreUtils::checkStringValidity($message_html, 'Message', INVERSE_PRINTABLE_ASCII_PATTERN, field: 'messageHtml');
        $hide_after = (new Input('hideAfter', 'timestamp', [
          Input::IN_RANGE => [time(), null],
          Input::CUSTOM_ERROR_MESSAGES => [
            Input::ERROR_MISSING => 'Hide after date is missing',
            Input::ERROR_INVALID => 'Hide after date is invalid',
            Input::ERROR_RANGE => 'Hide after date cannot be in the past',
          ],
        ]))->out();
        $type = (new Input('type', function ($value) {
          if (!isset(Notice::VALID_TYPES[$value]))
            return Input::ERROR_INVALID;
        }, [
          Input::CUSTOM_ERROR_MESSAGES => [
            Input::ERROR_MISSING => 'Notice type is missing',
            Input::ERROR_INVALID => 'Notice type (@value) is invalid',
          ],
        ]))->out();

        $notice->message_html = CoreUtils::sanitizeHtml($message_html);
        $notice->hide_after = date('c', $hide_after);
        $notice->type = $type;
        $notice->save();
        // Timestamps are set by the database, so read the row back
        $notice = Notice::find($notice->id);

        Response::ok(self::mapNotice($notice), $creating ? 201 : 200);
      break;
      case 'DELETE':
        $this->load($params)->delete();

        Response::noContent();
      break;
      default:
        CoreUtils::notAllowed();
    }
  }
}
