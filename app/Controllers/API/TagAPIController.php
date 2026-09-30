<?php

namespace App\Controllers\API;

use App\Appearances;
use App\CGUtils;
use App\Controllers\Traits\ColorGuideAccessTrait;
use App\CoreUtils;
use App\DB;
use App\Input;
use App\Models\Appearance;
use App\Models\Tag;
use App\Models\Tagged;
use App\Permission;
use App\Regexes;
use App\Response;
use App\Tags;
use OpenApi\Annotations as OA;
use function count;
use function in_array;

/**
 * @OA\Schema(
 *   schema="Tag",
 *   type="object",
 *   description="Represents a color guide tag",
 *   required={
 *     "id",
 *     "name",
 *     "title",
 *     "type",
 *     "uses",
 *     "synonymOf"
 *   },
 *   additionalProperties=false,
 *   @OA\Property(property="id", ref="#/components/schemas/OneBasedId"),
 *   @OA\Property(property="name", type="string", description="The tag's name"),
 *   @OA\Property(property="title", type="string", nullable=true, description="Optional human-friendly title for the tag"),
 *   @OA\Property(property="type", type="string", description="The tag's type/category"),
 *   @OA\Property(property="uses", type="integer", minimum=0, description="Number of appearances this tag is applied to"),
 *   @OA\Property(property="synonymOf", ref="#/components/schemas/OneBasedId", nullable=true, description="ID of the tag this one is a synonym of, if any")
 * )
 */
class TagAPIController extends APIController {
  use ColorGuideAccessTrait;

  public function __construct() {
    parent::__construct();

    $this->_initAppearancePageState();
  }

  /**
   * @OA\Get(
   *   path="/tags",
   *   description="Search tags for autocomplete purposes, or list tags for management. Staff only.",
   *   tags={"tags"},
   *   @OA\Parameter(name="not", in="query", description="Exclude a tag ID from the results", @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\Parameter(name="action", in="query", description="Set to 'synon' to validate synonym selection (fails if a synonym already exists)", @OA\Schema(type="string")),
   *   @OA\Parameter(name="s", in="query", description="When set, performs an autocomplete search by name", @OA\Schema(ref="#/components/schemas/QueryString")),
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(type="array", @OA\Items(type="object", additionalProperties=false,
   *       @OA\Property(property="id", ref="#/components/schemas/OneBasedId"),
   *       @OA\Property(property="name", type="string"),
   *       @OA\Property(property="type", type="string", nullable=true, description="Tag type/category; when found via the 's' (autocomplete) search this is prefixed with 'typ-'"),
   *       @OA\Property(property="uses", type="integer"),
   *       @OA\Property(property="synonymOf", ref="#/components/schemas/OneBasedId", nullable=true),
   *       @OA\Property(property="synonymTarget", type="string", description="Name of the tag this one is a synonym of, only present when found via autocomplete")
   *     ))
   *   ),
   *   @OA\Response(response="401", description="Not signed in", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="403", description="Insufficient permission (staff required)", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="409", description="Tag is already a synonym of another tag", @OA\JsonContent(type="object", required={"message","synonymOf"}, @OA\Property(property="message", type="string"), @OA\Property(property="synonymOf", type="object", @OA\Property(property="id", ref="#/components/schemas/OneBasedId"), @OA\Property(property="name", type="string"))))
   * )
   */
  public function autocomplete() {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    if (Permission::insufficient('staff'))
      Response::denied();

    $except = (new Input('not', 'int', [Input::IS_OPTIONAL => true]))->out();
    if ((new Input('action', 'string', [Input::IS_OPTIONAL => true]))->out() === 'synon'){
      if ($except !== null)
        DB::$instance->where('id', $except);
      /** @var $Tag Tag */
      $Tag = DB::$instance->where('"synonym_of" IS NOT NULL')->getOne('tags');
      if (!empty($Tag))
        Response::error(409, "This tag is already a synonym of \"{$Tag->synonym->name}\"", [
          'synonymOf' => ['id' => $Tag->synonym->id, 'name' => $Tag->synonym->name],
        ]);
    }

    $viaAutocomplete = !empty($_GET['s']);
    $limit = null;
    $cols = 'id, name, type';
    if ($viaAutocomplete){
      if (!preg_match(Regexes::$tag_name, $_GET['s']))
        CGUtils::autocompleteRespond('[]');

      $query = CoreUtils::trim(strtolower($_GET['s']));
      DB::$instance->where('name', "%$query%", 'LIKE');
      $limit = 5;
      $cols = "id, name, 'typ-'||type as type";
      DB::$instance->orderBy('uses', 'DESC');
    }
    else DB::$instance->orderBy('type')->where('"synonym_of" IS NULL');

    if ($except !== null)
      DB::$instance->where('id', $except, '!=');

    $Tags = DB::$instance->disableAutoClass()->orderBy('name')->get('tags', $limit, "$cols, uses, synonym_of");
    if ($viaAutocomplete){
      foreach ($Tags as &$t){
        if (empty($t['synonym_of']))
          continue;
        $Syn = Tag::find($t['synonym_of']);
        if (!empty($Syn))
          $t['synonym_target'] = $Syn->name;
      }
      unset($t);
    }

    CGUtils::autocompleteRespond(empty($Tags) ? '[]' : CoreUtils::camelKeys($Tags));
  }

  /**
   * @OA\Post(
   *   path="/tags/recount-uses",
   *   description="Recalculate the use counts of the given tags. Staff only.",
   *   tags={"tags"},
   *   @OA\RequestBody(required=true, @OA\JsonContent(
   *     required={"tagIds"},
   *     @OA\Property(property="tagIds", type="array", description="IDs of tags to recount", @OA\Items(ref="#/components/schemas/OneBasedId"))
   *   )),
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(type="object", additionalProperties=false,
   *         @OA\Property(property="message", type="string"),
   *         @OA\Property(property="counts", type="object", description="Map of tag ID to its new use count")
   *       )
   *   ),
   *   @OA\Response(response="401", description="Not signed in", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="403", description="Insufficient permission (staff required)", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="422", description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse"))
   * )
   */
  public function recountUses() {
    if ($this->action !== 'POST')
      CoreUtils::notAllowed();

    if (Permission::insufficient('staff'))
      Response::denied();

    /** @var $tagIDs int[] */
    $tagIDs = (new Input('tagIds', 'int[]', [
      Input::CUSTOM_ERROR_MESSAGES => [
        Input::ERROR_MISSING => 'Missing list of tags to update',
        Input::ERROR_INVALID => 'List of tags is invalid',
      ],
    ]))->out();
    $counts = [];
    $updates = 0;
    foreach ($tagIDs as $tid){
      if (Tags::getActual($tid, 'id', RETURN_AS_BOOL)){
        $result = Tags::updateUses($tid, true);
        if ($result['status'])
          $updates++;
        $counts[$tid] = $result['count'];
      }
    }

    Response::ok([
      'message' => !$updates
        ? 'There was no change in the tag usage counts'
        : "$updates tag".($updates !== 1 ? "s'" : "'s").' use count'.($updates !== 1 ? 's were' : ' was').' updated',
      'counts' => $counts,
    ]);
  }

  /** @var Tag|null */
  private $tag;

  private function load_tag($params) {
    $this->_initialize($params);
    if (Permission::insufficient('staff'))
      Response::denied();

    if (!$this->creating){
      if (!isset($params['id']))
        Response::error(404, 'Missing tag ID');
      $id = (int)$params['id'];
      $this->tag = Tag::find($id);
      if (empty($this->tag))
        Response::error(404, 'This tag does not exist');
    }
  }

  /**
   * @OA\Get(
   *   path="/tags/{id}",
   *   description="Get a tag's details. Staff only.",
   *   tags={"tags"},
   *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(ref="#/components/schemas/Tag")
   *   ),
   *   @OA\Response(response="401", description="Not signed in", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="403", description="Insufficient permission (staff required)", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="404", description="Tag does not exist", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
   * )
   * @OA\Post(
   *   path="/tags",
   *   description="Create a new tag, optionally adding it to an appearance. Staff only.",
   *   tags={"tags"},
   *   @OA\RequestBody(required=true, @OA\JsonContent(
   *     required={"name"},
   *     @OA\Property(property="name", type="string", description="Tag name"),
   *     @OA\Property(property="type", type="string", description="Tag type/category"),
   *     @OA\Property(property="title", type="string", maxLength=255, nullable=true, description="Optional human-friendly title"),
   *     @OA\Property(property="addTo", ref="#/components/schemas/ZeroBasedId", description="ID of an appearance to add the new tag to; 0 means the tag cannot be applied")
   *   )),
   *   @OA\Response(
   *     response="201",
   *     description="Created. The response is the new tag; if 'addTo' was valid it also carries the appearance's rendered tag list in 'tags', and if it was not it carries a 'warning'.",
   *     @OA\JsonContent(allOf={
   *       @OA\Schema(ref="#/components/schemas/Tag"),
   *       @OA\Schema(type="object",
   *         @OA\Property(property="tags", type="string", description="Rendered HTML of the appearance's tags, present when addto was specified and valid"),
   *         @OA\Property(property="warning", type="string", description="Present when the tag was created but could not be added to the requested appearance")
   *       )
   *     })
   *   ),
   *   @OA\Response(response="401", description="Not signed in", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="403", description="Insufficient permission (staff required)", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="422", description="A tag with the same name and type already exists, or validation error", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse"))
   * )
   * @OA\Put(
   *   path="/tags/{id}",
   *   description="Update an existing tag's name, type or title. Staff only.",
   *   tags={"tags"},
   *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\RequestBody(required=true, @OA\JsonContent(
   *     required={"name"},
   *     @OA\Property(property="name", type="string", description="Tag name"),
   *     @OA\Property(property="type", type="string", description="Tag type/category"),
   *     @OA\Property(property="title", type="string", maxLength=255, nullable=true, description="Optional human-friendly title")
   *   )),
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(ref="#/components/schemas/Tag")
   *   ),
   *   @OA\Response(response="401", description="Not signed in", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="403", description="Insufficient permission (staff required)", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="422", description="A tag with the same name and type already exists, or validation error", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse"))
   * )
   * @OA\Delete(
   *   path="/tags/{id}",
   *   description="Delete a tag (or its synonym target if it's a synonym). Staff only. If the tag is in use, a confirmation must be sent via the 'sanityCheck' parameter.",
   *   tags={"tags"},
   *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\Parameter(name="sanityCheck", in="query", description="Set to confirm deletion of an in-use tag", @OA\Schema(type="string")),
   *   @OA\Response(response="204", description="Deleted"),
   *   @OA\Response(response="401", description="Not signed in", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="403", description="Insufficient permission (staff required)", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="404", description="Tag does not exist", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="409", description="The tag is in use and deletion was not confirmed with sanitycheck", @OA\JsonContent(type="object", required={"message","uses"}, @OA\Property(property="message", type="string"), @OA\Property(property="uses", type="integer", description="Number of appearances the tag is used on")))
   * )
   */
  public function api($params) {
    $this->load_tag($params);

    switch ($this->action){
      case 'GET':
        Response::ok(CoreUtils::camelKeys($this->tag->to_array()));
      break;
      case 'DELETE':
        $tid = $this->tag->synonym_of ?? $this->tag->id;
        $Uses = Tagged::by_tag($tid);
        $UseCount = count($Uses);
        if (!isset($_REQUEST['sanityCheck']) && $UseCount > 0)
          Response::error(409, 'This tag is currently used on '.CoreUtils::makePlural('appearance', $UseCount, PREPEND_NUMBER).'. Deleting will permanently remove the tag from those appearances. Repeat the request with sanitycheck set to confirm.', ['uses' => $UseCount]);

        $this->tag->delete();

        if (!empty(CGUtils::GROUP_TAG_IDS_ASSOC[$this->guide][$this->tag->id]))
          Appearances::getSortReorder($this->guide);
        foreach ($Uses as $use)
          $use->appearance->updateIndex();

        Response::noContent();
      break;
      case 'POST':
      case 'PUT':
        $data['name'] = CGUtils::validateTagName('name');

        $type = (new Input('type', function ($value) {
          if (!isset(Tags::TAG_TYPES[$value]))
            return Input::ERROR_INVALID;
        }, [
          Input::IS_OPTIONAL => true,
          Input::CUSTOM_ERROR_MESSAGES => [
            Input::ERROR_INVALID => 'Invalid tag type: @value',
          ],
        ]))->out();
        if (!empty($type)){
          $data['type'] = $type;
        }

        if (!$this->creating)
          DB::$instance->where('id', $this->tag->id, '!=');
        DB::$instance->where('name', $data['name']);
        if (isset($data['type']))
          DB::$instance->where('type', $data['type']);
        else DB::$instance->where('type IS NULL');
        if (DB::$instance->has('tags'))
          Response::invalid('name', 'A tag with the same name and type already exists');

        $data['title'] = (new Input('title', 'string', [
          Input::IS_OPTIONAL => true,
          Input::IN_RANGE => [null, 255],
          Input::CUSTOM_ERROR_MESSAGES => [
            Input::ERROR_RANGE => 'Tag title cannot be longer than @max characters',
          ],
        ]))->out();

        if ($this->creating){
          $Tag = new Tag($data);
          if (!$Tag->save())
            Response::dbError(status: 500);

          $created = CoreUtils::camelKeys($Tag->to_array());
          // The id comes back from the insert as a string
          $created['id'] = (int)$created['id'];
          $appearance_id = (new Input('addTo', 'int', [Input::IS_OPTIONAL => true]))->out();
          if ($appearance_id !== null){
            if ($appearance_id === 0)
              Response::ok($created + ['warning' => "The tag was created, but it could not be added to the appearance because it can't be tagged."], 201);

            $Appearance = Appearance::find($appearance_id);
            if (empty($Appearance))
              Response::ok($created + ['warning' => "The tag was created, but it could not be added to the appearance (#$appearance_id) because it doesn't seem to exist. Please try adding the tag manually."], 201);

            $Appearance->addTag($Tag)->updateIndex();
            Response::ok($created + ['tags' => $Appearance->getTagsHTML(NOWRAP)], 201);
          }
          Response::ok($created, 201);
        }
        else {
          $this->tag->update_attributes($data);
          $data = $this->tag->to_array();
          $tag_relations = Tagged::by_tag($this->tag->id);
          foreach ($tag_relations as $tagged){
            $tagged->appearance->updateIndex();
          }
        }

        Response::ok(CoreUtils::camelKeys($data));
      break;
      default:
        CoreUtils::notAllowed();
    }
  }

  /**
   * @OA\Put(
   *   path="/tags/{id}/synonym",
   *   description="Mark a tag as a synonym of another tag, merging their usages. Staff only.",
   *   tags={"tags"},
   *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\RequestBody(required=true, @OA\JsonContent(
   *     required={"targetId"},
   *     @OA\Property(property="targetId", ref="#/components/schemas/OneBasedId", description="ID of the tag to become a synonym of")
   *   )),
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(type="object", additionalProperties=false,
   *         @OA\Property(property="target", ref="#/components/schemas/Tag")
   *       )
   *   ),
   *   @OA\Response(response="401", description="Not signed in", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="403", description="Insufficient permission (staff required)", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="404", description="Tag does not exist", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="409", description="Either tag is already a synonym", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="422", description="Target tag missing or does not exist", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse"))
   * )
   * @OA\Delete(
   *   path="/tags/{id}/synonym",
   *   description="Remove a tag's synonym relationship, restoring it as a standalone tag. Staff only.",
   *   tags={"tags"},
   *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\Parameter(name="keepTagged", in="query", description="If present, the tag will be reapplied to all appearances tagged with its synonym target", @OA\Schema(type="string")),
   *   @OA\Response(
   *     response="200",
   *     description="Synonym removed",
   *     @OA\JsonContent(type="object", additionalProperties=false,
   *         @OA\Property(property="keepTagged", type="boolean")
   *       )
   *   ),
   *   @OA\Response(response="204", description="The tag was not a synonym, nothing changed"),
   *   @OA\Response(response="401", description="Not signed in", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="403", description="Insufficient permission (staff required)", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="404", description="Tag does not exist", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
   * )
   */
  public function synonymApi($params) {
    $this->load_tag($params);

    switch ($this->action){
      case 'PUT':
        if ($this->tag->synonym_of !== null)
          Response::error(409, "The selected tag is already a synonym of the \"{$this->tag->synonym->name}\" (".Tags::TAG_TYPES[$this->tag->synonym->type].') tag');

        $target_id = (new Input('targetId', 'int', [
          Input::CUSTOM_ERROR_MESSAGES => [
            Input::ERROR_MISSING => 'Target tag ID is missing',
            Input::ERROR_INVALID => 'Target tag ID is invalid',
          ],
        ]))->out();
        $target = Tag::find($target_id);
        if (empty($target))
          Response::invalid('targetId', 'Target tag does not exist');
        if ($target->synonym_of !== null)
          Response::error(409, "The target tag is already a synonym of the \"{$target->synonym->name}\" (".Tags::TAG_TYPES[$target->synonym->type].') tag');

        $target_tagged = Tagged::by_tag($target->id);
        $tagged_appearance_ids = [];
        foreach ($target_tagged as $tg)
          $tagged_appearance_ids[] = $tg->appearance_id;

        $tagged = Tagged::by_tag($this->tag->id);
        foreach ($tagged as $tg){
          if (in_array($tg->appearance_id, $tagged_appearance_ids, true))
            continue;

          if (!Tagged::make($target->id, $tg->appearance_id)->save())
            Response::error(500, 'Creating tag synonym failed, please retry. Technical details: '.$tg->to_json());
        }
        Tagged::delete_all(['conditions' => ['tag_id = ?', $this->tag->id]]);
        $this->tag->update_attributes([
          'synonym_of' => $target->id,
          'uses' => 0,
        ]);
        if (!empty($tagged_appearance_ids)){
          $tagged_appearances = Appearance::find('all', [
            'conditions' => [
              'id IN (?)',
              $tagged_appearance_ids,
            ],
          ]);
          foreach ($tagged_appearances as $tapp)
            $tapp->updateIndex();
        }

        $target->updateUses();
        Response::ok(['message' => 'Tag synonyms created', 'target' => CoreUtils::camelKeys($target->to_array())]);
      break;
      case 'DELETE':
        if ($this->tag->synonym_of === null)
          Response::noContent();

        if ($this->tag->synonym){
          $keep_tagged = isset($_REQUEST['keepTagged']);
          if ($keep_tagged){
            $target_tagged = Tagged::by_tag($this->tag->synonym->id);
            foreach ($target_tagged as $tg)
              $tg->appearance->addTag($this->tag);
          }
        }
        else $keep_tagged = false;

        if (!$this->tag->update_attributes(['synonym_of' => null]))
          Response::dbError('Could not update tag', status: 500);

        foreach ($this->tag->appearances as $app)
          $app->updateIndex();

        Response::ok(['keepTagged' => $keep_tagged]);
      break;
      default:
        CoreUtils::notAllowed();
    }
  }
}
