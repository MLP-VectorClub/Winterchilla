<?php

use Tests\Browser\Helpers\TestSeederConstants;

$base = TestSeederConstants::baseUrl();
$appearanceId = TestSeederConstants::APPEARANCE_ID;

function fetch(string $url, ?string &$contentType = null):array {
  $ch = curl_init($url);
  curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true]);
  $body = curl_exec($ch);
  $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
  return [curl_getinfo($ch, CURLINFO_HTTP_CODE), $body];
}

it('serves the web app manifest as JSON', function () use ($base) {
  [$code, $body] = fetch($base . '/manifest', $type);
  expect($code)->toBe(200)
    ->and($type)->toStartWith('application/json')
    ->and(json_decode($body, true))->toBeArray()->toHaveKey('name');
});

it('shows the browser recognition page at its short URL', function () use ($base) {
  visit($base . '/browser')
    ->assertPathIs('/about/browser')
    ->assertSee('Browser recognition test page');
})->group('winterchilla-only');

it('shows the guide-less blending tool', function () use ($base) {
  visit($base . '/blending')
    ->assertNoJavaScriptErrors()
    ->assertSee('Blending');
});

it('shows the color picker frame', function () use ($base) {
  visit($base . '/cg/picker/frame')
    ->assertNoJavaScriptErrors();
  [$code] = fetch($base . '/cg/picker/frame');
  expect($code)->toBe(200);
});

it('lists episodes and movies on paginated URLs', function () use ($base) {
  visit($base . '/episodes/1')->assertSee('TV Episodes');
  visit($base . '/movies/1')->assertSee('Movies');
});

it('lists events on a paginated URL', function () use ($base) {
  visit($base . '/events/1')
    ->assertNoJavaScriptErrors()
    ->assertSee('Events Archive');
});

it('shows the global logs on a paginated URL to staff', function () use ($base) {
  visit(TestSeederConstants::loginUrl(TestSeederConstants::ADMIN_ID))
    ->navigate($base . '/logs/1')
    ->assertNoJavaScriptErrors()
    ->assertSee('Global logs');
});

it('denies the WebSocket diagnostics page to non-developers', function () use ($base) {
  visit($base . '/admin/wsdiag')->assertSee('403');
  visit(TestSeederConstants::loginUrl(TestSeederConstants::ADMIN_ID))
    ->navigate($base . '/admin/wsdiag')
    ->assertSee('403');
})->group('winterchilla-only');

it('exports an appearance in its file formats', function () use ($base, $appearanceId) {
  $prefix = $base . '/cg/pony/v/' . $appearanceId;

  [$code, $body] = fetch($prefix . '.json', $type);
  expect($code)->toBe(200)->and(json_decode($body, true))->not->toBeNull();

  [$code, $body] = fetch($prefix . '.gpl', $type);
  expect($code)->toBe(200)->and($body)->toContain('GIMP Palette');

  // The preview SVG redirects to a cache-busted URL of the image (Winterchilla: its static /img/ file, which the test server doesn't serve)
  $ch = curl_init($prefix . 'p.svg');
  curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true]);
  curl_exec($ch);
  // Winterchilla redirects to the cache-busted image URL; Celestia proxies the image from the API
  expect(curl_getinfo($ch, CURLINFO_HTTP_CODE))->toBeIn([200, 302]);

  [$code, $body] = fetch($prefix . 'f.svg', $type);
  expect($code)->toBe(200)->and($type)->toStartWith('image/svg+xml');

  [$code, $body] = fetch($prefix . '.png', $type);
  expect($code)->toBe(200)->and($type)->toStartWith('image/png');
});

it('restricts the diagnostic pages to developers', function () use ($base) {
  foreach (['/diagnose/lt/1', '/diagnose/ex/runtime'] as $path) {
    [$code] = fetch($base . $path);
    expect($code)->toBe(403);
  }

  visit(TestSeederConstants::loginUrl(TestSeederConstants::ADMIN_ID))
    ->navigate($base . '/diagnose/ex/runtime')
    ->assertSee('403');
})->group('winterchilla-only');
