<?php

// Health check for container probes: 200 only when PostgreSQL and Valkey/Redis answer. Does not touch the router/session code.
use Activerecord\Connection;
use App\RedisHelper;

require __DIR__.'/../config/init/minimal.php';
require __DIR__.'/../config/init/monolog.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');
$checks = ['database' => false, 'redis' => false];
try {
  $checks['database'] = Connection::instance()->connection->query('SELECT 1')->fetchColumn() == 1;
}
catch (Throwable $e) {
  error_log('health: database: '.$e->getMessage());
}
try {
  $redis = RedisHelper::getInstance();
  $checks['redis'] = $redis !== null && $redis->ping() !== false;
}
catch (Throwable $e) {
  error_log('health: redis: '.$e->getMessage());
}
$ok = !in_array(false, $checks, true);
http_response_code($ok ? 200 : 503);
echo json_encode(['status' => $ok, 'checks' => $checks]);
