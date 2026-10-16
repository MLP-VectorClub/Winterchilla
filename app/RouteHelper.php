<?php

namespace App;

use App\Controllers\Controller;
use RuntimeException;

class RouteHelper {
  /**
   * @param array{0: class-string, 1: string, 2?: mixed} $target the controller, its method and optionally one more argument for it
   * @param mixed $params
   *
   * @return void
   */
  public static function processHandler(array $target, $params):void {
    [$class, $method] = $target;
    $extra = array_slice($target, 2);
    $controller = new $class();
    if (false === $controller instanceof Controller)
      throw new RuntimeException("$class must be an instance of ".Controller::class);
    $controller->{$method}($params, ...$extra);
  }
}
