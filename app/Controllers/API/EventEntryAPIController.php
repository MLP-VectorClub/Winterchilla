<?php

namespace App\Controllers\API;

use App\Auth;
use App\Controllers\Traits\EventLoaderTrait;
use App\CoreUtils;
use App\Exceptions\MismatchedProviderException;
use App\Exceptions\UnsupportedProviderException;
use App\ImageProvider;
use App\Input;
use App\Models\EventEntry;
use App\Permission;
use App\Response;
use Exception;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *   schema="EventEntry",
 *   type="object",
 *   description="A submission made to a community event",
 *   required={
 *     "link",
 *     "title",
 *     "prevSrc",
 *   },
 *   @OA\Property(property="link", type="string", format="uri", description="URL of the submitted deviation or Sta.sh submission"),
 *   @OA\Property(property="title", type="string", minLength=2, maxLength=64),
 *   @OA\Property(property="prevSrc", type="string", format="uri", nullable=true, description="URL of the custom preview image, if one was provided"),
 * )
 */
class EventEntryAPIController extends APIController {
  use EventLoaderTrait;

  private ?EventEntry $entry;

  private function load_event_entry($params, string $action) {
    $lazy_loading = $action === 'lazyload';
    if (!Auth::$signed_in && !$lazy_loading)
      Response::error(401);

    if (!isset($params['entryid']))
      Response::error(404, 'Entry ID is missing or invalid');

    $this->entry = EventEntry::find((int)$params['entryid']);
    if (empty($this->entry))
      Response::error(404, 'The requested entry could not be found');
    if ($lazy_loading)
      return;

    if ($action === 'manage' && $this->entry->submitted_by !== Auth::$user->id && Permission::insufficient('staff'))
      Response::error(403, "You don't have permission to manage this entry");

    $this->load_event(['id' => $this->entry->event_id]);

    if ($action !== 'view' && Permission::insufficient('staff') && $this->event->ends_at->getTimestamp() < time())
      Response::error(403, 'This event has ended, entries can no longer be submitted or modified. Please ask a staff member if you need to make any changes.');
  }

  private function _processEntryData():array {
    $update = [];

    $link = (new Input('link', 'url', [
      Input::CUSTOM_ERROR_MESSAGES => [
        Input::ERROR_MISSING => 'Entry link is missing',
        Input::ERROR_INVALID => 'Entry link (@value) is invalid',
      ],
    ]))->out();
    try {
      $submission = new ImageProvider($link, [
        ImageProvider::PROV_FAVME,
        ImageProvider::PROV_DA,
        ImageProvider::PROV_STASH,
      ], false, false);
    }
    catch (MismatchedProviderException | UnsupportedProviderException $e){
      Response::invalid('link', 'Entry link must point to a deviation or Sta.sh submission');
    }
    catch (Exception $e){
      Response::invalid('link', 'Error while checking submission link: '.$e->getMessage());
    }
    $update['sub_id'] = $submission->id;
    $update['sub_prov'] = $submission->provider;

    $title = (new Input('title', 'string', [
      Input::IN_RANGE => [2, 64],
      Input::CUSTOM_ERROR_MESSAGES => [
        Input::ERROR_MISSING => 'Entry title is missing',
        Input::ERROR_INVALID => 'Entry title (@valie) is invalid',
      ],
    ]))->out();
    CoreUtils::checkStringValidity($title, 'Entry title', field: 'title');
    $update['title'] = $title;

    $prev_src = (new Input('prevSrc', 'url', [
      Input::IS_OPTIONAL => true,
      Input::CUSTOM_ERROR_MESSAGES => [
        Input::ERROR_INVALID => 'Preview (@value) is invalid',
      ],
    ]))->out();
    if (isset($prev_src)){
      try {
        $prov = new ImageProvider($prev_src);
      }
      catch (Exception $e){
        Response::invalid('prevSrc', 'Preview image error: '.$e->getMessage());
      }
      $update['prev_src'] = $prev_src;
      $update['prev_full'] = $prov->fullsize;
      $update['prev_thumb'] = $prov->preview;
    }
    else {
      $update['prev_src'] = null;
      $update['prev_full'] = null;
      $update['prev_thumb'] = null;
    }

    return $update;
  }

  /**
   * @OA\Get(
   *   path="/events/{id}/entries",
   *   description="Get the currently logged in user's entry details for management purposes. Requires the entry to belong to the current user, or staff permissions.",
   *   tags={"events"},
   *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(ref="#/components/schemas/EventEntry")
   *   ),
   *   @OA\Response(response="401", description="Not signed in", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="403", description="Insufficient permissions to manage this entry", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="404", description="Entry or event not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   * )
   * @OA\Get(
   *   path="/event-entries/{entryid}",
   *   description="Get an entry's details for management purposes. Requires the entry to belong to the current user, or staff permissions.",
   *   tags={"events"},
   *   @OA\Parameter(name="entryid", in="path", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(ref="#/components/schemas/EventEntry")
   *   ),
   *   @OA\Response(response="401", description="Not signed in", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="403", description="Insufficient permissions to manage this entry", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="404", description="Entry or event not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   * )
   * @OA\Put(
   *   path="/events/{id}/entries",
   *   description="Update the currently logged in user's entry. Requires the entry to belong to the current user (or staff permissions), and the event must not have ended (unless staff).",
   *   tags={"events"},
   *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\RequestBody(
   *     required=true,
   *     @OA\JsonContent(
   *       type="object",
   *       required={"link", "title"},
   *       @OA\Property(property="link", type="string", format="uri", description="URL of a deviation or Sta.sh submission"),
   *       @OA\Property(property="title", type="string", minLength=2, maxLength=64),
   *       @OA\Property(property="prevSrc", type="string", format="uri", nullable=true, description="Optional custom preview image URL"),
   *     )
   *   ),
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(type="object", required={"entryHtml"}, additionalProperties=false,
   *           @OA\Property(property="entryHtml", type="string", description="Rendered HTML for the updated entry list item")
   *         )
   *   ),
   *   @OA\Response(response="422", description="Validation error with the submitted link, title or preview image", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")),
   *   @OA\Response(response="401", description="Not signed in", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="403", description="Insufficient permissions, or the event has ended", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="404", description="Entry or event not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   * )
   * @OA\Put(
   *   path="/event-entries/{entryid}",
   *   description="Update an existing entry. Requires the entry to belong to the current user (or staff permissions), and the event must not have ended (unless staff).",
   *   tags={"events"},
   *   @OA\Parameter(name="entryid", in="path", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\RequestBody(
   *     required=true,
   *     @OA\JsonContent(
   *       type="object",
   *       required={"link", "title"},
   *       @OA\Property(property="link", type="string", format="uri", description="URL of a deviation or Sta.sh submission"),
   *       @OA\Property(property="title", type="string", minLength=2, maxLength=64),
   *       @OA\Property(property="prevSrc", type="string", format="uri", nullable=true, description="Optional custom preview image URL"),
   *     )
   *   ),
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(type="object", required={"entryHtml"}, additionalProperties=false,
   *           @OA\Property(property="entryHtml", type="string", description="Rendered HTML for the updated entry list item")
   *         )
   *   ),
   *   @OA\Response(response="422", description="Validation error with the submitted link, title or preview image", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")),
   *   @OA\Response(response="401", description="Not signed in", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="403", description="Insufficient permissions, or the event has ended", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="404", description="Entry or event not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   * )
   * @OA\Delete(
   *   path="/events/{id}/entries",
   *   description="Delete the currently logged in user's entry. Requires the entry to belong to the current user (or staff permissions), and the event must not have ended (unless staff).",
   *   tags={"events"},
   *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\Response(response="204", description="Deleted"),
   *   @OA\Response(response="401", description="Not signed in", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="403", description="Insufficient permissions, or the event has ended", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="404", description="Entry or event not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="500", description="Database error while deleting the entry", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   * )
   * @OA\Delete(
   *   path="/event-entries/{entryid}",
   *   description="Delete an existing entry. Requires the entry to belong to the current user (or staff permissions), and the event must not have ended (unless staff).",
   *   tags={"events"},
   *   @OA\Parameter(name="entryid", in="path", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\Response(response="204", description="Deleted"),
   *   @OA\Response(response="401", description="Not signed in", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="403", description="Insufficient permissions, or the event has ended", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="404", description="Entry or event not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="500", description="Database error while deleting the entry", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   * )
   */
  public function api($params) {
    switch ($this->action){
      case 'GET':
        $this->load_event_entry($params, 'manage');

        Response::ok([
          'link' => "http://{$this->entry->sub_prov}/{$this->entry->sub_id}",
          'title' => $this->entry->title,
          'prevSrc' => $this->entry->prev_src,
        ]);
      break;
      case 'PUT':
        $this->load_event_entry($params, 'manage');

        $changes = [];
        foreach ($this->_processEntryData() as $k => $v){
          if ($v !== $this->entry->{$k})
            $changes[$k] = $v;
        }

        if (!empty($changes)){
          $changes['updated_at'] = date('c');
          $this->entry->update_attributes($changes);
        }

        Response::ok(['entryHtml' => $this->entry->toListItemHTML($this->event, false, NOWRAP)]);
      break;
      case 'DELETE':
        $this->load_event_entry($params, 'manage');

        if (!$this->entry->delete())
          Response::dbError('Failed to delete entry', status: 500);

        Response::noContent();
      break;
      default:
        CoreUtils::notAllowed();
    }
  }

  /**
   * @OA\Get(
   *   path="/event-entries/{entryid}/lazyload",
   *   description="Get the lazily-loaded preview HTML for an entry. Does not require the user to be signed in.",
   *   tags={"events"},
   *   security={},
   *   @OA\Parameter(name="entryid", in="path", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(type="object", required={"html"}, additionalProperties=false,
   *           @OA\Property(property="html", type="string", description="Rendered HTML of the entry's preview")
   *         )
   *   ),
   *   @OA\Response(response="404", description="Entry not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   * )
   */
  public function lazyload($params) {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    $this->load_event_entry($params, 'lazyload');

    Response::ok(['html' => $this->entry->getListItemPreview()]);
  }
}
