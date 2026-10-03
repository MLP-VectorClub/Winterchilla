/* Prepended to every page script by `COVERAGE=1 pnpm build` (see build.mjs); never part of a normal build. Sends the statement/function/branch
   hit counts that istanbul collected in window.__coverage__ to the TEST_MODE-only /test-coverage/js endpoint, so a test run's coverage survives
   the browser closing. Only counts that are not zero are sent. */
(function () {
  if (window.__coverageReporter) return;
  window.__coverageReporter = true;
  var id = Math.random().toString(36).slice(2) + Date.now().toString(36);
  var last = '';
  function hits() {
    var out = {}, all = window.__coverage__ || {};
    Object.keys(all).forEach(function (file) {
      var c = all[file], entry = {s: {}, f: {}, b: {}}, any = false;
      Object.keys(c.s).forEach(function (k) { if (c.s[k]) { entry.s[k] = c.s[k]; any = true; } });
      Object.keys(c.f).forEach(function (k) { if (c.f[k]) { entry.f[k] = c.f[k]; any = true; } });
      Object.keys(c.b).forEach(function (k) { if (c.b[k].some(Boolean)) { entry.b[k] = c.b[k]; any = true; } });
      if (any) out[file] = entry;
    });
    return JSON.stringify(out);
  }
  function send() {
    try {
      var body = hits();
      if (body === last) return;
      last = body;
      navigator.sendBeacon('/test-coverage/js?id=' + id, body);
    } catch (e) { /* coverage is best effort */ }
  }
  window.addEventListener('pagehide', send);
  document.addEventListener('visibilitychange', function () { if (document.visibilityState === 'hidden') send(); });
  setInterval(send, 1000);
})();
