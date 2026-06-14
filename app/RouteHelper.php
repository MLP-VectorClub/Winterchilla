<?php

namespace App;

use App\Controllers\Controller;
use RuntimeException;

class RouteHelper {
  /**
   * @param array{0: class-string, 1: string} $target
   * @param mixed $params
   *
   * @return void
   */
  public static function processHandler(array $target, $params):void {
    [$class, $method] = $target;
    $controller = new $class();
    if (false === $controller instanceof Controller)
      throw new RuntimeException("$class must be an instance of ".Controller::class);
    $controller->{$method}($params);
  }
}
