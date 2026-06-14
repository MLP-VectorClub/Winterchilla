<?php

namespace App\Controllers;

use App\Appearances;
use App\CGUtils;
use App\CoreUtils;
use App\DB;
use App\Input;
use App\Models\Appearance;
use App\Models\Tag;
use App\Models\Tagged;
use App\Pagination;
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
 *     "synonym_of"
 *   },
 *   additionalProperties=false,
 *   @OA\Property(property="id", ref="#/components/schemas/OneBasedId"),
 *   @OA\Property(property="name", type="string", description="The tag's name"),
 *   @OA\Property(property="title", type="string", nullable=true, description="Optional human-friendly title for the tag"),
 *   @OA\Property(property="type", type="string", description="The tag's type/category"),
 *   @OA\Property(property="uses", type="integer", minimum=0, description="Number of appearances this tag is applied to"),
 *   @OA\Property(property="synonym_of", ref="#/components/schemas/OneBasedId", nullable=true, description="ID of the tag this one is a synonym of, if any")
 * )
 */
class TagController extends ColorGuideController {
  public function list() {
    $pagination = new Pagination('/cg/tags', 50, DB::$instance->count('tags'));

    CoreUtils::fixPath($pagination->toURI());
    $heading = 'All Tags';
    $title = "Page {$pagination->getPage()} - $heading - Color Guide";

    $tags = Tags::get($pagination->getLimit());

    $js = ['paginate'];
    if (Permission::sufficient('staff'))
      $js[] = true;

    CoreUtils::loadPage('ColorGuideController::tagList', [
      'title' => $title,
      'heading' => $heading,
      'css' => [true],
      'js' => $js,
      'import' => [
        'tags' => $tags,
        'pagination' => $pagination,
      ],
    ]);
  }

  /**
   * @OA\Get(
   *   path="/cg/tags",
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
   *       @OA\Property(property="synonym_of", ref="#/components/schemas/OneBasedId", nullable=true),
   *       @OA\Property(property="synonym_target", type="string", description="Name of the tag this one is a synonym of, only present when found via autocomplete")
   *     ))
   *   ),
   *   @OA\Response(response="403", description="Insufficient permission (staff required)", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="400", description="Tag is already a synonym of another tag", @OA\JsonContent(allOf={
   *     @OA\Schema(ref="#/components/schemas/ServerResponse"),
   *     @OA\Schema(type="object", @OA\Property(property="undo", type="boolean"))
   *   }))
   * )
   */
  public function autocomplete() {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    if (Permission::insufficient('staff'))
      Response::fail();

    $except = (new Input('not', 'int', [Input::IS_OPTIONAL => true]))->out();
    if ((new Input('action', 'string', [Input::IS_OPTIONAL => true]))->out() === 'synon'){
      if ($except !== null)
        DB::$instance->where('id', $except);
      /** @var $Tag Tag */
      $Tag = DB::$instance->where('"synonym_of" IS NOT NULL')->getOne('tags');
      if (!empty($Tag))
        Response::fail("This tag is already a synonym of <strong>{$Tag->synonym->name}</strong>.<br>Would you like to remove the synonym?", ['undo' => true]);
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

    CGUtils::autocompleteRespond(empty($Tags) ? '[]' : $Tags);
  }

  /**
   * @OA\Post(
   *   path="/cg/tags/recount-uses",
   *   description="Recalculate the use counts of the given tags. Staff only.",
   *   tags={"tags"},
   *   @OA\RequestBody(required=true, @OA\JsonContent(
   *     required={"tagids"},
   *     @OA\Property(property="tagids", type="array", description="IDs of tags to recount", @OA\Items(ref="#/components/schemas/OneBasedId"))
   *   )),
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(allOf={
   *       @OA\Schema(ref="#/components/schemas/ServerResponse"),
   *       @OA\Schema(type="object", additionalProperties=false,
   *         @OA\Property(property="counts", type="object", description="Map of tag ID to its new use count")
   *       )
   *     })
   *   ),
   *   @OA\Response(response="403", description="Insufficient permission (staff required)", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="400", description="Validation error", @OA\JsonContent(ref="#/components/schemas/ServerResponse"))
   * )
   */
  public function recountUses() {
    if ($this->action !== 'POST')
      CoreUtils::notAllowed();

    if (Permission::insufficient('staff'))
      Response::fail();

    /** @var $tagIDs int[] */
    $tagIDs = (new Input('tagids', 'int[]', [
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

    Response::success(
      (
      !$updates
        ? 'There was no change in the tag usage counts'
        : "$updates tag".($updates !== 1 ? "s'" : "'s").' use count'.($updates !== 1 ? 's were' : ' was').' updated'
      ),
      ['counts' => $counts]
    );
  }

  /** @var Tag|null */
  private $tag;

  private function load_tag($params) {
    $this->_initialize($params);
    if (Permission::insufficient('staff'))
      CoreUtils::noPerm();

    if (!$this->creating){
      if (!isset($params['id']))
        Response::fail('Missing tag ID');
      $id = (int)$params['id'];
      $this->tag = Tag::find($id);
      if (empty($this->tag))
        Response::fail('This tag does not exist');
    }
  }

  /**
   * @OA\Get(
   *   path="/cg/tag/{id}",
   *   description="Get a tag's details. Staff only.",
   *   tags={"tags"},
   *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(allOf={
   *       @OA\Schema(ref="#/components/schemas/ServerResponse"),
   *       @OA\Schema(ref="#/components/schemas/Tag")
   *     })
   *   ),
   *   @OA\Response(response="403", description="Insufficient permission (staff required)", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="400", description="Tag does not exist", @OA\JsonContent(ref="#/components/schemas/ServerResponse"))
   * )
   * @OA\Post(
   *   path="/cg/tag",
   *   description="Create a new tag, optionally adding it to an appearance. Staff only.",
   *   tags={"tags"},
   *   @OA\RequestBody(required=true, @OA\JsonContent(
   *     required={"name"},
   *     @OA\Property(property="name", type="string", description="Tag name"),
   *     @OA\Property(property="type", type="string", description="Tag type/category"),
   *     @OA\Property(property="title", type="string", maxLength=255, nullable=true, description="Optional human-friendly title"),
   *     @OA\Property(property="addto", ref="#/components/schemas/ZeroBasedId", description="ID of an appearance to add the new tag to; 0 means the tag cannot be applied")
   *   )),
   *   @OA\Response(
   *     response="200",
   *     description="OK. If 'addto' was specified and valid, only a success message and 'tags' are returned (no tag fields); if 'addto' pointed to an invalid/untaggable appearance, only a success message is returned. Otherwise the submitted tag fields are echoed back (without id, uses or synonym_of).",
   *     @OA\JsonContent(allOf={
   *       @OA\Schema(ref="#/components/schemas/ServerResponse"),
   *       @OA\Schema(type="object",
   *         @OA\Property(property="name", type="string", description="Tag name, present when 'addto' was not specified or invalid"),
   *         @OA\Property(property="type", type="string", description="Tag type/category, present when 'addto' was not specified or invalid"),
   *         @OA\Property(property="title", type="string", nullable=true, description="Optional human-friendly title, present when 'addto' was not specified or invalid"),
   *         @OA\Property(property="tags", type="string", description="Rendered HTML of the appearance's tags, present when addto was specified and valid")
   *       )
   *     })
   *   ),
   *   @OA\Response(response="403", description="Insufficient permission (staff required)", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="400", description="A tag with the same name and type already exists, or validation error", @OA\JsonContent(ref="#/components/schemas/ServerResponse"))
   * )
   * @OA\Put(
   *   path="/cg/tag/{id}",
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
   *     @OA\JsonContent(allOf={
   *       @OA\Schema(ref="#/components/schemas/ServerResponse"),
   *       @OA\Schema(ref="#/components/schemas/Tag")
   *     })
   *   ),
   *   @OA\Response(response="403", description="Insufficient permission (staff required)", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="400", description="A tag with the same name and type already exists, or validation error", @OA\JsonContent(ref="#/components/schemas/ServerResponse"))
   * )
   * @OA\Delete(
   *   path="/cg/tag/{id}",
   *   description="Delete a tag (or its synonym target if it's a synonym). Staff only. If the tag is in use, a confirmation must be sent via the 'sanitycheck' parameter.",
   *   tags={"tags"},
   *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\Parameter(name="sanitycheck", in="query", description="Set to confirm deletion of an in-use tag", @OA\Schema(type="string")),
   *   @OA\Response(response="200", description="OK", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="403", description="Insufficient permission (staff required)", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="400", description="Tag does not exist, or confirmation required", @OA\JsonContent(allOf={
   *     @OA\Schema(ref="#/components/schemas/ServerResponse"),
   *     @OA\Schema(type="object", @OA\Property(property="confirm", type="boolean"))
   *   }))
   * )
   */
  public function api($params) {
    $this->load_tag($params);

    switch ($this->action){
      case 'GET':
        Response::done($this->tag->to_array());
      break;
      case 'DELETE':
        $tid = $this->tag->synonym_of ?? $this->tag->id;
        $Uses = Tagged::by_tag($tid);
        $UseCount = count($Uses);
        if (!isset($_REQUEST['sanitycheck']) && $UseCount > 0)
          Response::fail('<p>This tag is currently used on '.CoreUtils::makePlural('appearance', $UseCount, PREPEND_NUMBER).'</p><p>Deleting will <strong class="color-red">permanently remove</strong> the tag from those appearances!</p><p>Are you <em class="color-red">REALLY</em> sure about this?</p>', ['confirm' => true]);

        $this->tag->delete();

        if (!empty(CGUtils::GROUP_TAG_IDS_ASSOC[$this->guide][$this->tag->id]))
          Appearances::getSortReorder($this->guide);
        foreach ($Uses as $use)
          $use->appearance->updateIndex();

        Response::success('Tag deleted successfully');
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
        if (DB::$instance->where('name', $data['name'])->where('type', $data['type'])->has('tags'))
          Response::fail('A tag with the same name and type already exists');

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
            Response::dbError();

          $appearance_id = (new Input('addto', 'int', [Input::IS_OPTIONAL => true]))->out();
          if ($appearance_id !== null){
            if ($appearance_id === 0)
              Response::success("The tag was created, <strong>but</strong> it could not be added to the appearance because it can't be tagged.");

            $Appearance = Appearance::find($appearance_id);
            if (empty($Appearance))
              Response::success("The tag was created, <strong>but</strong> it could not be added to the appearance (<a href='/cg/v/$appearance_id'>#$appearance_id</a>) because it doesn't seem to exist. Please try adding the tag manually.");

            $Appearance->addTag($Tag)->updateIndex();
            Response::done(['tags' => $Appearance->getTagsHTML(NOWRAP)]);
          }
        }
        else {
          $this->tag->update_attributes($data);
          $data = $this->tag->to_array();
          $tag_relations = Tagged::by_tag($this->tag->id);
          foreach ($tag_relations as $tagged){
            $tagged->appearance->updateIndex();
          }
        }

        Response::done($data);
      break;
      default:
        CoreUtils::notAllowed();
    }
  }

  /**
   * @OA\Put(
   *   path="/cg/tag/{id}/synonym",
   *   description="Mark a tag as a synonym of another tag, merging their usages. Staff only.",
   *   tags={"tags"},
   *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\RequestBody(required=true, @OA\JsonContent(
   *     required={"target_id"},
   *     @OA\Property(property="target_id", ref="#/components/schemas/OneBasedId", description="ID of the tag to become a synonym of")
   *   )),
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(allOf={
   *       @OA\Schema(ref="#/components/schemas/ServerResponse"),
   *       @OA\Schema(type="object", additionalProperties=false,
   *         @OA\Property(property="target", ref="#/components/schemas/Tag")
   *       )
   *     })
   *   ),
   *   @OA\Response(response="403", description="Insufficient permission (staff required)", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="400", description="Tag does not exist, target does not exist, or either is already a synonym, or validation error", @OA\JsonContent(ref="#/components/schemas/ServerResponse"))
   * )
   * @OA\Delete(
   *   path="/cg/tag/{id}/synonym",
   *   description="Remove a tag's synonym relationship, restoring it as a standalone tag. Staff only.",
   *   tags={"tags"},
   *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\Parameter(name="keep_tagged", in="query", description="If present, the tag will be reapplied to all appearances tagged with its synonym target", @OA\Schema(type="string")),
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(allOf={
   *       @OA\Schema(ref="#/components/schemas/ServerResponse"),
   *       @OA\Schema(type="object", additionalProperties=false,
   *         @OA\Property(property="keep_tagged", type="boolean")
   *       )
   *     })
   *   ),
   *   @OA\Response(response="403", description="Insufficient permission (staff required)", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="400", description="Tag does not exist", @OA\JsonContent(ref="#/components/schemas/ServerResponse"))
   * )
   */
  public function synonymApi($params) {
    $this->load_tag($params);

    switch ($this->action){
      case 'PUT':
        if ($this->tag->synonym_of !== null)
          Response::fail("The selected tag is already a synonym of the \"{$this->tag->synonym->name}\" (".Tags::TAG_TYPES[$this->tag->synonym->type].') tag');

        $target_id = (new Input('target_id', 'int', [
          Input::CUSTOM_ERROR_MESSAGES => [
            Input::ERROR_MISSING => 'Target tag ID is missing',
            Input::ERROR_INVALID => 'Target tag ID is invalid',
          ],
        ]))->out();
        $target = Tag::find($target_id);
        if (empty($target))
          Response::fail('Target tag does not exist');
        if ($target->synonym_of !== null)
          Response::fail("The selected tag is already a synonym of the \"{$target->synonym->name}\" (".Tags::TAG_TYPES[$target->synonym->type].') tag');

        $target_tagged = Tagged::by_tag($target->id);
        $tagged_appearance_ids = [];
        foreach ($target_tagged as $tg)
          $tagged_appearance_ids[] = $tg->appearance_id;

        $tagged = Tagged::by_tag($this->tag->id);
        foreach ($tagged as $tg){
          if (in_array($tg->appearance_id, $tagged_appearance_ids, true))
            continue;

          if (!Tagged::make($target->id, $tg->appearance_id)->save())
            Response::fail('Creating tag synonym failed, please retry.<br>Technical details: '.$tg->to_json());
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
        Response::success('Tag synonyms created', ['target' => $target->to_array()]);
      break;
      case 'DELETE':
        if ($this->tag->synonym_of === null)
          Response::done();

        if ($this->tag->synonym){
          $keep_tagged = isset($_REQUEST['keep_tagged']);
          if ($keep_tagged){
            $target_tagged = Tagged::by_tag($this->tag->synonym->id);
            foreach ($target_tagged as $tg)
              $tg->appearance->addTag($this->tag);
          }
        }
        else $keep_tagged = false;

        if (!$this->tag->update_attributes(['synonym_of' => null]))
          Response::dbError('Could not update tag');

        foreach ($this->tag->appearances as $app)
          $app->updateIndex();

        Response::done(['keep_tagged' => $keep_tagged]);
      break;
      default:
        CoreUtils::notAllowed();
    }
  }
}
