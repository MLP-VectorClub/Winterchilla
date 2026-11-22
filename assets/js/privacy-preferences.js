/**
 * The two privacy choices a visitor can make, shared with the new site (Celestia): they are cookies on the parent domain with the same names and values on
 * both, so a choice made on one site holds on the other.
 *
 *  - `mlpvc_privacy_notice`: when the notice about the privacy policy was dismissed (epoch milliseconds). The notice comes back only after the policy changes
 *  - `mlpvc_stats_optout`: set on the privacy policy page to stay out of the anonymous visitor statistics. Its value is the expiry it asked for (epoch
 *    milliseconds). The websocket server never sees it, so the connection announces it in the handshake (see websocket.js)
 */
(function() {
  'use strict';

  const NOTICE_COOKIE = 'mlpvc_privacy_notice';
  const OPT_OUT_COOKIE = 'mlpvc_stats_optout';
  const SHARED_DOMAIN = 'mlpvector.club';
  const MAX_AGE_SECONDS = 400 * 24 * 60 * 60;

  const readCookie = name => {
    const match = document.cookie.split(';').map(part => part.trim()).find(part => part.startsWith(name + '='));
    return match ? match.slice(name.length + 1) : null;
  };
  const readNumber = name => {
    const value = readCookie(name);
    return value !== null && /^\d+$/.test(value) ? Number(value) : null;
  };
  const domainPart = () => {
    const host = location.hostname;
    return host === SHARED_DOMAIN || host.endsWith('.' + SHARED_DOMAIN) ? `; Domain=${SHARED_DOMAIN}` : '';
  };
  const writeCookie = (name, value, maxAge) => {
    document.cookie = `${name}=${value}; Max-Age=${maxAge}; Path=/${domainPart()}; SameSite=Lax${location.protocol === 'https:' ? '; Secure' : ''}`;
  };

  $.statsOptedOut = () => readNumber(OPT_OUT_COOKIE) !== null;
  const setOptOut = optedOut => {
    writeCookie(OPT_OUT_COOKIE, optedOut ? String(Date.now() + MAX_AGE_SECONDS * 1000) : '', optedOut ? MAX_AGE_SECONDS : 0);
  };

  // Starts the 400 days over on every page load, so the choice does not expire for someone who keeps visiting
  if ($.statsOptedOut())
    setOptOut(true);

  $(function() {
    const $notice = $('#privacy-update-notice');
    if ($notice.length) {
      const updated = Date.parse($notice.attr('data-updated'));
      const dismissedAt = readNumber(NOTICE_COOKIE);
      if (dismissedAt === null || !(dismissedAt >= updated))
        $notice.prop('hidden', false);

      $notice.on('click', '.dismiss-notice', e => {
        e.preventDefault();
        writeCookie(NOTICE_COOKIE, String(Date.now()), MAX_AGE_SECONDS);
        $notice.prop('hidden', true);
      });
    }

    const $form = $('#stats-opt-out-form');
    if ($form.length) {
      const $box = $('#stats-opt-out'), $status = $('#stats-opt-out-status'), $saved = $('#stats-opt-out-saved'), $save = $('#stats-opt-out-save');
      const show = () => {
        const saved = $.statsOptedOut();
        $status.text(saved ? 'You are currently opted out on this device.' : 'You are currently not opted out on this device.');
        $save.prop('disabled', $box.prop('checked') === saved);
      };
      $box.prop('checked', $.statsOptedOut());
      show();
      $form.prop('hidden', false);
      $box.on('change', () => {
        $saved.prop('hidden', true);
        show();
      });
      $form.on('submit', e => {
        e.preventDefault();
        setOptOut($box.prop('checked'));
        // The server only reads the choice in the handshake: connect again so it applies at once
        if ($.WS && $.WS.conn && $.WS.conn.connected) {
          $.WS.conn.disconnect();
          $.WS.conn.connect();
        }
        $saved.prop('hidden', false);
        show();
      });
    }
  });
})();
