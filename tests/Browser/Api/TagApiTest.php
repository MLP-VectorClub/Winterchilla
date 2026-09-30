<?php

use Tests\Browser\Helpers\ApiClient;
use Tests\Browser\Helpers\TestSeederConstants;

// Contract: /tags, /tags/{id} and /tags/{id}/synonym (tag: tags). Staff only.

function makeTag(ApiClient $admin, string $name, array $extra = []):array {
  $r = $admin->post('/tags', ['name' => $name, 'type' => 'app'] + $extra);
  expect($r['status'])->toBe(201);
  return $r['json'];
}

function uniqueTagName(string $prefix):string {
  return $prefix . '-' . substr(md5(uniqid('', true)), 0, 8);
}

it('requires staff for every tag endpoint', function () {
  $guest = ApiClient::guest();
  $user = ApiClient::loggedInAs(TestSeederConstants::USER_ID);

  foreach ([['GET', '/tags'], ['POST', '/tags/recount-uses'], ['GET', '/tags/1'], ['POST', '/tags'], ['PUT', '/tags/1'], ['DELETE', '/tags/1'], ['PUT', '/tags/1/synonym'], ['DELETE', '/tags/1/synonym']] as [$method, $path]) {
    expect($guest->request($method, $path)['status'])->toBe(401, "$method $path as guest")
      ->and($user->request($method, $path)['status'])->toBe(403, "$method $path as user");
  }
});

it('validates new tags with 422 and field errors', function () {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);

  $r = $admin->post('/tags');
  expect($r['status'])->toBe(422)
    ->and($r['json'])->toHaveKeys(['message', 'errors'])
    ->and($r['json']['errors'])->toHaveKey('name');

  $r = $admin->post('/tags', ['name' => 'x']);
  expect($r['status'])->toBe(422)->and($r['json']['errors'])->toHaveKey('name');

  $r = $admin->post('/tags', ['name' => uniqueTagName('bad-type'), 'type' => 'nonsense']);
  expect($r['status'])->toBe(422)->and($r['json']['errors'])->toHaveKey('type');
});

it('creates, reads, updates and deletes a tag', function () {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
  $name = uniqueTagName('contract');

  $tag = makeTag($admin, $name, ['title' => 'A contract tag']);
  expect($tag)->toHaveKeys(['id', 'name', 'type', 'title', 'uses', 'synonymOf'])
    ->and($tag['name'])->toBe($name)
    ->and($tag['synonymOf'])->toBeNull();

  $r = $admin->post('/tags', ['name' => $name, 'type' => 'app']);
  expect($r['status'])->toBe(422)->and($r['json']['errors'])->toHaveKey('name');

  $r = $admin->get('/tags/' . $tag['id']);
  expect($r['status'])->toBe(200)->and($r['json'])->toHaveKey('synonymOf')->not->toHaveKey('status')
    ->and($r['json']['title'])->toBe('A contract tag');

  $r = $admin->request('PUT', '/tags/' . $tag['id'], ['name' => $name . '-2', 'type' => 'cat']);
  expect($r['status'])->toBe(200)->and($r['json']['name'])->toBe($name . '-2')->and($r['json']['type'])->toBe('cat');

  $r = $admin->request('DELETE', '/tags/' . $tag['id']);
  expect($r['status'])->toBe(204)->and($r['body'])->toBe('');
  expect($admin->get('/tags/' . $tag['id'])['status'])->toBe(404);
  expect($admin->request('DELETE', '/tags/' . $tag['id'])['status'])->toBe(404);
});

it('lists tags and autocompletes by name', function () {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
  $name = uniqueTagName('listed');
  $tag = makeTag($admin, $name);

  $r = $admin->get('/tags');
  expect($r['status'])->toBe(200)->and($r['json'])->toBeArray();
  $ids = array_column($r['json'], 'id');
  expect($ids)->toContain($tag['id']);

  $r = $admin->get('/tags', ['s' => $name]);
  expect($r['status'])->toBe(200)
    ->and($r['json'][0])->toHaveKeys(['id', 'name', 'type', 'uses', 'synonymOf'])
    ->and($r['json'][0]['name'])->toBe($name);

  $admin->request('DELETE', '/tags/' . $tag['id']);
});

it('confirms before deleting a tag that is in use', function () {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
  $tag = makeTag($admin, uniqueTagName('inuse'), ['addTo' => TestSeederConstants::APPEARANCE_ID]);
  expect($tag)->toHaveKey('tags');

  $r = $admin->request('DELETE', '/tags/' . $tag['id']);
  expect($r['status'])->toBe(409)->and($r['json'])->toHaveKeys(['message', 'uses'])->and($r['json']['uses'])->toBe(1);

  $r = $admin->request('DELETE', '/tags/' . $tag['id'], ['sanityCheck' => 1]);
  expect($r['status'])->toBe(204);
});

it('warns instead of failing when a new tag cannot be added to the requested appearance', function () {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);

  $tag = makeTag($admin, uniqueTagName('warn'), ['addTo' => 987654]);
  expect($tag)->toHaveKey('warning');

  $admin->request('DELETE', '/tags/' . $tag['id']);
});

it('recounts tag uses', function () {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
  $tag = makeTag($admin, uniqueTagName('recount'));

  $r = $admin->post('/tags/recount-uses', ['tagIds' => (string)$tag['id']]);
  expect($r['status'])->toBe(200)
    ->and($r['json'])->toHaveKeys(['message', 'counts'])->not->toHaveKey('status');

  $r = $admin->post('/tags/recount-uses');
  expect($r['status'])->toBe(422)->and($r['json']['errors'])->toHaveKey('tagIds');

  $admin->request('DELETE', '/tags/' . $tag['id']);
});

it('makes a tag a synonym of another and removes the synonym again', function () {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
  $source = makeTag($admin, uniqueTagName('source'));
  $target = makeTag($admin, uniqueTagName('target'));
  $path = '/tags/' . $source['id'] . '/synonym';

  $r = $admin->request('PUT', $path);
  expect($r['status'])->toBe(422)->and($r['json']['errors'])->toHaveKey('targetId');
  expect($admin->request('PUT', $path, ['targetId' => 987654])['status'])->toBe(422);

  $r = $admin->request('PUT', $path, ['targetId' => $target['id']]);
  expect($r['status'])->toBe(200)->and($r['json']['target']['id'])->toBe($target['id']);

  // Already a synonym now
  expect($admin->request('PUT', $path, ['targetId' => $target['id']])['status'])->toBe(409);
  $r = $admin->get('/tags', ['not' => $source['id'], 'action' => 'synon']);
  expect($r['status'])->toBe(409)->and($r['json']['synonymOf']['id'])->toBe($target['id']);

  $r = $admin->request('DELETE', $path, ['keepTagged' => 1]);
  expect($r['status'])->toBe(200)->and($r['json'])->toHaveKey('keepTagged');
  expect($admin->request('DELETE', $path)['status'])->toBe(204);

  expect($admin->request('PUT', '/tags/987654/synonym', ['targetId' => $target['id']])['status'])->toBe(404);

  $admin->request('DELETE', '/tags/' . $source['id']);
  $admin->request('DELETE', '/tags/' . $target['id']);
});

it('reports validation messages as plain text, not HTML', function () {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);

  // Clients escape messages when they show them as HTML, so the server must not have escaped them already
  $r = $admin->post('/tags', ['name' => 'bad<b>tag', 'type' => 'app']);
  expect($r['status'])->toBe(422)
    ->and(implode(' ', $r['json']['errors']['name']))->toContain('<b>')->not->toContain('&lt;');
});
