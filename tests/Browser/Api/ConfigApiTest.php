<?php

use Tests\Browser\Helpers\ApiClient;

// Contract: GET /config (tag: configuration). Public and identical for every visitor.

it('returns the constants and validation patterns the front end needs', function () {
  $r = ApiClient::guest()->get('/config');

  expect($r['status'])->toBe(200)
    ->and($r['json'])->toHaveKeys(['tagTypes', 'roles', 'showTypes', 'maxUploadSize', 'patterns', 'wsServerHost', 'discordInviteLink'])
    ->and($r['json']['tagTypes'])->toHaveKeys(['app', 'cat', 'spec', 'gen', 'char'])
    ->and($r['json']['roles'])->toHaveKey('staff')
    ->and($r['json']['showTypes'])->toHaveKeys(['episode', 'movie'])
    ->and($r['json']['discordInviteLink'])->toStartWith('https://')
    ->and($r['json'])->not->toHaveKey('status');
});

it('sends patterns as source and flags that work as JavaScript regular expressions', function () {
  $patterns = ApiClient::guest()->get('/config')['json']['patterns'];

  expect($patterns)->toHaveKeys(['printableAscii', 'hexColor', 'username', 'episodeTitle']);
  foreach ($patterns as $name => $pattern) {
    expect($pattern)->toHaveKeys(['source', 'flags'])->and($pattern['source'])->toBeString()->not->toStartWith('/', $name);
    // Every pattern has to be usable as a PHP regex too (same syntax for the subset we use)
    expect(@preg_match('~' . str_replace('~', '\~', $pattern['source']) . '~' . $pattern['flags'], ''))->not->toBeFalse();
  }
  expect(preg_match('~' . $patterns['hexColor']['source'] . '~' . $patterns['hexColor']['flags'], '#ffaa00'))->toBe(1);
});

it('is cacheable and only answers GET', function () {
  $guest = ApiClient::guest();

  expect($guest->post('/config')['status'])->toBe(405);
});
