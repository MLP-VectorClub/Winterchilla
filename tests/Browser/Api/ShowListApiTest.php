<?php

use Tests\Browser\Helpers\ApiClient;
use Tests\Browser\Helpers\TestSeederConstants;

// Contract: GET /show, the paginated list of shows that Luna mirrors (tag: shows).

it('lists shows by type with Luna-shaped pagination', function () {
  $r = ApiClient::guest()->get('/show', ['types' => ['episode', 'movie'], 'order' => 'overall']);

  expect($r['status'])->toBe(200)
    ->and($r['json']['pagination'])->toHaveKeys(['currentPage', 'totalPages', 'totalItems', 'itemsPerPage'])
    ->and($r['json']['pagination']['currentPage'])->toBe(1)
    ->and($r['json']['pagination']['itemsPerPage'])->toBe(8);
  $ids = array_column($r['json']['show'], 'id');
  expect($ids)->toContain(TestSeederConstants::SHOW_ID)->toContain(TestSeederConstants::MOVIE_ID);
  expect($r['json']['show'][0])->toHaveKeys(['id', 'type', 'title', 'season', 'episode', 'parts', 'no', 'airs'])
    ->not->toHaveKey('status');
});

it('filters by type and pages through the list', function () {
  $guest = ApiClient::guest();
  $movies = $guest->get('/show', ['types' => ['movie'], 'order' => 'overall']);

  expect(array_unique(array_column($movies['json']['show'], 'type')))->toBe(['movie']);
  $paged = $guest->get('/show', ['types' => ['episode', 'movie'], 'order' => 'series', 'size' => 1, 'page' => 2]);
  expect($paged['status'])->toBe(200)
    ->and($paged['json']['show'])->toHaveCount(1)
    ->and($paged['json']['pagination']['currentPage'])->toBe(2)
    ->and($paged['json']['pagination']['itemsPerPage'])->toBe(1);
});

it('validates the query with 422', function () {
  $guest = ApiClient::guest();

  expect($guest->get('/show', ['order' => 'overall'])['json']['errors'])->toHaveKey('types');
  expect($guest->get('/show', ['types' => ['nope'], 'order' => 'overall'])['json']['errors'])->toHaveKey('types');
  expect($guest->get('/show', ['types' => ['episode']])['json']['errors'])->toHaveKey('order');
  expect($guest->get('/show', ['types' => ['episode'], 'order' => 'overall', 'size' => 11])['json']['errors'])->toHaveKey('size');
  expect($guest->get('/show', ['types' => ['episode'], 'order' => 'overall', 'page' => 0])['json']['errors'])->toHaveKey('page');
});

it('looks a show up by season and episode', function () {
  $guest = ApiClient::guest();
  $r = $guest->get('/show', ['types' => ['episode'], 'order' => 'series', 'season' => 1, 'episode' => 1]);

  expect($r['status'])->toBe(200)->and(array_column($r['json']['show'], 'id'))->toContain(TestSeederConstants::SHOW_ID);
  foreach ($r['json']['show'] as $show)
    expect($show['season'])->toBe(1)->and($show['episode'])->toBe(1);
  expect($guest->get('/show', ['types' => ['episode'], 'order' => 'series', 'season' => 99])['json']['show'])->toBe([]);
  expect($guest->get('/show', ['types' => ['episode'], 'order' => 'series', 'season' => 'x'])['json']['errors'])->toHaveKey('season');
});

it('returns the latest show', function () {
  $r = ApiClient::guest()->get('/show/latest');

  expect($r['status'])->toBeIn([200, 404]);
  if ($r['status'] === 200)
    expect($r['json'])->toHaveKeys(['id', 'type', 'title', 'season', 'episode', 'parts', 'no', 'airs']);
  else
    expect($r['json'])->toHaveKey('message');
});
