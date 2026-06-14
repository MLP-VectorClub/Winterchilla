<?php

namespace App\Controllers;

use App\Auth;
use App\CGUtils;
use App\CoreUtils;
use App\DB;
use App\DeviantArt;
use App\HTTP;
use App\Input;
use App\Models\Appearance;
use App\Models\PinnedAppearance;
use App\Models\Show;
use App\Models\ShowAppearance;
use App\Models\ShowVote;
use App\Pagination;
use App\Permission;
use App\Posts;
use App\Regexes;
use App\Response;
use App\ShowHelper;
use App\Twig;
use DateInterval;
use Exception;
use League\Uri\Components\Query;
use League\Uri\Modifier;
use OpenApi\Annotations as OA;
use Throwable;

/**
 * @OA\Schema(
 *   schema="Show",
 *   type="object",
 *   description="Represents a show entry (episode or movie/special)",
 *   additionalProperties=false,
 *   @OA\Property(property="id", type="integer", example=1),
 *   @OA\Property(property="type", type="string", enum={"episode","movie","short","special"}),
 *   @OA\Property(property="season", type="integer", nullable=true),
 *   @OA\Property(property="episode", type="integer", nullable=true),
 *   @OA\Property(property="parts", type="integer", description="Number of parts the episode is split into (1 or 2)"),
 *   @OA\Property(property="no", type="integer", nullable=true, description="Overall number"),
 *   @OA\Property(property="title", type="string"),
 *   @OA\Property(property="airs", type="string", format="date-time"),
 *   @OA\Property(property="aired", type="boolean"),
 *   @OA\Property(property="notes", type="string", nullable=true),
 *   @OA\Property(property="posted_by", type="integer", description="ID of the user who created this show entry"),
 * )
 */
class ShowController extends Controller {
  public function index() {
    $base_path = '/show';
    $episodes_pagination = new Pagination($base_path, 8, Show::count(['conditions' => "type = 'episode'"]), 'ep');
    $show_pagination = new Pagination($base_path, 8, Show::count(['conditions' => "type != 'episode'"]));

    DB::$instance->orderBy('no', 'DESC');
    $episodes = ShowHelper::get($episodes_pagination->getLimit(), "type = 'episode'", true);
    DB::$instance->orderBy('no', 'DESC');
    $movies = ShowHelper::get($show_pagination->getLimit(), "type != 'episode'", true);

    $path = $episodes_pagination->toURI();
    $path = Modifier::wrap($path)->appendQuery($show_pagination->getPageQueryString())->unwrap();
    CoreUtils::fixPath($path);
    $heading = 'Episodes & Movies';

    $settings = [
      'heading' => $heading,
      'title' => $heading,
      'css' => [true],
      'js' => ['paginate', true],
      'import' => [
        'episodes_pagination' => $episodes_pagination,
        'show_pagination' => $show_pagination,
        'episodes' => $episodes,
        'movies' => $movies,
      ],
    ];
    if (Permission::sufficient('staff')){
      $settings['js'][] = 'pages/show/index-manage';
      $settings['import']['export'] = [
        'episodeTitleRegex' => Regexes::$ep_title,
        'showTypes' => ShowHelper::VALID_TYPES,
      ];
    }
    CoreUtils::loadPage(__METHOD__, $settings);
  }

  public function latest():void {
    $latest_episode = ShowHelper::getLatest();
    if (empty($latest_episode))
      CoreUtils::loadPage(__CLASS__.'::view', [
        'title' => 'Home',
      ]);

    HTTP::tempRedirect($latest_episode->toURL());
  }

  public function viewEpisode($params):void {
    if (empty($params['id']))
      CoreUtils::notFound();

    $ep_data = Show::parseID($params['id']);

    $current_episode = empty($ep_data)
      ? ShowHelper::getLatest()
      : ShowHelper::getActual($ep_data['season'], $ep_data['episode']);

    ShowHelper::loadPage($current_episode);
  }

  public function viewById($params):void {
    ShowHelper::loadPage(Show::find($params['id']));
  }

  /** @var Show */
  private $show;

  private function load_show($params):void {
    $this->show = Show::find($params['id']);
    if (empty($this->show))
      CoreUtils::notFound();
  }

  /**
   * @OA\Get(
   *   path="/show/{id}/posts",
   *   description="Get the rendered HTML for the requests or reservations section of a show's page",
   *   tags={"shows","posts"},
   *   security={},
   *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\Parameter(name="section", in="query", required=true, @OA\Schema(type="string", enum={"requests","reservations"})),
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(
   *       allOf={
   *         @OA\Schema(ref="#/components/schemas/ServerResponse"),
   *         @OA\Schema(
   *           type="object",
   *           required={"render"},
   *           additionalProperties=false,
   *           @OA\Property(property="render", type="string", description="Rendered HTML for the requested section")
   *         )
   *       }
   *     )
   *   ),
   *   @OA\Response(response="404", description="Show not found", @OA\JsonContent(ref="#/components/schemas/ServerResponse"))
   * )
   */
  public function postList($params):void {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    $this->load_show($params);

    $section = $_GET['section'];
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
      default:
        Response::fail('This should never happen');
    }
    Response::done(['render' => $rendered]);
  }

  /**
   * @OA\Get(
   *   path="/show/{id}",
   *   description="Get information about a single show entry",
   *   tags={"shows"},
   *   security={},
   *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(
   *       allOf={
   *         @OA\Schema(ref="#/components/schemas/ServerResponse"),
   *         @OA\Schema(
   *           type="object",
   *           required={"show"},
   *           additionalProperties=false,
   *           @OA\Property(property="show", ref="#/components/schemas/Show")
   *         )
   *       }
   *     )
   *   ),
   *   @OA\Response(response="404", description="Show not found", @OA\JsonContent(ref="#/components/schemas/ServerResponse"))
   * )
   * @OA\Post(
   *   path="/show",
   *   description="Create a new show entry (episode or movie/special). Requires staff permissions.",
   *   tags={"shows"},
   *   @OA\RequestBody(
   *     required=true,
   *     @OA\JsonContent(
   *       type="object",
   *       required={"type","title","airs"},
   *       @OA\Property(property="type", type="string", description="One of the valid show types (e.g. episode, movie, short, special)"),
   *       @OA\Property(property="season", type="integer", description="Required if type is episode"),
   *       @OA\Property(property="episode", type="integer", description="Required if type is episode"),
   *       @OA\Property(property="twoparter", type="boolean", description="If set, marks the episode as the first part of a two-part episode"),
   *       @OA\Property(property="no", type="integer", description="Overall number"),
   *       @OA\Property(property="title", type="string", minLength=5, maxLength=100),
   *       @OA\Property(property="airs", type="string", description="Air date & time, parsed as a timestamp"),
   *       @OA\Property(property="notes", type="string", maxLength=1000, nullable=true)
   *     )
   *   ),
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(
   *       allOf={
   *         @OA\Schema(ref="#/components/schemas/ServerResponse"),
   *         @OA\Schema(
   *           type="object",
   *           required={"url"},
   *           additionalProperties=false,
   *           @OA\Property(property="url", type="string", format="uri", description="URL of the newly created show entry")
   *         )
   *       }
   *     )
   *   ),
   *   @OA\Response(response="403", description="Insufficient permissions", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="400", description="Validation error", @OA\JsonContent(ref="#/components/schemas/ServerResponse"))
   * )
   * @OA\Put(
   *   path="/show/{id}",
   *   description="Update an existing show entry. Requires staff permissions.",
   *   tags={"shows"},
   *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\RequestBody(
   *     required=true,
   *     @OA\JsonContent(
   *       type="object",
   *       required={"title","airs"},
   *       @OA\Property(property="season", type="integer"),
   *       @OA\Property(property="episode", type="integer"),
   *       @OA\Property(property="twoparter", type="boolean"),
   *       @OA\Property(property="type", type="string", description="Cannot be changed to episode via this interface if not already an episode"),
   *       @OA\Property(property="no", type="integer"),
   *       @OA\Property(property="title", type="string", minLength=5, maxLength=100),
   *       @OA\Property(property="airs", type="string"),
   *       @OA\Property(property="notes", type="string", maxLength=1000, nullable=true)
   *     )
   *   ),
   *   @OA\Response(response="200", description="OK", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="403", description="Insufficient permissions", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="404", description="Show not found", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="400", description="Validation error", @OA\JsonContent(ref="#/components/schemas/ServerResponse"))
   * )
   * @OA\Delete(
   *   path="/show/{id}",
   *   description="Delete a show entry. Requires staff permissions.",
   *   tags={"shows"},
   *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(
   *       allOf={
   *         @OA\Schema(ref="#/components/schemas/ServerResponse"),
   *         @OA\Schema(
   *           type="object",
   *           required={"upcoming"},
   *           additionalProperties=false,
   *           @OA\Property(property="upcoming", type="string", description="Rendered HTML for the sidebar's upcoming episode info")
   *         )
   *       }
   *     )
   *   ),
   *   @OA\Response(response="403", description="Insufficient permissions", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="404", description="Show not found", @OA\JsonContent(ref="#/components/schemas/ServerResponse"))
   * )
   */
  public function api($params):void {
    if ($this->action !== 'GET' && Permission::insufficient('staff'))
      Response::fail();

    if (!$this->creating)
      $this->load_show($params);

    switch ($this->action){
      case 'GET':
        Response::done([ 'show' => $this->show->to_array() ]);
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
                Response::fail("There's already an episode with the same season & episode number");
            }
          }

          $update['parts'] = 1;
          if (isset($_REQUEST['twoparter'])){
            $next_part = Show::find_by_season_and_episode($update['season'], $update['episode'] + 1);
            if (!empty($next_part))
              Response::fail("This episode cannot have two parts because {$next_part->toURL()} already exists.");
            $update['parts'] = 2;
          }
        }
        else if (!$this->creating){
          $update['type'] = ShowHelper::validateType();
          if ($update['type'] === 'episode')
            Response::fail('Show entries cannot be converted to episodes via the interface.');
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
        CoreUtils::checkStringValidity($update['title'], "$what title", INVERSE_EP_TITLE_PATTERN);

        $airs = (new Input('airs', 'timestamp', [
          Input::CUSTOM_ERROR_MESSAGES => [
            Input::ERROR_MISSING => 'No air date & time specified',
            Input::ERROR_INVALID => 'Invalid air date and/or time (@value) specified',
          ],
        ]))->out();
        if (empty($airs))
          Response::fail('Please specify an air date & time');
        if ($airs < strtotime('2010-10-10T00:00:00'))
          Response::fail('Air dates before October 10th, 2010 are invalid.');
        $update['airs'] = date('c', strtotime('this minute', $airs));

        $notes = (new Input('notes', 'text', [
          Input::IS_OPTIONAL => true,
          Input::IN_RANGE => [null, 1000],
          Input::CUSTOM_ERROR_MESSAGES => [
            Input::ERROR_RANGE => "$what notes cannot be longer than @max characters",
          ],
        ]))->out();
        if ($notes !== null){
          CoreUtils::checkStringValidity($notes, "$what notes");
          $notes = CoreUtils::sanitizeHtml($notes, ['a'], ['a.href']);
          if ($this->creating || $notes !== $this->show->notes)
            $update['notes'] = $notes;
        }
        else $update['notes'] = null;

        if ($this->creating){
          $this->show = new Show($update);
          if (!$this->show->save())
            Response::dbError('Show entry creation failed');

          Response::done(['url' => $this->show->toURL()]);
        }

        // Updating
        if (!DB::$instance->where('id', $this->show->id)->update(Show::$table_name, $update))
          Response::dbError('Updating show entry failed');

        Response::done();
      break;
      case 'DELETE':
        if (!DB::$instance->where('id', $this->show->id)->delete(Show::$table_name))
          Response::dbError();

        Response::success('Episode deleted successfully', [
          'upcoming' => CoreUtils::getSidebarUpcoming(NOWRAP),
        ]);
      break;
      default:
        CoreUtils::notAllowed();
    }
  }

  /**
   * @OA\Get(
   *   path="/show/{id}/vote",
   *   description="Get the current voting status/results for an episode, either as rendered HTML (if `html` is set) or as vote counts grouped by score",
   *   tags={"shows"},
   *   security={},
   *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\Parameter(name="html", in="query", required=false, @OA\Schema(type="string"), description="If present, returns the rendered sidebar voting HTML instead of vote counts"),
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(
   *       allOf={
   *         @OA\Schema(ref="#/components/schemas/ServerResponse"),
   *         @OA\Schema(
   *           type="object",
   *           additionalProperties=false,
   *           @OA\Property(property="html", type="string", description="Rendered sidebar voting HTML (only present if `html` query param was set)"),
   *           @OA\Property(
   *             property="data",
   *             type="object",
   *             description="Map of vote values (1-5) to the number of votes received for that value (only present if `html` query param was not set)",
   *             additionalProperties=@OA\Property(type="integer")
   *           )
   *         )
   *       }
   *     )
   *   ),
   *   @OA\Response(response="404", description="Show not found", @OA\JsonContent(ref="#/components/schemas/ServerResponse"))
   * )
   * @OA\Post(
   *   path="/show/{id}/vote",
   *   description="Cast a vote for an episode. Requires the user to be signed in, the episode to have already aired, and the user to not have voted before.",
   *   tags={"shows"},
   *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\RequestBody(
   *     required=true,
   *     @OA\JsonContent(
   *       type="object",
   *       required={"vote"},
   *       @OA\Property(property="vote", type="integer", minimum=1, maximum=5, description="The vote value to cast")
   *     )
   *   ),
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(
   *       allOf={
   *         @OA\Schema(ref="#/components/schemas/ServerResponse"),
   *         @OA\Schema(
   *           type="object",
   *           required={"newhtml"},
   *           additionalProperties=false,
   *           @OA\Property(property="newhtml", type="string", description="Updated rendered sidebar voting HTML")
   *         )
   *       }
   *     )
   *   ),
   *   @OA\Response(response="401", description="Not signed in", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="400", description="Episode hasn't aired yet, already voted, or invalid vote value", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="404", description="Show not found", @OA\JsonContent(ref="#/components/schemas/ServerResponse"))
   * )
   */
  public function voteApi($params):void {
    $this->load_show($params);

    switch ($this->action){
      case 'GET':
        if (isset($_REQUEST['html']))
          Response::done(['html' => ShowHelper::getSidebarVoting($this->show)]);

        $vote_count_query = DB::$instance->query(
          "SELECT count(*) as value, vote as label FROM show_votes WHERE show_id = ? GROUP BY vote ORDER BY vote", [$this->show->id]);
        $vote_counts = [];
        foreach ($vote_count_query as $row)
          $vote_counts[$row['label']] = $row['value'];

        Response::done(['data' => $vote_counts]);
      break;
      case 'POST':
        if (!Auth::$signed_in)
          Response::fail();

        if (!$this->show->aired)
          Response::fail('You can only vote on this episode after it has aired.');

        $user_vote = $this->show->getVoteOf(Auth::$user);
        if (!empty($user_vote))
          Response::fail("You already voted for this {$this->show->type}");

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
          Response::dbError();

        $this->show->updateScore();
        Response::done(['newhtml' => ShowHelper::getSidebarVoting($this->show)]);
      break;
      default:
        CoreUtils::notAllowed();
    }
  }

  /**
   * @OA\Get(
   *   path="/show/{id}/guide-relations",
   *   description="Get the list of color guide appearances that can be linked to this show, along with the appearances currently linked. Requires staff permissions.",
   *   tags={"shows","appearances","color guide"},
   *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(
   *       allOf={
   *         @OA\Schema(ref="#/components/schemas/ServerResponse"),
   *         @OA\Schema(
   *           type="object",
   *           required={"groups","entries","linkedIds"},
   *           additionalProperties=false,
   *           @OA\Property(property="groups", type="object", description="Map of color guide group keys to their display labels"),
   *           @OA\Property(
   *             property="entries",
   *             type="array",
   *             description="List of appearances eligible to be linked, excluding pinned/group-owned ones",
   *             @OA\Items(
   *               type="object",
   *               additionalProperties=false,
   *               @OA\Property(property="id", ref="#/components/schemas/OneBasedId"),
   *               @OA\Property(property="label", type="string"),
   *               @OA\Property(property="guide", type="string", nullable=true)
   *             )
   *           ),
   *           @OA\Property(
   *             property="linkedIds",
   *             type="array",
   *             description="IDs of the appearances currently linked to this show",
   *             @OA\Items(ref="#/components/schemas/OneBasedId")
   *           )
   *         )
   *       }
   *     )
   *   ),
   *   @OA\Response(response="403", description="Insufficient permissions", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="404", description="Show not found", @OA\JsonContent(ref="#/components/schemas/ServerResponse"))
   * )
   * @OA\Post(
   *   path="/show/{id}/guide-relations",
   *   description="Update the list of color guide appearances linked to this show. Requires staff permissions.",
   *   tags={"shows","appearances","color guide"},
   *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\RequestBody(
   *     @OA\JsonContent(
   *       type="object",
   *       @OA\Property(
   *         property="ids",
   *         type="array",
   *         description="List of appearance IDs that should be linked to this show. Existing relations not in this list are removed, missing ones are added.",
   *         @OA\Items(ref="#/components/schemas/OneBasedId")
   *       )
   *     )
   *   ),
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(
   *       allOf={
   *         @OA\Schema(ref="#/components/schemas/ServerResponse"),
   *         @OA\Schema(
   *           type="object",
   *           required={"section"},
   *           additionalProperties=false,
   *           @OA\Property(property="section", type="string", description="Rendered HTML for the show's linked appearances section")
   *         )
   *       }
   *     )
   *   ),
   *   @OA\Response(response="403", description="Insufficient permissions", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="404", description="Show not found", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="400", description="Validation error", @OA\JsonContent(ref="#/components/schemas/ServerResponse"))
   * )
   */
  public function guideRelationsApi($params):void {
    if (Permission::insufficient('staff'))
      Response::fail();

    $this->load_show($params);

    switch ($this->action){
      case 'GET':
        $columns = ['id', 'label', 'guide'];

        $linked_ids = [];
        foreach ($this->show->related_appearances as $p){
          $linked_ids[] = $p->id;
        }

        /** @var $appearances Appearance[] */
        $entries = DB::$instance->disableAutoClass()
          ->where('id', PinnedAppearance::getAllIds(), '!=')
          ->where('owner_id IS NULL')
          ->orderBy('label')
          ->get('appearances', null, $columns);

        Response::done([
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

        Response::done(['section' => ShowHelper::getAppearancesSectionHTML($this->show)]);
      break;
      default:
        CoreUtils::notAllowed();
    }
  }

  /**
   * @OA\Get(
   *   path="/show/next",
   *   description="Get information about the next upcoming episode that hasn't aired yet",
   *   tags={"shows"},
   *   security={},
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(
   *       allOf={
   *         @OA\Schema(ref="#/components/schemas/ServerResponse"),
   *         @OA\Schema(
   *           type="object",
   *           additionalProperties=false,
   *           @OA\Property(property="episode", type="integer"),
   *           @OA\Property(property="airs", type="string", format="date-time"),
   *           @OA\Property(property="season", type="integer"),
   *           @OA\Property(property="title", type="string")
   *         )
   *       }
   *     )
   *   ),
   *   @OA\Response(
   *     response="400",
   *     description="The show is on hiatus, no upcoming episode is known",
   *     @OA\JsonContent(
   *       allOf={
   *         @OA\Schema(ref="#/components/schemas/ServerResponse"),
   *         @OA\Schema(
   *           type="object",
   *           additionalProperties=false,
   *           @OA\Property(property="hiatus", type="boolean")
   *         )
   *       }
   *     )
   *   )
   * )
   */
  public function next():void {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    $next_episode = DB::$instance->where('season is not null AND airs > now()')->orderBy('airs')->getOne(Show::$table_name);
    if (empty($next_episode))
      Response::fail("The show is on hiatus, the next episode's title and air date is unknown.", ['hiatus' => true]);

    Response::done($next_episode->to_array([
      'only' => ['episode', 'airs', 'season', 'title'],
    ]));
  }

  /**
   * @OA\Get(
   *   path="/show/prefill",
   *   description="Get suggested values for creating the next episode entry, based on the most recently added one. Requires staff permissions.",
   *   tags={"shows"},
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(
   *       allOf={
   *         @OA\Schema(ref="#/components/schemas/ServerResponse"),
   *         @OA\Schema(
   *           type="object",
   *           required={"season","episode","no","airday"},
   *           additionalProperties=false,
   *           @OA\Property(property="season", type="integer"),
   *           @OA\Property(property="episode", type="integer"),
   *           @OA\Property(property="no", type="integer", description="Suggested overall number"),
   *           @OA\Property(property="airday", type="string", format="date", description="Suggested air date")
   *         )
   *       }
   *     )
   *   ),
   *   @OA\Response(response="403", description="Insufficient permissions", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="400", description="No last added episode found", @OA\JsonContent(ref="#/components/schemas/ServerResponse"))
   * )
   */
  public function prefill():void {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    if (Permission::insufficient('staff'))
      Response::fail();

    /** @var $last_added Show */
    $last_added = DB::$instance->orderBy('no', 'DESC')->where('season is not null')->getOne(Show::$table_name);
    if (empty($last_added))
      Response::fail('No last added episode found');

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
    Response::done([
      'season' => $season,
      'episode' => $episode,
      'no' => $last_added->no + $last_added->parts,
      'airday' => $airs,
    ]);
  }
}
