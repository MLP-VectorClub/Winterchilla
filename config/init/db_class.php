<?php

use Activerecord\Connection;
use App\CoreUtils;
use App\DB;
use App\PostgresDbWrapper;

$ar_conn = Connection::instance();
DB::$instance = PostgresDbWrapper::withConnection($ar_conn->connection);

// Read-only mode: any write that slips through fails loudly (SQLSTATE 25006) instead of changing data both applications share
if (CoreUtils::readOnly())
  $ar_conn->connection->exec('SET SESSION CHARACTERISTICS AS TRANSACTION READ ONLY');
