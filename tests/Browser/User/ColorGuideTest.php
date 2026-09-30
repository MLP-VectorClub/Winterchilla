<?php

use Tests\Browser\Helpers\TestSeederConstants;

$base         = TestSeederConstants::BASE_URL;
$appearanceId = TestSeederConstants::APPEARANCE_ID;
$cutiemarkId  = TestSeederConstants::CUTIEMARK_ID;

/**
 * Plain HTTP GET (following redirects) for non-page responses like SVGs and downloads.
 *
 * @return array{status: int, headers: array<string, string>, body: string}
 */
function httpGet(string $url): array {
  $body = file_get_contents($url, false, stream_context_create(['http' => ['ignore_errors' => true]]));
  // The header list holds every redirect hop; only keep the final response's status/headers
  $status = 0;
  $headers = [];
  foreach (http_get_last_response_headers() ?? [] as $line) {
    if (preg_match('~^HTTP/\S+ (\d+)~', $line, $m)) {
      $status = (int)$m[1];
      $headers = [];
    }
    else if (str_contains($line, ':')) {
      [$name, $value] = explode(':', $line, 2);
      $headers[strtolower(trim($name))] = trim($value);
    }
  }

  return ['status' => $status, 'headers' => $headers, 'body' => (string)$body];
}

it('shows the seeded appearance detail page', function () use ($base, $appearanceId) {
  visit($base . '/cg/pony/v/' . $appearanceId . '-Twilight-Sparkle')
    ->assertNoJavaScriptErrors()
    ->assertSee('Twilight Sparkle')
    ->assertSee('Twilight Sparkle');
});

it('shows the pony color guide change list', function () use ($base) {
  visit($base . '/cg/pony/changes')
    ->assertNoJavaScriptErrors()
    ->assertSee('Major Friendship is Magic Color Changes')
    ->assertSee('Seeded newest major change')
    ->assertSee('Seeded older major change');
});

it('shows the color picker tool', function () use ($base) {
  visit($base . '/cg/picker')
    ->assertNoJavaScriptErrors()
    ->assertTitleContains('Color Picker');
});

it('lets a user open an image file in the color picker', function () use ($base) {
  $fixture = realpath(__DIR__ . '/../fixtures/picker-sample.png');

  visit($base . '/cg/picker')
    ->assertNoJavaScriptErrors()
    ->wait(1)
    ->withinFrame('#picker-frame', function ($frame) use ($fixture) {
      $frame->attach('.fileinput', $fixture)
        ->wait(1)
        ->assertNoJavaScriptErrors();

      $tabCount = $frame->script('document.getElementById("tabbar").children.length');
      expect($tabCount)->toBe(1);
    });
});

it('lets a user paste an image from the clipboard in the color picker', function () use ($base) {
  visit($base . '/cg/picker')
    ->assertNoJavaScriptErrors()
    ->wait(1)
    ->withinFrame('#picker-frame', function ($frame) {
      $frame->click('File')
        ->click('#paste-image')
        ->wait(1)
        ->assertNoJavaScriptErrors();

      // Simulate an OS clipboard paste of an image, the same way the
      // browser dispatches a "paste" ClipboardEvent on Ctrl+V.
      $result = $frame->script('async () => {
        const pasteDiv = document.getElementById("paste-div");
        const canvas = document.createElement("canvas");
        canvas.width = 5;
        canvas.height = 5;
        canvas.getContext("2d").fillRect(0, 0, 5, 5);
        const blob = await new Promise(resolve => canvas.toBlob(resolve, "image/png"));
        const file = new File([blob], "clipboard.png", { type: "image/png" });

        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);

        pasteDiv.dispatchEvent(new ClipboardEvent("paste", {
          bubbles: true,
          cancelable: true,
          clipboardData: dataTransfer,
        }));

        return true;
      }');
      expect($result)->toBeTrue();

      $frame->wait(1)
        ->assertNoJavaScriptErrors();

      $tabCount = $frame->script('document.getElementById("tabbar").children.length');
      expect($tabCount)->toBe(1);
    });
});

it('shows admin controls on appearance page when logged in as admin', function () use ($base, $appearanceId) {
  visit($base . '/test-login/' . TestSeederConstants::ADMIN_ID)
    ->navigate($base . '/cg/pony/v/' . $appearanceId . '-Twilight-Sparkle')
    ->assertNoJavaScriptErrors()
    ->assertSee('Twilight Sparkle')
    ->assertSee('Twilight Sparkle');
});

it('shows the new appearance button on guide page for admins', function () use ($base) {
  visit($base . '/test-login/' . TestSeederConstants::ADMIN_ID)
    ->navigate($base . '/cg/pony')
    ->assertNoJavaScriptErrors()
    ->assertSee('Friendship is Magic Color Guide');
});

it('shows the reverse blending tool', function () use ($base) {
  visit($base . '/cg/blending-reverse')
    ->assertNoJavaScriptErrors()
    ->assertSee('Blending Reverser');
});

it('canonicalizes non-"cg" spellings on the reverse blending tool URL', function () use ($base) {
  visit($base . '/colorguide/blending-reverse')
    ->assertPathIs('/cg/blending-reverse');
});

it('redirects /[cg]/preferred to the guest default guide', function () use ($base) {
  visit($base . '/cg/preferred')
    ->assertPathIs('/cg');
});

it('404s on the not-yet-implemented tag-changes page', function () use ($base, $appearanceId) {
  visit($base . '/cg/pony/tag-changes/' . $appearanceId)
    ->assertSee('404');
});

it('404s requesting a cutiemark SVG that does not exist', function () use ($base) {
  visit($base . '/cg/cutiemark/1.svg')
    ->assertSee('404');
});

it('404s downloading a cutiemark that does not exist', function () use ($base) {
  visit($base . '/cg/cutiemark/download/1')
    ->assertSee('404');
});

it('lists the seeded cutiemark on the appearance page', function () use ($base, $appearanceId, $cutiemarkId) {
  visit($base . '/cg/pony/v/' . $appearanceId . '-Twilight-Sparkle')
    ->assertNoJavaScriptErrors()
    ->assertSee('Cutie Mark')
    ->assertPresent('#cm' . $cutiemarkId)
    ->assertSeeIn('#cm' . $cutiemarkId, 'Facing Left');
});

it('renders the seeded cutiemark SVG', function () use ($base, $cutiemarkId) {
  $res = httpGet($base . '/cg/cutiemark/' . $cutiemarkId . '.svg');

  expect($res['status'])->toBe(200)
    ->and($res['headers']['content-type'] ?? '')->toContain('image/svg+xml')
    ->and($res['body'])->toContain('<svg')
    ->and(simplexml_load_string($res['body']))->not->toBeFalse()
    // svgo (svgo.config.js) drops the fixture's width/height in favor of its viewBox
    ->and($res['body'])->toContain('viewBox="0 0 1000 1000"')
    ->and($res['body'])->not->toContain('width="100%"');
});

it('downloads the rendered cutiemark SVG', function () use ($base, $cutiemarkId) {
  $res = httpGet($base . '/cg/cutiemark/download/' . $cutiemarkId);

  expect($res['status'])->toBe(200)
    ->and($res['headers']['content-disposition'] ?? '')->toContain('attachment')
    ->and($res['headers']['content-disposition'] ?? '')->toContain("Twilight Sparkle's Cutie Mark.svg")
    ->and($res['body'])->toContain('<svg');
});

it('serves the rendered file instead of the source to guests requesting ?source', function () use ($base, $cutiemarkId) {
  $res = httpGet($base . '/cg/cutiemark/download/' . $cutiemarkId . '?source');

  expect($res['status'])->toBe(200)
    ->and($res['headers']['content-disposition'] ?? '')->not->toContain('(source)');
});

it('re-sorts the full list through the API when the sort order changes', function () use ($base) {
  visit($base . '/cg/pony/full')
    ->assertNoJavaScriptErrors()
    ->select('#sort-by', 'label')
    ->assertQueryStringHas('sort_by', 'label')
    ->assertSee('Twilight Sparkle');
});
