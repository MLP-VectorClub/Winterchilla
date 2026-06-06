<?php

use Tests\Browser\Helpers\TestSeederConstants;

$base = TestSeederConstants::BASE_URL;
$page = $base . '/test-dialog';

// ── Basic dialog types ────────────────────────────────────────────────────

it('fail dialog opens with correct content and close button', function () use ($page) {
  visit($page)
    ->assertNoJavaScriptErrors()
    ->click('[data-testid="trigger-fail"]')
    ->assertSee('Error Title')
    ->assertSee('Error message content')
    ->assertVisible('[data-testid="dialog-btn-close"]')
    ->assertScript('$.Dialog.isOpen() === true')
    ->assertScript('$.Dialog._open !== undefined && $.Dialog._open.type === "fail"');
});

it('success dialog opens with close button', function () use ($page) {
  visit($page)
    ->assertNoJavaScriptErrors()
    ->click('[data-testid="trigger-success"]')
    ->assertSee('Success Title')
    ->assertSee('Success message')
    ->assertVisible('[data-testid="dialog-btn-close"]');
});

it('wait dialog appends ellipsis to content', function () use ($page) {
  visit($page)
    ->assertNoJavaScriptErrors()
    ->click('[data-testid="trigger-wait"]')
    ->assertSee('Please hold…')
    ->assertScript('$.Dialog._open.type === "wait"');
});

it('request dialog has submit and cancel buttons', function () use ($page) {
  visit($page)
    ->assertNoJavaScriptErrors()
    ->click('[data-testid="trigger-request"]')
    ->assertVisible('[data-testid="dialog-btn-submit"]')
    ->assertVisible('[data-testid="dialog-btn-cancel"]')
    ->assertScript('$.Dialog._open.type === "request"');
});

it('request dialog callback receives the form element', function () use ($page) {
  visit($page)
    ->assertNoJavaScriptErrors()
    ->click('[data-testid="trigger-request"]')
    ->assertVisible('[data-testid="dialog-btn-submit"]')
    ->assertScript('window.__lastRequestForm !== undefined')
    ->assertScript('window.__lastRequestForm instanceof HTMLFormElement')
    ->assertScript('window.__lastRequestForm.id === "test-form"');
});

it('confirm dialog has confirm and cancel buttons', function () use ($page) {
  visit($page)
    ->assertNoJavaScriptErrors()
    ->click('[data-testid="trigger-confirm"]')
    ->assertSee('Confirm Title')
    ->assertSee('Are you sure?')
    ->assertVisible('[data-testid="dialog-btn-confirm"]')
    ->assertVisible('[data-testid="dialog-btn-cancel"]');
});

it('confirm dialog calls handler with true when confirmed', function () use ($page) {
  visit($page)
    ->assertNoJavaScriptErrors()
    ->click('[data-testid="trigger-confirm"]')
    ->assertVisible('[data-testid="dialog-btn-confirm"]')
    ->click('[data-testid="dialog-btn-confirm"]')
    ->assertScript('window.__lastConfirmResult === true');
});

it('confirm dialog calls handler with false when cancelled', function () use ($page) {
  visit($page)
    ->assertNoJavaScriptErrors()
    ->click('[data-testid="trigger-confirm"]')
    ->assertVisible('[data-testid="dialog-btn-cancel"]')
    ->click('[data-testid="dialog-btn-cancel"]')
    ->assertScript('window.__lastConfirmResult === false');
});

it('info dialog opens with close button', function () use ($page) {
  visit($page)
    ->assertNoJavaScriptErrors()
    ->click('[data-testid="trigger-info"]')
    ->assertSee('Info Title')
    ->assertSee('Info message')
    ->assertVisible('[data-testid="dialog-btn-close"]');
});

// ── Dialog state ──────────────────────────────────────────────────────────

it('isOpen returns true while open and false after close', function () use ($page) {
  visit($page)
    ->assertNoJavaScriptErrors()
    ->assertScript('$.Dialog.isOpen() === false')
    ->click('[data-testid="trigger-info"]')
    ->assertVisible('[data-testid="dialog-btn-close"]')
    ->assertScript('$.Dialog.isOpen() === true')
    ->click('[data-testid="dialog-btn-close"]')
    ->assertScript('$.Dialog.isOpen() === false');
});

it('_open reflects dialog type and is undefined when closed', function () use ($page) {
  visit($page)
    ->assertNoJavaScriptErrors()
    ->assertScript('$.Dialog._open === undefined')
    ->click('[data-testid="trigger-fail"]')
    ->assertVisible('[data-testid="dialog-btn-close"]')
    ->assertScript('$.Dialog._open !== undefined && $.Dialog._open.type === "fail"')
    ->click('[data-testid="dialog-btn-close"]')
    ->assertScript('$.Dialog._open === undefined');
});

it('dialog overlay is removed from DOM after close', function () use ($page) {
  visit($page)
    ->assertNoJavaScriptErrors()
    ->click('[data-testid="trigger-info"]')
    ->assertVisible('#dialogOverlay')
    ->click('[data-testid="dialog-btn-close"]')
    ->assertScript('document.getElementById("dialogOverlay") === null');
});

it('body gets dialog-open class when open and loses it on close', function () use ($page) {
  visit($page)
    ->assertNoJavaScriptErrors()
    ->click('[data-testid="trigger-info"]')
    ->assertVisible('[data-testid="dialog-btn-close"]')
    ->assertScript('document.body.classList.contains("dialog-open")')
    ->click('[data-testid="dialog-btn-close"]')
    ->assertScript('!document.body.classList.contains("dialog-open")');
});

// ── Inline notices ────────────────────────────────────────────────────────

it('inline fail notice appears inside open request dialog', function () use ($page) {
  visit($page)
    ->assertNoJavaScriptErrors()
    ->click('[data-testid="trigger-request-for-inline"]')
    ->assertVisible('[data-testid="dialog-btn-submit"]')
    ->click('[data-testid="dialog-trigger-inline-fail"]')
    ->assertSee('Inline fail notice')
    ->assertVisible('[data-testid="dialog-btn-submit"]')
    ->assertScript('$.Dialog._open.type === "request"');
});

it('inline wait notice appears inside open request dialog', function () use ($page) {
  visit($page)
    ->assertNoJavaScriptErrors()
    ->click('[data-testid="trigger-request-for-inline"]')
    ->assertVisible('[data-testid="dialog-btn-submit"]')
    ->click('[data-testid="dialog-trigger-inline-wait"]')
    ->assertSee('Loading inline…')
    ->assertScript('$.Dialog._open.type === "request"');
});

it('inline wait notice disables dialog buttons', function () use ($page) {
  visit($page)
    ->assertNoJavaScriptErrors()
    ->click('[data-testid="trigger-request-for-inline"]')
    ->assertVisible('[data-testid="dialog-btn-submit"]')
    ->click('[data-testid="dialog-trigger-inline-wait"]')
    ->assertSee('Loading inline…')
    ->assertScript('document.querySelector("[data-testid=\'dialog-btn-submit\']").disabled === true');
});

it('clearNotice hides the inline notice', function () use ($page) {
  visit($page)
    ->assertNoJavaScriptErrors()
    ->click('[data-testid="trigger-request-for-inline"]')
    ->assertVisible('[data-testid="dialog-btn-submit"]')
    ->click('[data-testid="dialog-trigger-inline-fail"]')
    ->assertSee('Inline fail notice')
    ->click('[data-testid="dialog-trigger-clear-notice"]')
    ->assertScript('(function(){ var n = document.querySelector("#dialogContent .notice"); return !n || n.style.display === "none"; })()')
    ->assertScript('$.Dialog._open.type === "request"');
});

it('clearNotice re-enables buttons after clearing a wait notice', function () use ($page) {
  visit($page)
    ->assertNoJavaScriptErrors()
    ->click('[data-testid="trigger-request-for-inline"]')
    ->assertVisible('[data-testid="dialog-btn-submit"]')
    ->click('[data-testid="dialog-trigger-inline-wait"]')
    ->assertSee('Loading inline…')
    ->assertScript('document.querySelector("[data-testid=\'dialog-btn-submit\']").disabled === true')
    ->click('[data-testid="dialog-trigger-clear-notice"]')
    ->assertScript('document.querySelector("[data-testid=\'dialog-btn-submit\']").disabled === false');
});

// ── Styling correctness ───────────────────────────────────────────────────

it('colored dialog applies color class to content area', function () use ($page) {
  visit($page)
    ->assertNoJavaScriptErrors()
    ->click('[data-testid="trigger-confirm"]')
    ->assertVisible('[data-testid="dialog-btn-confirm"]')
    ->assertScript('document.querySelector("#dialogContent > div").classList.contains("orange")');
});

it('inline notice is placed inside content div not as a direct child of dialogContent', function () use ($page) {
  visit($page)
    ->assertNoJavaScriptErrors()
    ->click('[data-testid="trigger-request-for-inline"]')
    ->assertVisible('[data-testid="dialog-btn-submit"]')
    ->click('[data-testid="dialog-trigger-inline-fail"]')
    ->assertSee('Inline fail notice')
    ->assertScript('document.querySelector("#dialogContent > div > .notice") !== null')
    ->assertScript('document.querySelector("#dialogContent > .notice") === null');
});

it('inline fail with title updates the dialog header', function () use ($page) {
  visit($page)
    ->assertNoJavaScriptErrors()
    ->click('[data-testid="trigger-request-for-inline"]')
    ->assertVisible('[data-testid="dialog-btn-submit"]')
    ->click('[data-testid="dialog-trigger-inline-fail-titled"]')
    ->assertSee('Notice Title')
    ->assertScript('$.Dialog._open.type === "request"');
});
