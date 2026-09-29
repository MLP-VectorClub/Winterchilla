<?php

namespace App;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Adds details about the current request (and the signed-in user, if any) to the context of every log record
 */
class RequestContextProcessor implements ProcessorInterface {
  public function __invoke(LogRecord $record):LogRecord {
    $context = $record->context;
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

    return $record->with(context: $context);
  }
}
