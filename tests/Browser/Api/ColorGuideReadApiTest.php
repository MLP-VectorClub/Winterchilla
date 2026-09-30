<?php

use Tests\Browser\Helpers\ApiClient;
use Tests\Browser\Helpers\TestSeederConstants;

// Contract: the public read endpoints of the color guide that Luna mirrors (tag: color guide).

it('reports the number of entries per guide', function () {
  $r = ApiClient::guest()->get('/color-guide');

  expect($r['status'])->toBe(200)
    ->and($r['json'])->toHaveKey('entryCounts')
    ->and($r['json']['entryCounts'])->toHaveKeys(['pony', 'eqg'])
    ->and($r['json']['entryCounts']['pony'])->toBeGreaterThanOrEqual(1)
    ->and($r['json'])->not->toHaveKey('status');
});

it('lists major changes newest first with Luna-shaped pagination', function () {
  $r = ApiClient::guest()->get('/color-guide/major-changes', ['guide' => 'pony', 'size' => 1]);

  expect($r['status'])->toBe(200)
    ->and($r['json']['pagination'])->toBe(['currentPage' => 1, 'totalPages' => 2, 'totalItems' => 2, 'itemsPerPage' => 1]);
  $change = $r['json']['changes'][0];
  expect($change)->toHaveKeys(['id', 'reason', 'appearance', 'user', 'createdAt'])
    ->and($change['reason'])->toBe('Seeded newest major change')
    ->and($change['user'])->toBeNull()
    ->and($change['appearance'])->toHaveKeys(['id', 'label', 'guide', 'previewData']);

  $second = ApiClient::guest()->get('/color-guide/major-changes', ['guide' => 'pony', 'size' => 1, 'page' => 2]);
  expect($second['json']['changes'][0]['reason'])->toBe('Seeded older major change');
});

it('only tells staff who made a major change', function () {
  $r = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID)->get('/color-guide/major-changes', ['guide' => 'pony']);

  expect($r['status'])->toBe(200)
    ->and($r['json']['changes'][0]['user'])->toHaveKeys(['id', 'name', 'role', 'avatarUrl']);
});

it('validates the major changes query with 422', function () {
  $guest = ApiClient::guest();

  expect($guest->get('/color-guide/major-changes')['json']['errors'])->toHaveKey('guide');
  expect($guest->get('/color-guide/major-changes', ['guide' => 'pony', 'size' => 99])['json']['errors'])->toHaveKey('size');
  expect($guest->get('/color-guide/major-changes', ['guide' => 'pony', 'page' => 0])['json']['errors'])->toHaveKey('page');
});
