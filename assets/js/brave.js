(() => {

  const isBrave = 'brave' in navigator && typeof navigator.brave.isBrave === 'function';
  if (!isBrave) return;

  const localStorageKey = 'brave_ack';
  const dismissed = localStorage.getItem(localStorageKey);
  if (dismissed !== null) return;

  const domain = window.location.hostname;

  $.Dialog.info(
    'Attention Brave users',
    `<p>Please be aware that despite what the status says in your browser, Brave Support is holding the verification of this domain hostage by suspending the account I originally used to verify the site, citing "violation of the Brave terms of service".</p>
    <p>Their system is refusing to allow me to re-verify the site on the one remaining account that I still have access to, yet continue to display the site as verified, despite the account behind it being suspended since May 8th, 2020.</p>
    <p class="align-center">
      <a href="/img/brave_verified.png" target="_blank">
        <img src="/img/brave_verified.png" style="width:190px; height: 260px" alt="Screenshot of the Brave Rewards browser popup saying the ${domain} domain is a Brave Verified Creator">
      </a>
    </p>
    <p>Any and all contributions to this site are either getting lost in the ether or landing directly in Brave's figurative pockets, with me having no control over their decision.</p>
    <p>Do <strong>NOT</strong> send any tips or monthly contributions to the <strong>${domain}</strong> domain until further notice, no matter how much you want to support the group or the site.</p>
    <p class="align-center"><em>This message will not be shown again after you click Close.</em></p>`,
    () => {
      $('#dialogButtons').find('.close-button').on('click', () => {
        localStorage.setItem(localStorageKey, 'true');
        const halfAYearFromNow = new Date(Date.now() + (60e3 * 60 * 24 * 183)).toGMTString();
        document.cookie = `${localStorageKey}=true; expires=${halfAYearFromNow}`;
      });
    }
  )

})();
