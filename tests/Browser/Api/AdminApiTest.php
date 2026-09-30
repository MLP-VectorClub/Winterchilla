<?php

use Tests\Browser\Helpers\ApiClient;
use Tests\Browser\Helpers\TestSeederConstants;

// Contract: /admin/... endpoints (tag: admin). Staff only.

it('requires staff for every admin endpoint', function () {
  $guest = ApiClient::guest();
  $user = ApiClient::loggedInAs(TestSeederConstants::USER_ID);

  foreach ([
    ['GET', '/admin/logs/1'], ['GET', '/useful-links/1'], ['POST', '/useful-links'], ['PUT', '/useful-links/1'],
    ['DELETE', '/useful-links/1'], ['PUT', '/useful-links/order'], ['DELETE', '/admin/stat-cache'],
  ] as [$method, $path]) {
    expect($guest->request($method, $path)['status'])->toBe(401, "$method $path as guest")
      ->and($user->request($method, $path)['status'])->toBe(403, "$method $path as user");
  }
});

it('manages useful links: create, read, update, delete and reorder', function () {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
  $label = 'Contract ' . substr(md5(uniqid('', true)), 0, 6);

  $r = $admin->post('/useful-links', ['label' => $label, 'url' => '/about', 'title' => 'A contract link', 'minRole' => 'guest']);
  expect($r['status'])->toBe(201)->and($r['json'])->toHaveKey('id')->not->toHaveKey('status');
  $id = $r['json']['id'];
  $path = '/useful-links/' . $id;

  $r = $admin->get($path);
  expect($r['status'])->toBe(200)
    ->and($r['json'])->toMatchArray(['label' => $label, 'url' => '/about', 'title' => 'A contract link', 'minRole' => 'guest']);

  $r = $admin->request('PUT', $path, ['label' => $label . ' 2', 'url' => '/about', 'title' => 'A contract link', 'minRole' => 'guest']);
  expect($r['status'])->toBe(204)->and($r['body'])->toBe('');
  expect($admin->get($path)['json']['label'])->toBe($label . ' 2');

  // Nothing to change is still a success
  expect($admin->request('PUT', $path, ['label' => $label . ' 2', 'url' => '/about', 'title' => 'A contract link', 'minRole' => 'guest'])['status'])->toBe(204);

  expect($admin->request('PUT', '/useful-links/order', ['list' => (string)$id])['status'])->toBe(204);

  expect($admin->request('DELETE', $path)['status'])->toBe(204);
  expect($admin->get($path)['status'])->toBe(404);
});

it('validates useful links with 422 and field errors', function () {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);

  $r = $admin->post('/useful-links');
  expect($r['status'])->toBe(422)->and($r['json'])->toHaveKeys(['message', 'errors'])->and($r['json']['errors'])->toHaveKey('label');

  $r = $admin->post('/useful-links', ['label' => 'Valid label', 'url' => '/about', 'minRole' => 'nonsense']);
  expect($r['status'])->toBe(422)->and($r['json']['errors'])->toHaveKey('minRole');

  $r = $admin->request('PUT', '/useful-links/order');
  expect($r['status'])->toBe(422)->and($r['json']['errors'])->toHaveKey('list');
});

it('404s for missing log entries and links', function () {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);

  expect($admin->get('/admin/logs/987654')['status'])->toBe(404)
    ->and($admin->get('/useful-links/987654')['status'])->toBe(404)
    ->and($admin->request('DELETE', '/useful-links/987654')['status'])->toBe(404);
});

it('clears the PHP stat cache with 204', function () {
  expect(ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID)->request('DELETE', '/admin/stat-cache')['status'])->toBe(204);
});

it('lists log entries for staff only, with filters and Luna-shaped pagination', function () {
  expect(ApiClient::guest()->get('/admin/logs')['status'])->toBe(401);
  expect(ApiClient::loggedInAs(TestSeederConstants::USER_ID)->get('/admin/logs')['status'])->toBe(403);

  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
  $r = $admin->get('/admin/logs', ['size' => 5]);
  expect($r['status'])->toBe(200)
    ->and($r['json']['pagination'])->toHaveKeys(['currentPage', 'totalPages', 'totalItems', 'itemsPerPage'])
    ->and($r['json']['pagination']['itemsPerPage'])->toBe(5)
    ->and(count($r['json']['entries']))->toBeLessThanOrEqual(5)
    ->and($r['json'])->not->toHaveKey('status');
  foreach ($r['json']['entries'] as $entry)
    expect($entry)->toHaveKeys(['id', 'type', 'typeLabel', 'initiator', 'ip', 'createdAt', 'hasDetails']);

  $filtered = $admin->get('/admin/logs', ['type' => 'rolechange']);
  expect($filtered['status'])->toBe(200);
  foreach ($filtered['json']['entries'] as $entry)
    expect($entry['type'])->toBe('rolechange');
  expect($admin->get('/admin/logs', ['initiatorId' => 0])['status'])->toBe(200);
});

it('validates the log list query with 422', function () {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);

  expect($admin->get('/admin/logs', ['type' => 'nonsense'])['json']['errors'])->toHaveKey('type');
  expect($admin->get('/admin/logs', ['initiatorId' => 'x'])['json']['errors'])->toHaveKey('initiatorId');
  expect($admin->get('/admin/logs', ['size' => 500])['json']['errors'])->toHaveKey('size');
  expect($admin->get('/admin/logs', ['page' => 0])['json']['errors'])->toHaveKey('page');
});
