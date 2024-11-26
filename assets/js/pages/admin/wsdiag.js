(function() {
  'use strict';

  const { WSS } = window.reactComponents;
  const wssRoot = ReactDOM.createRoot(document.getElementById('wsdiag'));
  wssRoot.render(<WSS responseTimeHistorySize={6} />);
})();
