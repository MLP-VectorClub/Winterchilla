<?php

namespace App\Controllers\API;

use App\Auth;
use App\CGUtils;
use App\CoreUtils;
use App\DB;
use App\Input;
use App\Models\Appearance;
use App\Models\PinnedAppearance;
use App\Models\Show;
use App\Models\ShowAppearance;
use App\Models\ShowVote;
use App\Permission;
use App\Posts;
use App\Regexes;
use App\Response;
use App\ShowHelper;
use DateInterval;

class ShowAPIController extends APIController {
  /** @var Show */
  private $show;

  private function load_show($params):void {
    $this->show = Show::find($params['id']);
    if (empty($this->show))
      CoreUtils::notFound();
  }

  public function postList($params):void {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    $this->load_show($params);

    $section = $_GET['section'] ?? null;
    if (!in_array($section, ['requests', 'reservations'], true))
      Response::invalid('section', 'The section must be either requests or reservations.');
    $only = $section === 'requests' ? ONLY_REQUESTS : ONLY_RESERVATIONS;

    switch ($only){
      case ONLY_REQUESTS:
        $requests = $this->show->getRequests();
        $rendered = Posts::getRequestsSection($requests);
      break;
      case ONLY_RESERVATIONS:
        $reservations = $this->show->getReservations();
        $rendered = Posts::getReservationsSection($reservations);
      break;
    }
    Response::ok(['render' => $rendered]);
  }

  public function list():void {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    $types = $_GET['types'] ?? null;
    if (!is_array($types) || count($types) === 0)
      Response::invalid('types', 'At least one show type is required.');
    foreach ($types as $type) {
      if (!is_string($type) || !isset(ShowHelper::VALID_TYPES[$type]))
        Response::invalid('types', 'One of the selected show types is invalid.');
    }
    $order = $_GET['order'] ?? null;
    if (!in_array($order, ['series', 'overall'], true))
      Response::invalid('order', 'The order must be either series or overall.');
    $size = $_GET['size'] ?? 8;
    if (!is_numeric($size) || $size < 1 || $size > 10)
      Response::invalid('size', 'The size must be between 1 and 10.');
    $size = (int)$size;
    $page = $_GET['page'] ?? 1;
    if (!is_numeric($page) || $page < 1)
      Response::invalid('page', 'The page must be at least 1.');
    $page = (int)$page;

    $conditions = ['type IN (?)', array_values($types)];
    foreach (['season', 'episode'] as $filter) {
      if (!isset($_GET[$filter]))
        continue;
      if (!is_string($_GET[$filter]) || !ctype_digit($_GET[$filter]))
        Response::invalid($filter, "The $filter must be a non-negative integer.");
      $conditions[0] .= " AND $filter = ?";
      $conditions[] = (int)$_GET[$filter];
    }
    $total = Show::count(['conditions' => $conditions]);
    $shows = Show::find('all', [
      'conditions' => $conditions,
      'order' => $order === 'series' ? 'season desc, episode desc' : 'no desc',
      'limit' => $size,
      'offset' => ($page - 1) * $size,
    ]);

    Response::ok([
      'show' => array_map(fn(Show $show) => self::mapShowListItem($show), $shows),
      'pagination' => [
        'currentPage' => $page,
        'totalPages' => max(1, (int)ceil($total / $size)),
        'totalItems' => $total,
        'itemsPerPage' => $size,
      ],
    ]);
  }

  public function latest():void {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    $latest = ShowHelper::getLatest();
    if (empty($latest))
      Response::error(404, 'Nothing has aired yet');

    Response::ok(self::mapShowListItem($latest));
  }

  static function mapShowListItem(Show $show):array {
    return [
      'id' => $show->id,
      'type' => $show->type,
      'title' => $show->title,
      'season' => $show->season,
      'episode' => $show->episode,
      'parts' => $show->parts,
      'no' => $show->no,
      'airs' => $show->airs !== null ? gmdate('c', $show->airs->getTimestamp()) : null,
    ];
  }

  public function api($params):void {
    if ($this->action !== 'GET' && Permission::insufficient('staff'))
      Response::denied();

    if (!$this->creating)
      $this->load_show($params);

    switch ($this->action){
      case 'GET':
        Response::ok([
          'show' => ['score' => $this->show->score === null ? null : (float)$this->show->score] + CoreUtils::camelKeys($this->show->to_array()) + [
            'aired' => $this->show->aired,
            'willAir' => $this->show->willair,
            'canEdit' => Permission::sufficient('staff'),
            'relatedAppearances' => array_map(
              fn(Appearance $a) => AppearancesAPIController::mapPreviewAppearance($a),
              array_filter($this->show->related_appearances, fn(Appearance $a) => !$a->private || Permission::sufficient('staff'))
            ),
          ],
        ]);
      break;
      case 'POST':
      case 'PUT':
        $update = [];
        if ($this->creating){
          $update['type'] = ShowHelper::validateType();
          $update['posted_by'] = Auth::$user->id;
          $is_episode = $update['type'] === 'episode';
          $what = CoreUtils::capitalize($update['type']);
        }
        else {
          $is_episode = $this->show->is_episode;
          $what = CoreUtils::capitalize($this->show->type);
        }

        if ($is_episode){
          $update['season'] = ShowHelper::validateSeason(ShowHelper::ALLOW_MOVIES);
          $update['episode'] = ShowHelper::validateEpisode(!$is_episode);

          if (!$this->creating) {
            $season_changed = $update['season'] !== $this->show->season;
            $episode_changed = $update['episode'] !== $this->show->episode;
            if ($season_changed || $episode_changed){
              $target = ShowHelper::getActual(
                $update['season'] ?? $this->show->season,
                $update['episode'] ?? $this->show->episode,
                ShowHelper::ALLOW_MOVIES
              );
              if (!empty($target))
                Response::error(409, "There's already an episode with the same season & episode number");
            }
          }

          $update['parts'] = 1;
          if (CoreUtils::requestFlag('twoparter')){
            $next_part = Show::find_by_season_and_episode($update['season'], $update['episode'] + 1);
            if (!empty($next_part))
              Response::error(409, "This episode cannot have two parts because {$next_part->toURL()} already exists.");
            $update['parts'] = 2;
          }
        }
        else if (!$this->creating){
          $update['type'] = ShowHelper::validateType();
          if ($update['type'] === 'episode')
            Response::invalid('type', 'Show entries cannot be converted to episodes via the interface.');
        }

        $update['no'] = (new Input('no', 'int', [
          Input::IS_OPTIONAL => true,
          Input::IN_RANGE => [1, null],
          Input::CUSTOM_ERROR_MESSAGES => [
            Input::ERROR_INVALID => 'Overall number (@value) is invalid',
            Input::ERROR_RANGE => 'Overall number cannot be less than @min',
          ],
        ]))->out();

        $update['title'] = (new Input('title', 'string', [
          Input::IN_RANGE => [5, 100],
          Input::CUSTOM_ERROR_MESSAGES => [
            Input::ERROR_MISSING => "$what title is missing",
            Input::ERROR_RANGE => "$what title must be between @min and @max characters",
          ],
        ]))->out();
        CoreUtils::checkStringValidity($update['title'], "$what title", INVERSE_EP_TITLE_PATTERN, field: 'title');

        $airs = (new Input('airs', 'timestamp', [
          Input::CUSTOM_ERROR_MESSAGES => [
            Input::ERROR_MISSING => 'No air date & time specified',
            Input::ERROR_INVALID => 'Invalid air date and/or time (@value) specified',
          ],
        ]))->out();
        if (empty($airs))
          Response::invalid('airs', 'Please specify an air date & time');
        if ($airs < strtotime('2010-10-10T00:00:00'))
          Response::invalid('airs', 'Air dates before October 10th, 2010 are invalid.');
        $update['airs'] = date('c', strtotime('this minute', $airs));

        $notes = (new Input('notes', 'text', [
          Input::IS_OPTIONAL => true,
          Input::IN_RANGE => [null, 1000],
          Input::CUSTOM_ERROR_MESSAGES => [
            Input::ERROR_RANGE => "$what notes cannot be longer than @max characters",
          ],
        ]))->out();
        if ($notes !== null){
          CoreUtils::checkStringValidity($notes, "$what notes", field: 'notes');
          $notes = CoreUtils::sanitizeHtml($notes, ['a'], ['a.href']);
          if ($this->creating || $notes !== $this->show->notes)
            $update['notes'] = $notes;
        }
        else $update['notes'] = null;

        if ($this->creating){
          $this->show = new Show($update);
          if (!$this->show->save())
            Response::dbError('Show entry creation failed', status: 500);

          Response::ok(['id' => $this->show->id, 'url' => $this->show->toURL()], 201);
        }

        // Updating
        if (!DB::$instance->where('id', $this->show->id)->update(Show::$table_name, $update))
          Response::dbError('Updating show entry failed', status: 500);

        Response::noContent();
      break;
      case 'DELETE':
        if (!DB::$instance->where('id', $this->show->id)->delete(Show::$table_name))
          Response::dbError(status: 500);

        Response::ok([
          'upcoming' => CoreUtils::getSidebarUpcoming(NOWRAP),
        ]);
      break;
      default:
        CoreUtils::notAllowed();
    }
  }

  /** @return object Rating to number of votes (an object, so an empty map serializes as `{}`) */
  private function voteCounts():object {
    $rows = DB::$instance->query(
      "SELECT count(*) as value, vote as label FROM show_votes WHERE show_id = ? GROUP BY vote ORDER BY vote", [$this->show->id]);
    $counts = [];
    foreach ($rows as $row)
      $counts[$row['label']] = (int)$row['value'];

    return (object)$counts;
  }

  public function voteApi($params):void {
    $this->load_show($params);

    switch ($this->action){
      case 'GET':
        if (isset($_REQUEST['html']))
          Response::ok(['html' => ShowHelper::getSidebarVoting($this->show)]);

        Response::ok(['data' => $this->voteCounts()]);
      break;
      case 'POST':
        if (!Auth::$signed_in)
          Response::error(401);

        if (!$this->show->aired)
          Response::error(409, 'You can only vote on this episode after it has aired.');

        $user_vote = $this->show->getVoteOf(Auth::$user);
        if (!empty($user_vote))
          Response::error(409, "You already voted for this {$this->show->type}");

        $vote_value = (new Input('vote', 'int', [
          Input::IN_RANGE => [1, 5],
          Input::CUSTOM_ERROR_MESSAGES => [
            Input::ERROR_MISSING => 'Vote value missing from request',
            Input::ERROR_RANGE => 'Vote value must be an integer between @min and @max (inclusive)',
          ],
        ]))->out();

        $vote = new ShowVote();
        $vote->show_id = $this->show->id;
        $vote->user_id = Auth::$user->id;
        $vote->vote = $vote_value;
        if (!$vote->save())
          Response::dbError(status: 500);

        $this->show->updateScore();
        Response::ok(['data' => $this->voteCounts(), 'newhtml' => ShowHelper::getSidebarVoting($this->show)]);
      break;
      default:
        CoreUtils::notAllowed();
    }
  }

  public function guideRelationsApi($params):void {
    if (Permission::insufficient('staff'))
      Response::denied();

    $this->load_show($params);

    switch ($this->action){
      case 'GET':
        $columns = ['id', 'label', 'guide'];

        $linked_ids = [];
        foreach ($this->show->related_appearances as $p){
          $linked_ids[] = $p->id;
        }

        // An empty NOT IN () list is a SQL syntax error, so only exclude pinned appearances if there are any
        $pinned_ids = PinnedAppearance::getAllIds();
        $query = DB::$instance->disableAutoClass();
        if (!empty($pinned_ids))
          $query->where('id', $pinned_ids, '!=');
        /** @var $appearances Appearance[] */
        $entries = $query
          ->where('owner_id IS NULL')
          ->orderBy('label')
          ->get('appearances', null, $columns);

        Response::ok([
          'groups' => CGUtils::GUIDE_MAP,
          'entries' => $entries,
          'linkedIds' => $linked_ids,
        ]);
      break;
      case 'PUT':
        /** @var $appearance_ids int[] */
        $appearance_ids = (new Input('ids', 'int[]', [
          Input::IS_OPTIONAL => true,
          Input::CUSTOM_ERROR_MESSAGES => [
            Input::ERROR_MISSING => 'Missing appearance ID list',
            Input::ERROR_INVALID => 'Appearance ID list is invalid',
          ],
        ]))->out();
        if (empty($appearance_ids))
          $appearance_ids = [];

        $existing_relation_ids = array_map(function ($p) { return $p->id; }, $this->show->related_appearances);

        $added = array_diff($appearance_ids, $existing_relation_ids);
        if (!empty($added)){
          foreach ($added as $appearance_id)
            ShowAppearance::makeRelation($this->show->id, $appearance_id);
        }

        $removed = array_diff($existing_relation_ids, $appearance_ids);
        if (!empty($removed))
          DB::$instance->where('show_id', $this->show->id)->where('appearance_id', $removed)->delete(ShowAppearance::$table_name);

        $this->show->reload();

        Response::ok(['section' => ShowHelper::getAppearancesSectionHTML($this->show)]);
      break;
      default:
        CoreUtils::notAllowed();
    }
  }

  public function next():void {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    $next_episode = DB::$instance->where('season is not null AND airs > now()')->orderBy('airs')->getOne(Show::$table_name);
    if (empty($next_episode))
      Response::error(404, "The show is on hiatus, the next episode's title and air date is unknown.", ['hiatus' => true]);

    Response::ok($next_episode->to_array([
      'only' => ['episode', 'airs', 'season', 'title'],
    ]));
  }

  public function prefill():void {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    if (Permission::insufficient('staff'))
      Response::denied();

    /** @var $last_added Show */
    $last_added = DB::$instance->orderBy('no', 'DESC')->where('season is not null')->getOne(Show::$table_name);
    if (empty($last_added))
      Response::error(404, 'No last added episode found');

    $season = $last_added->season;
    if ($last_added->parts === 2 && $last_added->episode + 1 === 26){
      $season++;
      $episode = 1;
      $airs = date('Y-m-d', strtotime('this saturday'));
    }
    else {
      $episode = min($last_added->episode + 1, 26);
      $airs = $last_added->airs->add(new DateInterval('P1W'))->format('Y-m-d');
    }
    Response::ok([
      'season' => $season,
      'episode' => $episode,
      'no' => $last_added->no + $last_added->parts,
      'airday' => $airs,
    ]);
  }
}
