<?php

use Tests\Browser\Helpers\ApiClient;
use Tests\Browser\Helpers\TestSeederConstants;

// Contract: the personal guide point history, /users/{id}/personal-guide/point-history (tag: personal guide).

$path = '/users/' . TestSeederConstants::USER_ID . '/personal-guide/point-history';

it('keeps the point history to the user and staff', function () use ($path) {
  expect(ApiClient::guest()->get($path)['status'])->toBe(401);
  expect(ApiClient::loggedInAs(TestSeederConstants::FRESH_USER_ID)->get($path)['status'])->toBe(403);
  expect(ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID)->get('/users/987654/personal-guide/point-history')['status'])->toBe(404);
});

it('lists the point history newest first with Luna-shaped pagination', function () use ($path) {
  $r = ApiClient::loggedInAs(TestSeederConstants::USER_ID)->get($path);

  expect($r['status'])->toBe(200)
    ->and($r['json']['pagination'])->toHaveKeys(['currentPage', 'totalPages', 'totalItems', 'itemsPerPage'])
    ->and($r['json']['pagination']['itemsPerPage'])->toBe(20)
    ->and($r['json']['entries'])->not->toBeEmpty()
    ->and($r['json']['entries'][0])->toHaveKeys(['id', 'changeType', 'reason', 'amount', 'data', 'createdAt'])
    ->and($r['json'])->not->toHaveKey('status');
  $types = array_column($r['json']['entries'], 'changeType');
  expect($types)->toContain('manual_give');
});

it('only tells staff who granted points manually', function () use ($path) {
  $find = fn(array $entries) => array_values(array_filter($entries, fn($e) => $e['changeType'] === 'manual_give'))[0];

  $own = $find(ApiClient::loggedInAs(TestSeederConstants::USER_ID)->get($path)['json']['entries']);
  $staff = $find(ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID)->get($path)['json']['entries']);

  expect($own['data'])->not->toHaveKey('by')->and($staff['data'])->toHaveKey('by');
});

it('validates the point history query with 422', function () use ($path) {
  $user = ApiClient::loggedInAs(TestSeederConstants::USER_ID);

  expect($user->get($path, ['size' => 500])['json']['errors'])->toHaveKey('size');
  expect($user->get($path, ['page' => 0])['json']['errors'])->toHaveKey('page');
});

it('lists the personal guide appearances, hiding private ones from visitors', function () {
  $path = '/users/' . TestSeederConstants::USER_ID . '/personal-guide/appearances';
  $guest = ApiClient::guest()->get($path);

  expect($guest['status'])->toBe(200)
    ->and($guest['json']['pagination'])->toHaveKeys(['currentPage', 'totalPages', 'totalItems', 'itemsPerPage'])
    ->and($guest['json']['canManage'])->toBeFalse();
  $listed = array_column($guest['json']['appearances'], null, 'id');
  expect($listed)->toHaveKeys([TestSeederConstants::PERSONAL_APPEARANCE_ID, TestSeederConstants::PRIVATE_PERSONAL_APPEARANCE_ID])
    ->and($listed[TestSeederConstants::PERSONAL_APPEARANCE_ID])->toHaveKeys(['id', 'label', 'sprite', 'colorGroups', 'private'])
    ->and($listed[TestSeederConstants::PRIVATE_PERSONAL_APPEARANCE_ID])->toBe(['id' => TestSeederConstants::PRIVATE_PERSONAL_APPEARANCE_ID, 'label' => $listed[TestSeederConstants::PRIVATE_PERSONAL_APPEARANCE_ID]['label'], 'private' => true]);

  $owner = ApiClient::loggedInAs(TestSeederConstants::USER_ID)->get($path);
  $own = array_column($owner['json']['appearances'], null, 'id');
  expect($owner['json']['canManage'])->toBeTrue()
    ->and($own[TestSeederConstants::PRIVATE_PERSONAL_APPEARANCE_ID])->toHaveKey('colorGroups');
});

it('validates the personal guide appearance list', function () {
  $path = '/users/' . TestSeederConstants::USER_ID . '/personal-guide/appearances';

  expect(ApiClient::guest()->get($path, ['size' => 99])['json']['errors'])->toHaveKey('size');
  expect(ApiClient::guest()->get($path, ['page' => 0])['json']['errors'])->toHaveKey('page');
  expect(ApiClient::guest()->get('/users/987654/personal-guide/appearances')['status'])->toBe(404);
});
