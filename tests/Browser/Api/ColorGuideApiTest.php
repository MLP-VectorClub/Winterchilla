<?php

use Tests\Browser\Helpers\ApiClient;
use Tests\Browser\Helpers\TestSeederConstants;

// Contract: /cg/full, /cg/full/reorder and /users/contributions/lazyload/{favme} (tags: color guide, users).

it('returns the rendered full list of a guide in the requested order', function () {
  $guest = ApiClient::guest();

  $r = $guest->get('/cg/full', ['guide' => 'pony']);
  expect($r['status'])->toBe(200)
    ->and($r['json'])->toHaveKeys(['html', 'stateUrl'])->not->toHaveKey('status')
    ->and($r['json']['html'])->toContain('Twilight Sparkle')
    ->and($r['json']['stateUrl'])->toBe('/cg/pony/full');

  $r = $guest->get('/cg/full', ['guide' => 'pony', 'sort_by' => 'label']);
  expect($r['status'])->toBe(200)->and($r['json']['stateUrl'])->toBe('/cg/pony/full?sort_by=label');

  // An unknown order falls back to relevance
  expect($guest->get('/cg/full', ['guide' => 'pony', 'sort_by' => 'nonsense'])['json']['stateUrl'])->toBe('/cg/pony/full');
});

it('validates the guide of the full list with 422', function () {
  foreach ([[], ['guide' => 'nonsense']] as $query) {
    $r = ApiClient::guest()->get('/cg/full', $query);
    expect($r['status'])->toBe(422)->and($r['json']['errors'])->toHaveKey('guide');
  }
});

it('reorders a guide\'s full list for staff and answers with that guide\'s list only', function () {
  $guest = ApiClient::guest();
  expect($guest->request('PUT', '/appearances/order', ['guide' => 'pony', 'list' => '1'])['status'])->toBe(401);
  expect(ApiClient::loggedInAs(TestSeederConstants::USER_ID)->request('PUT', '/appearances/order', ['guide' => 'pony', 'list' => '1'])['status'])->toBe(403);

  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
  $r = $admin->request('PUT', '/appearances/order', ['list' => '1']);
  expect($r['status'])->toBe(422)->and($r['json']['errors'])->toHaveKey('guide');

  $r = $admin->request('PUT', '/appearances/order', ['guide' => 'pony', 'list' => '2,1', 'ordering' => 'relevance']);
  expect($r['status'])->toBe(200)->and($r['json'])->toHaveKey('html')->and($r['json']['html'])->toContain('Twilight Sparkle');

  // The guide is honoured: the eqg list doesn't contain the pony guide's appearances
  $r = $admin->request('PUT', '/appearances/order', ['guide' => 'eqg', 'list' => '1', 'ordering' => 'relevance']);
  expect($r['status'])->toBe(200)->and($r['json']['html'])->not->toContain('Twilight Sparkle');
});

it('returns the preview link of a cached deviation for the contributions page', function () {
  $r = ApiClient::guest()->get('/users/contributions/lazyload/dfin001');

  expect($r['status'])->toBe(200)
    ->and($r['json'])->toHaveKey('html')->not->toHaveKey('status')
    ->and($r['json']['html'])->toContain('Finished Test Vector');
});
