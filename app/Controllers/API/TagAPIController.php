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
use function count;
use function in_array;

class TagAPIController extends APIController {
  use ColorGuideAccessTrait;

  public function __construct() {
    parent::__construct();

    $this->_initAppearancePageState();
  }

  public function list() {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    $size = $_GET['size'] ?? 50;
    if (!is_numeric($size) || $size < 1 || $size > 100)
      Response::invalid('size', 'The size must be between 1 and 100.');
    $size = (int)$size;
    $page = $_GET['page'] ?? 1;
    if (!is_numeric($page) || $page < 1)
      Response::invalid('page', 'The page must be at least 1.');
    $page = (int)$page;

    $total = DB::$instance->count('tags');
    $tags = Tags::get([($page - 1) * $size, $size]);

    Response::ok([
      'tags' => array_map(fn(Tag $t) => [
        'id' => $t->id,
        'name' => $t->name,
        'type' => $t->type,
        'title' => $t->title,
        'uses' => $t->uses,
        'synonymOf' => $t->synonym_of === null ? null : ['id' => $t->synonym->id, 'name' => $t->synonym->name],
      ], $tags),
      'pagination' => [
        'currentPage' => $page,
        'totalPages' => max(1, (int)ceil($total / $size)),
        'totalItems' => $total,
        'itemsPerPage' => $size,
      ],
      'canEdit' => Permission::sufficient('staff'),
    ]);
  }

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
        if (!CoreUtils::requestFlag('sanityCheck') && $UseCount > 0)
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
          $keep_tagged = CoreUtils::requestFlag('keepTagged');
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
