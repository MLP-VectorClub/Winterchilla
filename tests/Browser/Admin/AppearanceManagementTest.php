<?php

use Tests\Browser\Helpers\TestSeederConstants;

$base         = TestSeederConstants::BASE_URL;
$appearanceId = TestSeederConstants::APPEARANCE_ID;
$uniqueLabel  = 'E2E Test ' . substr(uniqid(), -6);

it('full appearance lifecycle: create, tag, edit, delete', function () use ($base, $uniqueLabel) {
  // Create
  visit($base . '/test-login/' . TestSeederConstants::ADMIN_ID)
    ->navigate($base . '/cg/pony')
    ->assertNoJavaScriptErrors()
    ->click('[data-testid="create-appearance-btn"]')
    ->fill('[data-testid="form-label-input"]', $uniqueLabel)
    ->click('[data-testid="dialog-btn-save"]')
    ->assertPathContains('/cg/pony/v/')
    ->assertSee($uniqueLabel)
    ->wait(1)
    // Edit label
    ->click('[data-testid="edit-appearance-btn"]')
    ->fill('[data-testid="form-label-input"]', $uniqueLabel . ' Updated')
    ->click('[data-testid="dialog-btn-save"]')
    ->assertSee($uniqueLabel . ' Updated')
    // Delete
    ->click('[data-testid="delete-appearance-btn"]')
    ->assertSee('Delete anyway?')
    ->click('[data-testid="dialog-btn-confirm"]')
    ->assertPathContains('/cg');
});

it('full color group lifecycle: create, edit, delete', function () use ($base, $appearanceId) {
  visit($base . '/test-login/' . TestSeederConstants::ADMIN_ID)
    ->navigate($base . '/cg/pony/v/' . $appearanceId . '-Twilight-Sparkle')
    ->assertNoJavaScriptErrors()
    // Create color group
    ->click('[data-testid="create-colorgroup-btn"]')
    ->fill('[data-testid="form-label-input"]', 'Test Color Group')
    ->fill('[data-testid="form-color-hex"]', '#8B57E4')
    ->fill('[data-testid="form-color-label"]', 'Test Purple')
    ->click('[data-testid="dialog-btn-save"]')
    ->assertSee('Test Color Group')
    // Edit color group
    ->click('[data-testid="edit-colorgroup-btn"]')
    ->fill('[data-testid="form-label-input"]', 'Test Color Group Updated')
    ->click('[data-testid="dialog-btn-save"]')
    ->assertSee('Test Color Group Updated')
    // Delete color group
    ->click('[data-testid="delete-colorgroup-btn"]')
    ->assertSee('By deleting this color group')
    ->click('[data-testid="dialog-btn-confirm"]')
    ->assertDontSee('Test Color Group Updated');
});

it('admin can open the sprite upload dialog', function () use ($base, $appearanceId) {
  visit($base . '/test-login/' . TestSeederConstants::ADMIN_ID)
    ->navigate($base . '/cg/pony/v/' . $appearanceId . '-Twilight-Sparkle')
    ->assertNoJavaScriptErrors()
    ->rightClick('[data-testid="sprite-wrap"]')
    ->assertSee('Upload new sprite')
    ->click('Upload new sprite')
    ->assertSee('Upload sprite image');
});
