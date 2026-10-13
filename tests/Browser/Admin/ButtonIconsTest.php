<?php

use Tests\Browser\Helpers\TestSeederConstants;

// The buttons that carry an icon on the old site (a typcn class on the button, or a typcn child) have one on every implementation (an svg on
// Celestia), so a restyled button does not silently lose it.

$base = TestSeederConstants::baseUrl();

$hasIcon = fn(string $selector) => "(() => { const b = document.querySelector('$selector'); return !!b && (/typcn/.test(b.className) || !!b.querySelector('svg, [class*=typcn]')); })()";

it('shows an icon on the metadata and guide relations buttons of an episode', function () use ($base, $hasIcon) {
  visit(TestSeederConstants::loginUrl(TestSeederConstants::ADMIN_ID))
    ->navigate($base . '/episode/' . TestSeederConstants::SHOW_ID)
    ->assertNoJavaScriptErrors()
    ->assertScript($hasIcon('#edit-show'), true)
    ->assertScript($hasIcon('#cg-relations'), true);
});
