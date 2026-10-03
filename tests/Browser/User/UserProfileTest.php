<?php

use Tests\Browser\Helpers\TestSeederConstants;

$base = TestSeederConstants::baseUrl();

it('shows the profile page of the seeded regular user', function () use ($base) {
  visit($base . '/users/' . TestSeederConstants::USER_ID)
    ->assertNoJavaScriptErrors()
    ->assertSee('TestUser');
});

it('shows the profile page of the seeded admin user', function () use ($base) {
  visit($base . '/users/' . TestSeederConstants::ADMIN_ID)
    ->assertNoJavaScriptErrors()
    ->assertSee('TestAdmin');
});

it('shows own account settings when logged in', function () use ($base) {
  visit(TestSeederConstants::loginUrl(TestSeederConstants::USER_ID))
    ->navigate($base . '/users/' . TestSeederConstants::USER_ID . '/account')
    ->assertNoJavaScriptErrors()
    // A fatal renders a bare 500 without the words "Fatal error", so assert real page content
    ->assertSee('Account Settings')
    ->assertSee('DeviantArt Account');
});

it('shows 403 to guests on account settings', function () use ($base) {
  visit($base . '/users/' . TestSeederConstants::USER_ID . '/account')
    ->assertSee('403');
});

it('lists club members for guests and all users for staff', function () use ($base) {
  visit($base . '/users')
    ->assertNoJavaScriptErrors()
    ->assertSee('Club Members');

  visit(TestSeederConstants::loginUrl(TestSeederConstants::ADMIN_ID))
    ->navigate($base . '/users')
    ->assertNoJavaScriptErrors()
    ->assertSee('TestUser')
    ->assertSee('TestAdmin');
});

it('hides /u/[uuid] from guests and non-developers', function () use ($base) {
  visit($base . '/u/' . TestSeederConstants::USER_DA_ID)
    ->assertSee('404');

  visit(TestSeederConstants::loginUrl(TestSeederConstants::USER_ID))
    ->navigate($base . '/u/' . TestSeederConstants::USER_DA_ID)
    ->assertSee('404');
});

it('shows a contributions tab', function () use ($base) {
  visit($base . '/users/' . TestSeederConstants::USER_ID . '/contrib/cms-provided')
    ->assertNoJavaScriptErrors()
    ->assertSee('Cutie Mark vectors provided by')
    ->assertSee('TestUser');
});

it('404s on an unknown contributions type', function () use ($base) {
  visit($base . '/users/' . TestSeederConstants::USER_ID . '/contrib/nonsense')
    ->assertSee('404');
});

it('only shows the requests contributions tab to its owner and staff', function () use ($base) {
  $url = $base . '/users/' . TestSeederConstants::USER_ID . '/contrib/requests';

  visit($url)->assertSee('404');

  visit(TestSeederConstants::loginUrl(TestSeederConstants::USER_ID))
    ->navigate($url)
    ->assertNoJavaScriptErrors()
    ->assertSee('Requests posted by');

  visit(TestSeederConstants::loginUrl(TestSeederConstants::ADMIN_ID))
    ->navigate($url)
    ->assertNoJavaScriptErrors()
    ->assertSee('Requests posted by');
});

it('renders the email verification page', function () use ($base) {
  visit($base . '/users/verify?hash=abc123')
    ->assertNoJavaScriptErrors()
    ->assertSee('Verify E-mail Address');

  visit($base . '/users/verify?hash=abc123&action=block')
    ->assertSee('Block E-mail Address');
});

it('redirects a legacy @username URL to the user ID URL', function () use ($base) {
  visit($base . '/@TestUser')
    // The profile then canonicalizes the URL to include the username
    ->assertUrlIs($base . '/users/' . TestSeederConstants::USER_ID . '-TestUser')
    ->assertSee('TestUser');

  visit($base . '/@TestUser/contrib/cms-provided')
    ->assertUrlIs($base . '/users/' . TestSeederConstants::USER_ID . '/contrib/cms-provided');
});

it('lazy-loads deviation previews on a contributions page through the API', function () use ($base) {
  // The seeded finished post's deviation is cached, so its preview link comes back from the API
  visit($base . '/users/' . TestSeederConstants::ADMIN_ID . '/contrib/finished-posts')
    ->assertNoJavaScriptErrors()
    // The promise placeholder is replaced once the API has answered (a failure would show an error dialog instead)
    ->assertMissing('.deviation-promise')
    ->assertDontSee('Cannot load deviation');
});
