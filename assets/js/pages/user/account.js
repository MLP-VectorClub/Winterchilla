(function () {
  'use strict';
  const { username, userId, sameUser } = window;

  const $changeEmailForm = $('#change-email');
  if ($changeEmailForm.length === 1) {
    const $resendVerification = $('#resend-verification');
    const sendRequest = data => {
      $.API.post(`/user/${userId}/email`, data).done(function(resp = {}) {
        $.Dialog.success(false, resp.message, true);
      }).fail($.API.fail());
    };
    $changeEmailForm.on('submit', (e) => {
      e.preventDefault();

      const data = $changeEmailForm.mkData();
      $.Dialog.wait('Update e-mail address');
      sendRequest(data);
    });
    $resendVerification.on('click', (e) => {
      e.preventDefault();

      const data = { resend: true };
      $.Dialog.wait('Resend e-mail verification');
      sendRequest(data);
    });
  }

  const $changePasswordForm = $('#change-password');
  if ($changePasswordForm.length === 1) {
    const $newPasswordInput = $('#new-password-input');
    const $revealNewPassword = $('#reveal-new-password');
    const $hideNewPassword = $('#hide-new-password');

    $revealNewPassword.on('click', e => {
      e.preventDefault();

      $revealNewPassword.addClass('hidden');
      $hideNewPassword.removeClass('hidden');
      $newPasswordInput.removeAttr('type').prop('type', 'text');
    });

    $hideNewPassword.on('click', e => {
      e.preventDefault();

      $hideNewPassword.addClass('hidden');
      $revealNewPassword.removeClass('hidden');
      $newPasswordInput.removeAttr('type').prop('type', 'password');
    });

    $changePasswordForm.on('submit', (e) => {
      e.preventDefault();

      const data = $changePasswordForm.mkData();

      $.Dialog.wait('Update password');
      $.API.post(`/user/password`, data).done(function(resp = {}) {
        $.Dialog.segway(false, resp.message);
      }).fail($.API.fail());
    });
  }

  let $signoutBtn = $('#signout'),
    $sessionList = $('.session-list');
  $sessionList.find('button.remove').off('click').on('click', function(e) {
    e.preventDefault();

    let title = 'Deleting session',
      $btn = $(this),
      $li = $btn.closest('li'),
      browser = $li.children('.browser').text().trim(),
      $platform = $li.children('.platform'),
      platform = $platform.length ? ` on <em>${$platform.children('strong').text().trim()}</em>` : '';

    // First item is sometimes the current session, trigger logout button instead
    if ($li.index() === 0 && $li.children().last().text().indexOf('Current') !== -1)
      return $signoutBtn.triggerHandler('click');

    let sessionID = $li.attr('id').replace(/^session-/, '');

    if (typeof sessionID === 'undefined')
      return $.Dialog.fail(title, 'Could not locate Session ID, please reload the page and try again.');

    $.Dialog.confirm(title, `${sameUser ? 'You' : username} will be signed out of <em>${browser}</em>${platform}.<br>Continue?`, function(sure) {
      if (!sure) return;

      $.Dialog.wait(title, `Signing out of ${browser}${platform}`);

      $.API.delete(`/user/session/${sessionID}`).done(function(resp = {}) {
        if ($li.siblings().length !== 0){
          $li.remove();
          return $.Dialog.close();
        }

        $.Navigation.reload(true);
      }).fail($.API.fail(title));
    });
  });
  $sessionList.find('button.useragent').on('click', function(e) {
    e.preventDefault();

    let $this = $(this);
    $.Dialog.info(`User-Agent for session ${$this.parents('li').attr('id').substring(8)}`, `<code>${$this.data('agent')}</code>`);
  });
  $('#sign-out-everywhere').on('click', function() {
    $.Dialog.confirm('Sign out from ALL sessions', 'This will invalidate ALL sessions. Continue?', function(sure) {
      if (!sure) return;

      $.Dialog.wait(false, 'Signing out');

      $.API.post('/da-auth/signout?everywhere', { userId }).done(function(resp = {}) {
        $.Navigation.reload(true);
      }).fail($.API.fail());
    });
  });

  const $discordConnect = $('#discord-connect');
  $discordConnect.find('.sync').on('click', function(e) {
    e.preventDefault();

    $.Dialog.wait('Syncing');

    $.API.post(`/discord-connect/sync/${userId}`).done(function() {
      $.Navigation.reload(true);
    }).fail($.API.failWith(body => {
      if (body.segway)
        $.Dialog.segway(false, $.mk('div').attr('class', 'color-red').html(body.message));
      else $.Dialog.fail(false, body.message);
    }));
  });
  $discordConnect.find('.unlink').on('click', function(e) {
    e.preventDefault();

    const you = sameUser ? 'you' : 'they';
    const your = sameUser ? 'your' : 'their';

    $.Dialog.confirm(
      'Unlink Discord account',
      `<p>If you unlink ${sameUser ? 'your' : 'this user\'s'} Discord account ${you} will no longer be able to use ${your} Discord avatar on the site or submit new entries to events for Discord server members.</p>
			<p>Are you sure you want to unlink ${your} Discord account?</p>`,
      sure => {
        if (!sure) return;

        $.Dialog.wait(false);

        $.API.post(`/discord-connect/unlink/${userId}`).done(function(resp = {}) {
          $.Dialog.segway(false, resp.message);
        }).fail($.API.fail());
      },
    );
  });
  const $syncInfo = $('#discord-sync-info');
  if ($syncInfo.length){
    const $timeTag = $syncInfo.find('time');
    const cooldown = parseInt($syncInfo.attr('data-cooldown'), 10);
    $timeTag.data('dyntime-beforeupdate', diff => {
      if (diff.time > cooldown){
        $syncInfo.find('.wait-message').remove();
        $discordConnect.find('.sync').enable();
        $timeTag.removeData('dyntime-beforeupdate');
      }
    });
  }
})()
