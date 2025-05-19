<?php

namespace App;

use Monolog\DateTimeImmutable;
use Monolog\Logger;

class UsefulLogger extends Logger {
  /**
   * @inheritdoc
   */
  public function addRecord(int $level, string $message, array $context = [], ?DateTimeImmutable $datetime = null): bool {
    $context['ip'] = $_SERVER['REMOTE_ADDR'] ?? null;
    $context['referrer'] = $_SERVER['HTTP_REFERER'] ?? null;
    $context['request_uri'] = $_SERVER['REQUEST_URI'] ?? null;
    $context['request_data'] = $_REQUEST;
    /** @noinspection ClassConstantCanBeUsedInspection */
    $context['auth'] = class_exists('\App\Auth') && Auth::$signed_in ? [
      'id' => Auth::$user->id,
      'name' => Auth::$user->name,
      'session' => Auth::$session->id,
    ] : null;
    return parent::addRecord($level, $message, $context);
  }
}
