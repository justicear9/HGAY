/**
 * Collapsible admin sidebar
 */
(function () {
  var app = document.getElementById('admin-app');
  var sidebar = document.getElementById('admin-sidebar');
  var toggle = document.getElementById('admin-sidebar-toggle');
  var mobileMenu = document.getElementById('admin-mobile-menu');
  var backdrop = document.getElementById('admin-sidebar-backdrop');
  if (!app || !sidebar) return;

  var storageKey = 'hgay-admin-sidebar-collapsed';
  var labelEl = toggle ? toggle.querySelector('.admin-sidebar-toggle-label') : null;

  function setCollapsed(collapsed, persist) {
    app.classList.toggle('sidebar-collapsed', collapsed);
    if (toggle) toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
    if (labelEl) labelEl.textContent = collapsed ? 'Expand' : 'Collapse';
    if (persist) {
      try {
        localStorage.setItem(storageKey, collapsed ? '1' : '0');
      } catch (_) {}
    }
  }

  function isMobile() {
    return window.matchMedia('(max-width: 900px)').matches;
  }

  try {
    if (localStorage.getItem(storageKey) === '1' && !isMobile()) {
      setCollapsed(true, false);
    }
  } catch (_) {}

  function toggleMobile() {
    var open = app.classList.toggle('sidebar-mobile-open');
    if (backdrop) backdrop.hidden = !open;
  }

  if (toggle) {
    toggle.addEventListener('click', function () {
      if (isMobile()) {
        toggleMobile();
        return;
      }
      setCollapsed(!app.classList.contains('sidebar-collapsed'), true);
    });
  }

  if (mobileMenu) {
    mobileMenu.addEventListener('click', toggleMobile);
  }

  if (backdrop) {
    backdrop.addEventListener('click', function () {
      app.classList.remove('sidebar-mobile-open');
      backdrop.hidden = true;
    });
  }

  window.addEventListener('resize', function () {
    if (!isMobile()) {
      app.classList.remove('sidebar-mobile-open');
      if (backdrop) backdrop.hidden = true;
    }
  });
})();
