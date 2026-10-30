/* Matomo, mod fără cookie-uri, pornit DOAR după consimțământul pentru analytics.
   După instalarea Matomo, completează MATOMO_URL (cu / la final) și MATOMO_SITE_ID. */
(function () {
  var MATOMO_URL = '';            // ex.: 'https://www.les-romania.org/analytics/'
  var MATOMO_SITE_ID = 1;
  var on = false, q = window._paq = window._paq || [];
  function allowed(c) { return !!c && c.analytics === true; }
  function start() {
    if (on || !MATOMO_URL) return; on = true;
    q.push(['disableCookies'], ['setDoNotTrack', true], ['trackPageView'], ['enableLinkTracking'],
           ['setTrackerUrl', MATOMO_URL + 'matomo.php'], ['setSiteId', String(MATOMO_SITE_ID)]);
    var s = document.createElement('script'); s.async = true; s.src = MATOMO_URL + 'matomo.js'; document.head.appendChild(s);
  }
  document.addEventListener('les:consent', function (e) { if (allowed(e.detail)) start(); else on = false; });
  document.addEventListener('les:event', function (e) { if (on) q.push(['trackEvent'].concat(e.detail)); });
  if (allowed(window.lesConsent)) start();
})();
