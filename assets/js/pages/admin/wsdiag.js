(function() {
  'use strict';

  const wssRoot = ReactDOM.createRoot(document.getElementById('wsdiag'));
  wssRoot.render(<window.reactComponents.WSS responseTimeHistorySize={6} />);
})();
