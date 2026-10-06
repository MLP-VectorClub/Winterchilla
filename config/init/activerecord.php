<?php

namespace ActiveRecord;

Config::initialize(function (Config $cfg) {
  $db_name = ($_ENV['TEST_MODE'] ?? null) === 'true' && !empty($_ENV['TEST_DB_NAME'])
    ? $_ENV['TEST_DB_NAME']
    : $_ENV['DB_NAME'];
  // In read-only mode a database role that is only allowed to read can be given (DB_READ_USER / DB_READ_PASS), so even code that forgets the check cannot write
  $read_only = ($_ENV['READ_ONLY'] ?? null) === 'true' && !empty($_ENV['DB_READ_USER']);
  $db_user = $read_only ? $_ENV['DB_READ_USER'] : $_ENV['DB_USER'];
  $db_pass = $read_only ? ($_ENV['DB_READ_PASS'] ?? '') : $_ENV['DB_PASS'];
  $cfg->set_connections([
    'pgsql' => "pgsql://{$db_user}:{$db_pass}@{$_ENV['DB_HOST']}/{$db_name}?charset=utf8",
    'failsafe' => 'sqlite://:memory:',
  ], 'pgsql');
});
Serialization::$DATETIME_FORMAT = 'c';
DateTime::$FORMATS['compat'] = 'c';
DateTime::$DEFAULT_FORMAT = 'compat';
Connection::$datetime_format = 'c';
