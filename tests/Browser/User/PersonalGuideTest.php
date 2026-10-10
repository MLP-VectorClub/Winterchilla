<?php

use Tests\Browser\Helpers\ApiClient;
use Tests\Browser\Helpers\TestSeederConstants;

$base    = TestSeederConstants::baseUrl();
$userId  = TestSeederConstants::USER_ID;
$adminId = TestSeederConstants::ADMIN_ID;

it('shows a guest the personal color guide list', function () use ($base, $userId) {
  visit($base . '/users/' . $userId . '/cg')
    ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
    ->assertSee('Personal Color Guide')
    ->assertSee("TestUser's Personal Color Guide");
});

it('shows the owner their own personal color guide list', function () use ($base, $userId) {
  visit(TestSeederConstants::loginUrl($userId))
    ->navigate($base . '/users/' . $userId . '/cg')
    ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
    ->assertSee('Personal Color Guide')
    ->assertSee("TestUser's Personal Color Guide");
});

it('shows the owner their own point history page', function () use ($base, $userId) {
  visit(TestSeederConstants::loginUrl($userId))
    ->navigate($base . '/users/' . $userId . '/cg/point-history')
    ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
    ->assertSee('Point History')
    ->assertSee('Your Point History');
});

it('shows the owner their own slot history page', function () use ($base, $userId) {
  visit(TestSeederConstants::loginUrl($userId))
    ->navigate($base . '/users/' . $userId . '/cg/slot-history')
    ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
    ->assertSee('Point History')
    ->assertSee('Your Point History');
});

it('lets staff view another user\'s point history', function () use ($base, $userId, $adminId) {
  visit(TestSeederConstants::loginUrl($adminId))
    ->navigate($base . '/users/' . $userId . '/cg/point-history')
    ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
    ->assertSee('Point History')
    ->assertSee("TestUser's Point History");
});

it('denies a guest access to a user\'s point history', function () use ($base, $userId) {
  visit($base . '/users/' . $userId . '/cg/point-history')
    ->assertSee('403');
});

it('lists the private personal appearance to guests only as a locked entry', function () use ($base, $userId) {
  visit($base . '/users/' . $userId . '/cg')
    ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
    ->assertSee('Personal Test Pony')
    ->assertSee('Private Test Pony')
    ->assertPresent('.typcn-lock-closed');
});

it('hides the private personal appearance page from guests unless they have its token', function () use ($base, $userId) {
  $path = '/users/' . $userId . '/cg/v/' . TestSeederConstants::PRIVATE_PERSONAL_APPEARANCE_ID . '-Private-Test-Pony';

  visit($base . $path)->assertSee('403');

  visit($base . $path . '?token=' . TestSeederConstants::PRIVATE_PERSONAL_TOKEN)
    ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
    ->assertSee('Private Test Pony');
});

it('shows the owner and staff the private personal appearance too', function () use ($base, $userId, $adminId) {
  visit(TestSeederConstants::loginUrl($userId))
    ->navigate($base . '/users/' . $userId . '/cg')
    ->assertSee('Personal Test Pony')
    ->assertSee('Private Test Pony');

  visit(TestSeederConstants::loginUrl($adminId))
    ->navigate($base . '/users/' . $userId . '/cg')
    ->assertSee('Private Test Pony');
});

it('shows a personal appearance page with its color group', function () use ($base, $userId) {
  visit($base . '/users/' . $userId . '/cg/v/' . TestSeederConstants::PERSONAL_APPEARANCE_ID . '-Personal-Test-Pony')
    ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
    ->assertSee('Personal Test Pony')
    ->assertSee('Personal Coat')
    ->assertSee('Personal Base');
});

it('shows the point history with the seeded grant to the owner', function () use ($base, $userId) {
  visit(TestSeederConstants::loginUrl($userId))
    ->navigate($base . '/users/' . $userId . '/cg/point-history')
    ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
    ->assertSee('Seeded contract test grant');
});

it('lets the owner open their private personal appearance page', function () use ($base, $userId) {
  visit(TestSeederConstants::loginUrl($userId))
    ->navigate($base . '/users/' . $userId . '/cg/v/' . TestSeederConstants::PRIVATE_PERSONAL_APPEARANCE_ID . '-Private-Test-Pony')
    ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
    ->assertSee('Private Test Pony');
});

it('lets staff give personal guide points from a user\'s profile', function () use ($base, $userId, $adminId) {
  visit(TestSeederConstants::loginUrl($adminId))
    ->navigate($base . '/users/' . $userId)
    ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
    ->click('#give-pcg-points')
    // The seeded personal appearances use up more slots than the user has, so the form requires topping them up first
    ->fill('input[name="amount"]', '15')
    ->click('[data-testid="dialog-btn-continue"]')
    ->click('[data-testid="dialog-btn-confirm"]')
    ->assertSee("You've successfully given 15 points to TestUser");
});


// Celestia hands the share token of a private appearance on to the files of its page, the old site built different addresses for them
it('opens the files of a private personal appearance for a guest with its share token', function () use ($base, $userId) {
  $id = TestSeederConstants::PRIVATE_PERSONAL_APPEARANCE_ID;
  $token = TestSeederConstants::PRIVATE_PERSONAL_TOKEN;
  $page = visit($base . "/users/$userId/cg/v/$id-Private-Test-Pony?token=$token")
    ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
    ->assertSee('Private Test Pony');

  // The "View as PNG" button keeps the token, and what it points to opens with it and not without
  $href = $page->script("document.querySelector('a[href*=\"/image?type=palette\"]').getAttribute('href')");
  expect($href)->toContain("token=$token");
  $url = $base . $href;
  $status = function (string $address): int {
    $ch = curl_init($address);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true]);
    curl_exec($ch);

    return (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
  };
  expect($status($url))->toBe(200)->and($status(str_replace("&token=$token", '', $url)))->toBe(403);
})->skip(fn() => !TestSeederConstants::external(), 'Celestia only')->group('celestia-only');

it('gives the owner a share link that opens a private personal appearance for anybody', function () use ($base, $userId) {
  $id = TestSeederConstants::PRIVATE_PERSONAL_APPEARANCE_ID;
  $owner = visit(TestSeederConstants::loginUrl($userId))
    ->navigate($base . "/users/$userId/cg/v/$id-Private-Test-Pony")
    ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
    ->click('button:has-text("Share")')
    ->assertSee('This appearance is private, but by using this link you can give anyone access to the colors');
  $link = trim((string)$owner->script("document.querySelector('.modal-body .input-group-text').textContent"));
  expect($link)->toContain('token=' . TestSeederConstants::PRIVATE_PERSONAL_TOKEN);

  // A visitor who is not signed in follows the link as it is, through the short address that leads to the page
  visit($link)
    ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
    ->assertSee('Private Test Pony');
})->skip(fn() => !TestSeederConstants::external(), 'Celestia only')->group('celestia-only');

it('lets the owner replace the share link of a private appearance, which ends the old one', function () use ($base) {
  $api = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
  $suffix = substr(md5(uniqid('', true)), 0, 6);
  $id = $api->post('/appearances', ['guide' => 'pony', 'label' => "Share Probe $suffix", 'private' => true])['json']['id'];
  $status = function (string $address): int {
    $ch = curl_init($address);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true]);
    curl_exec($ch);

    return (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
  };
  $link = fn($page) => trim((string)$page->script("document.querySelector('.modal-body .input-group-text').textContent"));

  try {
    $page = visit(TestSeederConstants::loginUrl(TestSeederConstants::ADMIN_ID))
      ->navigate($base . "/cg/pony/v/$id")
      ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
      ->click('button:has-text("Share")');
    $before = $link($page);
    preg_match('/token=([^&]+)/', $before, $m);
    $oldToken = $m[1] ?? '';
    expect($oldToken)->not->toBe('');
    expect($status($base . "/api/appearances/$id/palette?format=gpl&token=$oldToken"))->toBe(200);

    // Asking for a new link has to be confirmed, cancelling changes nothing
    $page->click('[data-testid="new-share-link"]')
      ->assertSee('The link above will stop working')
      ->click('[data-testid="new-share-link-confirm"]')
      ->assertSee('There is a new link');

    $after = $link($page);
    expect($after)->not->toBe($before)->and($after)->toContain('token=');
    expect($status($base . "/api/appearances/$id/palette?format=gpl&token=$oldToken"))->toBe(403);
    preg_match('/token=([^&]+)/', $after, $m);
    expect($status($base . "/api/appearances/$id/palette?format=gpl&token=" . ($m[1] ?? 'none')))->toBe(200);
  }
  finally {
    $api->request('DELETE', "/appearances/$id");
  }
})->skip(fn() => !TestSeederConstants::external(), 'Celestia only')->group('celestia-only');
