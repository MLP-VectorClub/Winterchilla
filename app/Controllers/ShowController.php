<?php

namespace App\Controllers;

use App\CoreUtils;
use App\DB;
use App\HTTP;
use App\Models\Show;
use App\Pagination;
use App\Permission;
use App\Regexes;
use App\ShowHelper;
use League\Uri\Modifier;
use OpenApi\Annotations as OA;

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
}
