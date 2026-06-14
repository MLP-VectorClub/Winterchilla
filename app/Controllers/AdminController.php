<?php

namespace App\Controllers;

use App\Auth;
use App\CoreUtils;
use App\DB;
use App\Logs;
use App\Models\Appearance;
use App\Models\Log;
use App\Models\Notice;
use App\Models\Post;
use App\Models\User;
use App\Pagination;
use App\Permission;
use App\Posts;
use Elasticsearch\Common\Exceptions\NoNodesAvailableException;
use IPTools\IP;
use League\Uri\Components\Query;
use League\Uri\Modifier;
use SeinopSys\PostgresDb;
use Throwable;
use function in_array;

class AdminController extends Controller {
  public function __construct() {
    parent::__construct();

    if (Permission::insufficient('staff'))
      CoreUtils::noPerm();
  }

  public function index() {
    if (Permission::sufficient('developer')){
      try {
        $client = CoreUtils::elasticClient();
        $client->ping();
        $indices = $client->cat()->indices(['v' => true]);
        $nodes = $client->cat()->nodes(['v' => true]);

        $usedIndexes = ['appearances'];
        $index_list = '';
        foreach ($indices as $no => $index){
          if (!in_array($index['index'], $usedIndexes, true))
            continue;

          $index_list .= "#$no ";
          foreach ($index as $key => $value){
            if (empty($value))
              continue;
            $index_list .= "$key:$value ";
          }
          $index_list .= "\n";
        }
        $node_list = '';
        foreach ($nodes as $no => $node){
          $node_list .= "#$no ";
          foreach ($node as $key => $value){
            if (empty($value))
              continue;
            $node_list .= "$key:$value ";
          }
        }
        $elastic_down = false;
      }
      catch (NoNodesAvailableException $e){
        $elastic_down = true;
      }
    }

    CoreUtils::loadPage(__METHOD__, [
      'title' => 'Admin Area',
      'css' => [true],
      'js' => [true],
      'import' => [
        'elastic_down' => $elastic_down ?? true,
        'index_list' => $index_list ?? null,
        'node_list' => $node_list ?? null,
        'recent_posts' => Posts::getRecentPosts(),
      ],
    ]);
  }

  public function log() {
    $type = Logs::validateEntryType('type', true, true);
    /** @noinspection NotOptimalIfConditionsInspection */
    if (isset($_GET['type']) && preg_match('/^[a-z_]+$/', $_GET['type']) && isset(Logs::LOG_DESCRIPTION[$_GET['type']]))
      $type = $_GET['type'];

    $ip = null;
    if (!isset($_GET['by']))
      $by = null;
    else {
      $_GET['by'] = strtolower(CoreUtils::trim($_GET['by']));
      switch ($_GET['by']){
        case 'me':
        case 'you':
          $initiator = Auth::$user->id;
          $by = 'you';
        break;
        case 'my ip':
        case 'your ip':
          $ip = $_SERVER['REMOTE_ADDR'];
        break;
        case 'web server':
          $initiator = 0;
          $by = 'Web server';
        break;
        default:
          $by = $_GET['by'] ?? null;
          if ($by !== null){
            $by = User::find(['name' => $by]);
            if ($by !== null){
              $initiator = $by->id;
              $by = $initiator === Auth::$user->id ? 'me' : $by->name;
            }
            else $by = null;
          }
          else if (!empty($_GET['by'])){
            try {
              $ip = IP::parse($_GET['by']);
            }
            catch (Throwable $e){
              // If we don't find a valid IP address then we just ignore the parameter
            }
            if ($ip !== null)
              $ip = (string)$ip;
          }
      }
    }

    $title = '';
    $where_args = [];
    $query_params = [];
    $remove_params = [];
    if ($type !== null){
      $where_args[] = ['entry_type', $type];
      $query_params['type'] = $type;
      $title .= Logs::LOG_DESCRIPTION[$type].' entries ';
    }
    else if (isset($query_params))
      $remove_params[] = 'type';
    if (isset($initiator)){
      $_params = $initiator === 0 ? ['"initiator" IS NULL'] : ['initiator', $initiator];
      $where_args[] = $_params;
      if (isset($by)){
        $query_params['by'] = $by;
        $title .= ($type === null ? 'Entries ' : '')."by $by ";
      }
    }
    else if (isset($ip)){
      $where_args[] = ['ip', in_array($ip, Logs::LOCALHOST_IPS, true) ? Logs::LOCALHOST_IPS : $ip];
      $query_params['by'] = $ip;
      $title .= ($type === null ? 'Entries ' : '')."from $ip ";
    }
    else $remove_params[] = 'by';

    foreach ($where_args as $arg)
      DB::$instance->where($arg[0], $arg[1] ?? PostgresDb::DBNULL);
    $pagination = new Pagination('/admin/logs', 40, DB::$instance->count(Log::$table_name));
    $heading = 'Global logs';
    if (!empty($title))
      $title .= '- ';
    $title .= "Page {$pagination->getPage()} - $heading - Admin Area";

    $path = $pagination->toURI();
    if (!empty($query_params))
      $path = Modifier::wrap($path)->appendQuery(Query::fromVariable($query_params))->unwrap();
    CoreUtils::fixPath($path, $remove_params);

    foreach ($where_args as $arg)
      DB::$instance->where(...$arg);
    $log_items = DB::$instance
      ->setModel(Log::class)
      ->orderBy('created_at', 'DESC')
      ->orderBy('id', 'DESC')
      ->get(Log::$table_name, $pagination->getLimit());

    $entry_types = Logs::LOG_DESCRIPTION;
    asort($entry_types);
    CoreUtils::loadPage(__METHOD__, [
      'heading' => $heading,
      'title' => $title,
      'css' => [true],
      'js' => [true, 'paginate'],
      'import' => [
        'pagination' => $pagination,
        'log_items' => $log_items,
        'type' => $type,
        'by' => $by ?? null,
        'ip' => $ip ?? null,
        'entry_types' => $entry_types,
      ],
    ]);
  }

  public function usefulLinks() {
    $heading = 'Manage useful links';
    CoreUtils::loadPage(__METHOD__, [
      'heading' => $heading,
      'title' => "$heading - Admin Area",
      'view' => [true],
      'libs' => ['sortable'],
      'js' => [true],
      'css' => [true],
    ]);
  }

  public function wsdiag() {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    if (Permission::insufficient('developer'))
      CoreUtils::noPerm();

    $heading = 'WebSocket Server Diagnostics';
    CoreUtils::loadPage(__METHOD__, [
      'heading' => $heading,
      'title' => "$heading - Admin Area",
      'js' => [true],
      'css' => [true],
      'import' => ['nav_wsdiag' => true],
    ]);
  }

  private function _setupPcgAppearances():PostgresDb {
    return DB::$instance->where('owner_id IS NOT NULL');
  }

  public function pcgAppearances() {
    $pagination = new Pagination('/admin/pcg-appearances', 10, $this->_setupPcgAppearances()->count(Appearance::$table_name));

    CoreUtils::fixPath($pagination->toURI());
    $heading = 'All PCG appearances';
    $title = "Page {$pagination->getPage()} - $heading - Color Guide";

    $appearances = $this->_setupPcgAppearances()->orderBy('created_at', 'DESC')->get(Appearance::$table_name, $pagination->getLimit());

    CoreUtils::loadPage(__METHOD__, [
      'title' => $title,
      'heading' => $heading,
      'css' => [true],
      'js' => ['paginate'],
      'import' => [
        'appearances' => $appearances,
        'pagination' => $pagination,
      ],
    ]);
  }

  public function notices() {
    $ItemsPerPage = 25;
    $pagination = new Pagination('/admin/notices', $ItemsPerPage, Notice::count());
    [$offset, $limit] = $pagination->getLimit();

    $notices = Notice::find('all', [
      'limit' => $limit,
      'offset' => $offset,
    ]);

    $heading = 'Manage notices';
    CoreUtils::loadPage(__METHOD__, [
      'heading' => $heading,
      'title' => "Page {$pagination->getPage()} - $heading - Admin Area",
      'view' => [true],
      #'js' => [true],
      'css' => [true],
      'import' => [
        'pagination' => $pagination,
        'notices' => $notices,
      ],
    ]);
  }
}
