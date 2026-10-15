<?php

namespace App\Models;

use ActiveRecord\DateTime;
use App\Time;

/**
 * @property string   id
 * @property string   message_html
 * @property string   type
 * @property int      posted_by
 * @property DateTime created_at
 * @property DateTime updated_at
 * @property DateTime hide_after
 * @property DateTime|null show_after
 * @property string   site
 * @method static Notice|Notice[] find(...$args)
 */
class Notice extends NSModel {
  /** Which site shows a notice: both of them, or only one while they share a database */
  const VALID_SITES = ['all', 'celestia', 'winterchilla'];

  const VALID_TYPES = [
    'info' => 'Informational (blue)',
    'success' => 'Success (green)',
    'fail' => 'Failure (red)',
    'warn' => 'Warning (orange)',
    'caution' => 'Caution (yellow)',
  ];

  /**
   * @return Notice[]
   */
  public static function list() {
    return self::find('all', [
      'conditions' => "hide_after > now() AND (show_after IS NULL OR show_after <= now()) AND site IN ('all', 'winterchilla')",
    ]);
  }

  /**
   * @param bool $withPath Replaces `{path}` with the address of the page being viewed, only for notices that are shown on a page
   */
  public function getMessage(bool $withPath = true): string {
    $message = $this->message_html;
    if ($withPath)
      $message = str_replace('{path}', htmlspecialchars(self::currentPath(), ENT_QUOTES), $message);
    $message = preg_replace_callback('~@ts\(([^)]+)\)~', function($match){
      $ts = strtotime($match[1]);
      return sprintf(
        '<span class="dynt-el">%s</span> (<time datetime="%s">%s</time>)',
        Time::format($ts, Time::FORMAT_READABLE),
        $match[1],
        Time::format($ts, Time::FORMAT_FULL),
      );
    },$message);
    return $message;
  }

  /** The path and query of the current request, the same address the other site has the page at */
  public static function currentPath(): string {
    $uri = $_SERVER['REQUEST_URI'] ?? '/';

    return str_starts_with($uri, '/') && !str_starts_with($uri, '//') ? $uri : '/';
  }
}
