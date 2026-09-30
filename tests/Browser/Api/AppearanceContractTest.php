<?php

use Tests\Browser\Helpers\ApiClient;
use Tests\Browser\Helpers\TestSeederConstants;

// Contract: /cg/appearance endpoints (tag: appearances), the management API of the color guide. Signed-in users
// manage their own personal guide appearances; staff manage the official guide. (AppearanceApiTest.php covers the
// file cleanup on deletion.)

$appearanceId = TestSeederConstants::APPEARANCE_ID;
$personalId = TestSeederConstants::PERSONAL_APPEARANCE_ID;

function uniqueAppearance(string $prefix = 'Contract Pony'):string {
  return substr($prefix . ' ' . substr(md5(uniqid('', true)), 0, 8), 0, 70);
}

function createOfficial(ApiClient $admin, ?string $label = null):array {
  $r = $admin->post('/cg/appearance', ['guide' => 'pony', 'label' => $label ?? uniqueAppearance()]);
  expect($r['status'])->toBe(201);
  return $r['json'];
}

it('requires authentication for every appearance management endpoint', function () use ($appearanceId) {
  $guest = ApiClient::guest();

  foreach ([
    ['GET', "/cg/appearance/$appearanceId"], ['POST', '/cg/appearance'], ['PUT', "/cg/appearance/$appearanceId"], ['DELETE', "/cg/appearance/$appearanceId"],
    ['POST', "/cg/appearance/$appearanceId/pin"], ['POST', "/cg/appearance/$appearanceId/sanitize-svg"],
    ['GET', "/cg/appearance/$appearanceId/guide-relations"],
  ] as [$method, $path]) {
    $r = $guest->request($method, $path);
    expect($r['status'])->toBe(401, "$method $path")->and($r['json'])->toHaveKey('message')->not->toHaveKey('status');
  }
});

it('404s for appearances that do not exist', function () {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);

  foreach ([['GET', '/cg/appearance/987654'], ['PUT', '/cg/appearance/987654'], ['DELETE', '/cg/appearance/987654'], ['GET', '/cg/appearance/987654/tagged'], ['GET', '/cg/appearance/987654/cutiemarks'], ['POST', '/cg/appearance/987654/pin']] as [$method, $path])
    expect($admin->request($method, $path)['status'])->toBe(404, "$method $path");
});

it('keeps regular users out of the official guide', function () use ($appearanceId) {
  $user = ApiClient::loggedInAs(TestSeederConstants::USER_ID);

  foreach ([['GET', "/cg/appearance/$appearanceId"], ['PUT', "/cg/appearance/$appearanceId"], ['DELETE', "/cg/appearance/$appearanceId"], ['POST', "/cg/appearance/$appearanceId/pin"], ['GET', "/cg/appearance/$appearanceId/cutiemarks"]] as [$method, $path])
    expect($user->request($method, $path)['status'])->toBe(403, "$method $path");

  $r = $user->post('/cg/appearance', ['guide' => 'pony', 'label' => uniqueAppearance()]);
  // Regular users always create personal guide appearances (the guide is forced), which is switched off for them by default
  expect($r['status'])->toBe(403)->and($r['json'])->toHaveKey('message');
});

it('creates, reads, updates and deletes an official appearance', function () {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
  $label = uniqueAppearance();

  $created = createOfficial($admin, $label);
  expect($created)->toHaveKeys(['id', 'goto', 'message'])->not->toHaveKey('status');
  $path = '/cg/appearance/' . $created['id'];

  $r = $admin->post('/cg/appearance', ['guide' => 'pony', 'label' => $label]);
  expect($r['status'])->toBe(422)->and($r['json']['errors'])->toHaveKey('label');

  $r = $admin->get($path);
  expect($r['status'])->toBe(200)->and($r['json'])->toHaveKeys(['label', 'notes', 'private'])->and($r['json']['label'])->toBe($label);

  $r = $admin->request('PUT', $path, ['label' => $label . ' II', 'notes' => 'Some notes']);
  expect($r['status'])->toBe(200)->and($r['json'])->not->toHaveKey('status');
  expect($admin->get($path)['json'])->toMatchArray(['label' => $label . ' II', 'notes' => 'Some notes']);

  $r = $admin->request('DELETE', $path);
  expect($r['status'])->toBe(204)->and($r['body'])->toBe('');
  expect($admin->get($path)['status'])->toBe(404);
});

it('validates appearance input with 422 and field errors', function () {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);

  $r = $admin->post('/cg/appearance', ['guide' => 'pony']);
  expect($r['status'])->toBe(422)->and($r['json'])->toHaveKeys(['message', 'errors'])->and($r['json']['errors'])->toHaveKey('label');

  $r = $admin->post('/cg/appearance', ['guide' => 'nonsense', 'label' => uniqueAppearance()]);
  expect($r['status'])->toBe(422)->and($r['json']['errors'])->toHaveKey('guide');

  $r = $admin->post('/cg/appearance', ['guide' => 'pony', 'label' => 'x']);
  expect($r['status'])->toBe(422)->and($r['json']['errors'])->toHaveKey('label');
});

it('pins and unpins official appearances and refuses to delete a pinned one', function () use ($personalId) {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
  $created = createOfficial($admin);
  $path = '/cg/appearance/' . $created['id'];

  $r = $admin->post("$path/pin");
  expect($r['status'])->toBe(200)->and($r['json'])->toHaveKey('message')->not->toHaveKey('status');

  $r = $admin->request('DELETE', $path);
  expect($r['status'])->toBe(409)->and($r['json'])->toHaveKey('message');

  expect($admin->request('DELETE', "$path/pin")['status'])->toBe(200);
  expect($admin->request('DELETE', $path)['status'])->toBe(204);

  // Personal guide appearances can't be pinned
  expect($admin->post("/cg/appearance/$personalId/pin")['status'])->toBe(409);
});

it('reports what is unavailable for personal guide appearances', function () use ($personalId, $appearanceId) {
  $user = ApiClient::loggedInAs(TestSeederConstants::USER_ID);

  expect($user->get("/cg/appearance/$personalId")['status'])->toBe(200);
  expect($user->get("/cg/appearance/$personalId/tagged")['status'])->toBe(409);
  expect($user->get("/cg/appearance/$personalId/relations")['status'])->toBe(409);

  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
  $r = $admin->get("/cg/appearance/$appearanceId/tagged");
  expect($r['status'])->toBe(200)->and($r['json'])->toHaveKey('tags');
});

it('lists an appearance\'s cutie marks and validates cutie mark updates', function () use ($appearanceId) {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);

  $r = $admin->get("/cg/appearance/$appearanceId/cutiemarks");
  expect($r['status'])->toBe(200)->and($r['json'])->toHaveKeys(['cms', 'preview']);

  $r = $admin->request('PUT', "/cg/appearance/$appearanceId/cutiemarks");
  expect($r['status'])->toBe(422)->and($r['json']['errors'])->toHaveKey('CMData');

  $five = json_encode(array_fill(0, 5, ['facing' => 'left']));
  $r = $admin->request('PUT', "/cg/appearance/$appearanceId/cutiemarks", ['CMData' => $five]);
  expect($r['status'])->toBe(422)->and($r['json']['errors'])->toHaveKey('cutiemarks');

  $r = $admin->request('PUT', "/cg/appearance/$appearanceId/cutiemarks", ['CMData' => json_encode([['id' => 987654]])]);
  expect($r['status'])->toBe(422)->and($r['json']['errors'])->toHaveKey('cutiemarks');
});

it('has no sprite until one is uploaded', function () {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
  $created = createOfficial($admin);
  $path = '/cg/appearance/' . $created['id'] . '/sprite';

  expect($admin->request('DELETE', $path)['status'])->toBe(404);

  $admin->request('DELETE', '/cg/appearance/' . $created['id']);
});

it('needs two color groups to reorder them', function () {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
  $created = createOfficial($admin);
  $path = '/cg/appearance/' . $created['id'] . '/colorgroups';

  expect($admin->get($path)['status'])->toBe(409);

  $admin->request('DELETE', '/cg/appearance/' . $created['id']);
});

it('clears an appearance selectively with 204', function () {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
  $created = createOfficial($admin);

  $r = $admin->request('DELETE', '/cg/appearance/' . $created['id'] . '/selective', ['wipe_notes' => 1]);
  expect($r['status'])->toBe(204);

  $admin->request('DELETE', '/cg/appearance/' . $created['id']);
});
