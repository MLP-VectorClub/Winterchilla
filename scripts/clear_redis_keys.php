<?php

require __DIR__.'/../vendor/autoload.php';
require __DIR__.'/../config/constants.php';

use App\CoreUtils;
use App\RedisHelper;

$prefix = basename(__FILE__).':';
$keys = array_slice($argv, 1);
if (empty($keys)){
  echo "$prefix Please specify the keys to clear as arguments\n";
  exit;
}
$num = RedisHelper::del($keys) ?? 0;
echo "$prefix ".CoreUtils::makePlural('key', $num, PREPEND_NUMBER)." deleted successfully\n";
