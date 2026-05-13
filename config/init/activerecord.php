<?php

namespace ActiveRecord;

Config::initialize(function (Config $cfg) {
  $db_name = ($_ENV['TEST_MODE'] ?? null) === 'true' && !empty($_ENV['TEST_DB_NAME'])
    ? $_ENV['TEST_DB_NAME']
    : $_ENV['DB_NAME'];
  $cfg->set_connections([
    'pgsql' => "pgsql://{$_ENV['DB_USER']}:{$_ENV['DB_PASS']}@{$_ENV['DB_HOST']}/{$db_name}?charset=utf8",
    'failsafe' => 'sqlite://:memory:',
  ], 'pgsql');
});
Serialization::$DATETIME_FORMAT = 'c';
DateTime::$FORMATS['compat'] = 'c';
DateTime::$DEFAULT_FORMAT = 'compat';
Connection::$datetime_format = 'c';
