<?php

use Tests\Browser\Helpers\ApiClient;
use Tests\Browser\Helpers\TestSeederConstants;

// Contract: the small public reads Luna mirrors, /useful-links/sidebar and /user-prefs/me.

it('gives signed-out visitors an empty sidebar link list', function () {
  $r = ApiClient::guest()->get('/useful-links/sidebar');

  expect($r['status'])->toBe(200)->and($r['json'])->toBe([]);
});

it('lists sidebar links by role in display order', function () {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
  $id = $admin->post('/useful-links', ['label' => 'Sidebar staff', 'url' => '/staff-only', 'title' => '', 'minRole' => 'staff'])['json']['id'] ?? null;
  $guestId = $admin->post('/useful-links', ['label' => 'Sidebar guest', 'url' => '/for-all', 'title' => '', 'minRole' => 'guest'])['json']['id'] ?? null;

  try {
    $asAdmin = $admin->get('/useful-links/sidebar');
    expect($asAdmin['status'])->toBe(200);
    $labels = array_column($asAdmin['json'], 'label');
    expect($labels)->toContain('Sidebar staff')->toContain('Sidebar guest')
      ->and($asAdmin['json'][0])->toHaveKeys(['id', 'label', 'url', 'title', 'minRole']);

    $asUser = array_column(ApiClient::loggedInAs(TestSeederConstants::USER_ID)->get('/useful-links/sidebar')['json'], 'label');
    expect($asUser)->toContain('Sidebar guest')->not->toContain('Sidebar staff');
  }
  finally {
    foreach (array_filter([$id, $guestId]) as $linkId)
      $admin->request('DELETE', "/useful-links/$linkId");
  }
});

it('returns the effective preferences, with defaults for guests', function () {
  $r = ApiClient::guest()->get('/user-prefs/me');

  expect($r['status'])->toBe(200)
    ->and($r['json'])->toHaveKeys(['cg_itemsperpage', 'p_hidediscord', 'a_pcgmake'])
    ->and($r['json']['cg_itemsperpage'])->toBe(7);

  $some = ApiClient::loggedInAs(TestSeederConstants::USER_ID)->get('/user-prefs/me', ['keys' => ['cg_itemsperpage', 'a_pcgmake']]);
  expect($some['status'])->toBe(200)->and(array_keys($some['json']))->toBe(['cg_itemsperpage', 'a_pcgmake']);
});

it('reflects a changed preference', function () {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
  $set = $admin->request('PUT', '/users/' . TestSeederConstants::ADMIN_ID . '/preferences/cg_itemsperpage', ['value' => '12']);

  try {
    expect($set['status'])->toBe(200)
      ->and($admin->get('/user-prefs/me', ['keys' => ['cg_itemsperpage']])['json'])->toEqual(['cg_itemsperpage' => 12]);
  }
  finally {
    $admin->request('PUT', '/users/' . TestSeederConstants::ADMIN_ID . '/preferences/cg_itemsperpage', ['value' => '7']);
  }
});

it('validates the preference keys with 422', function () {
  $guest = ApiClient::guest();

  expect($guest->get('/user-prefs/me', ['keys' => ['nope']])['json']['errors'])->toHaveKey('keys');
  expect($guest->get('/user-prefs/me', ['keys' => ['a_pcgmake', 'a_pcgmake']])['json']['errors'])->toHaveKey('keys');
});
