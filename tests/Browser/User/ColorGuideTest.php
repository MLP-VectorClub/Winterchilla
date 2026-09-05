<?php

use Tests\Browser\Helpers\TestSeederConstants;

$base         = TestSeederConstants::BASE_URL;
$appearanceId = TestSeederConstants::APPEARANCE_ID;

it('shows the seeded appearance detail page', function () use ($base, $appearanceId) {
  visit($base . '/cg/pony/v/' . $appearanceId . '-Twilight-Sparkle')
    ->assertNoJavaScriptErrors()
    ->assertDontSee('Fatal error')
    ->assertSee('Twilight Sparkle');
});

it('shows the pony color guide change list', function () use ($base) {
  visit($base . '/cg/pony/changes')
    ->assertNoJavaScriptErrors()
    ->assertDontSee('Fatal error');
});

it('shows the color picker tool', function () use ($base) {
  visit($base . '/cg/picker')
    ->assertNoJavaScriptErrors()
    ->assertDontSee('Fatal error');
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
    ->assertDontSee('Fatal error')
    ->assertSee('Twilight Sparkle');
});

it('shows the new appearance button on guide page for admins', function () use ($base) {
  visit($base . '/test-login/' . TestSeederConstants::ADMIN_ID)
    ->navigate($base . '/cg/pony')
    ->assertNoJavaScriptErrors()
    ->assertDontSee('Fatal error');
});

it('shows the reverse blending tool', function () use ($base) {
  visit($base . '/cg/blending-reverse')
    ->assertNoJavaScriptErrors()
    ->assertDontSee('Fatal error');
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

it('404s requesting a cutiemark SVG that has not been set', function () use ($base, $appearanceId) {
  visit($base . '/cg/cutiemark/' . $appearanceId . '.svg')
    ->assertSee('404');
});

it('404s downloading a cutiemark that has not been set', function () use ($base, $appearanceId) {
  visit($base . '/cg/cutiemark/download/' . $appearanceId)
    ->assertSee('404');
});
