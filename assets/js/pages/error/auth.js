(function() {
  'use strict';

  // If this page is showing in a sign-in popup, report the failure to the page that opened it
  // (see $.openAuthPopup in global.jsx). window.opener can't be used: DeviantArt's sign-in pages cut
  // the link between the two windows. Only close once acknowledged, so this page stays put when it's
  // shown in a regular tab (the full-page redirect sign-in) where nothing is listening.
  if (typeof BroadcastChannel !== 'function')
    return;

  const $content = $('#content');
  const channel = new BroadcastChannel('mlpvc-da-auth');
  channel.onmessage = e => {
    if (e.data && e.data.type === 'ack'){
      channel.close();
      window.close();
    }
  };
  channel.postMessage({
    type: 'result',
    success: false,
    title: $content.children('h1').html(),
    notice: $content.children('.notice').html(),
  });
})();
