<!-- Google tag (gtag.js) — loaded after page load to avoid blocking LCP -->
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  function loadGtag() {
    var s = document.createElement('script');
    s.async = true;
    s.src = 'https://www.googletagmanager.com/gtag/js?id=G-CFRH43PWP6';
    s.onload = function () {
      gtag('js', new Date());
      gtag('config', 'G-CFRH43PWP6');
    };
    document.head.appendChild(s);
  }
  window.addEventListener('load', function () {
    if ('requestIdleCallback' in window) {
      requestIdleCallback(loadGtag, { timeout: 4000 });
    } else {
      setTimeout(loadGtag, 2000);
    }
  });
</script>
