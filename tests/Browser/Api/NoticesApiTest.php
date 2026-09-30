<?php

use Tests\Browser\Helpers\ApiClient;
use Tests\Browser\Helpers\TestSeederConstants;

// Contract: /notices (tag: notices). Managing notices is staff only; the current ones are public.

function noticeBody(array $overrides = []):array {
  return $overrides + ['messageHtml' => 'Maintenance tonight', 'hideAfter' => gmdate('c', strtotime('+2 days')), 'type' => 'info'];
}

it('enforces permissions', function () {
  $guest = ApiClient::guest();
  $user = ApiClient::loggedInAs(TestSeederConstants::USER_ID);

  foreach ([['GET', '/notices'], ['POST', '/notices'], ['GET', '/notices/1'], ['PUT', '/notices/1'], ['DELETE', '/notices/1']] as [$method, $path]) {
    expect($guest->request($method, $path)['status'])->toBe(401, "$method $path")
      ->and($user->request($method, $path)['status'])->toBe(403, "$method $path");
  }
});

it('creates, reads, updates, lists and deletes a notice', function () {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);

  $created = $admin->post('/notices', noticeBody());
  expect($created['status'])->toBe(201)
    ->and($created['json'])->toHaveKeys(['id', 'type', 'messageHtml', 'hideAfter', 'postedBy', 'createdAt'])
    ->and($created['json']['postedBy'])->toBe(TestSeederConstants::ADMIN_ID)
    ->and($created['json'])->not->toHaveKey('status');
  $id = $created['json']['id'];

  try {
    expect($admin->get("/notices/$id")['json']['messageHtml'])->toContain('Maintenance tonight');

    $updated = $admin->request('PUT', "/notices/$id", noticeBody(['messageHtml' => 'Maintenance is over', 'type' => 'success']));
    expect($updated['status'])->toBe(200)
      ->and($updated['json']['type'])->toBe('success')
      ->and($updated['json']['messageHtml'])->toContain('over');

    $list = $admin->get('/notices', ['size' => 100]);
    expect($list['status'])->toBe(200)
      ->and($list['json']['pagination'])->toHaveKeys(['currentPage', 'totalPages', 'totalItems', 'itemsPerPage'])
      ->and(array_column($list['json']['notices'], 'id'))->toContain($id);

    // Everyone sees it while it is current
    $current = ApiClient::guest()->get('/notices/current');
    expect($current['status'])->toBe(200)->and(array_column($current['json'], 'id'))->toContain($id);
  }
  finally {
    expect($admin->request('DELETE', "/notices/$id")['status'])->toBe(204);
  }

  expect($admin->get("/notices/$id")['status'])->toBe(404)
    ->and($admin->request('DELETE', "/notices/$id")['status'])->toBe(404);
  expect(array_column(ApiClient::guest()->get('/notices/current')['json'], 'id'))->not->toContain($id);
});

it('validates notices with 422 and field errors', function () {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);

  expect($admin->post('/notices')['json']['errors'])->toHaveKey('messageHtml');
  expect($admin->post('/notices', noticeBody(['hideAfter' => gmdate('c', strtotime('-1 day'))]))['json']['errors'])->toHaveKey('hideAfter');
  expect($admin->post('/notices', noticeBody(['type' => 'nope']))['json']['errors'])->toHaveKey('type');
  expect($admin->post('/notices', noticeBody(['messageHtml' => str_repeat('a', 501)]))['json']['errors'])->toHaveKey('messageHtml');
  expect($admin->get('/notices', ['size' => 500])['json']['errors'])->toHaveKey('size');
});
