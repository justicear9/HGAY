/**
 * Events calendar — loads from api/events_list
 */
(function () {
  const calendarEl = document.getElementById('ev-calendar');
  const statusEl = document.getElementById('ev-status');
  const tabs = document.querySelectorAll('.ev-tab');
  if (!calendarEl) return;

  let allEvents = [];
  let filter = 'upcoming';

  const monthNames = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December',
  ];
  const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

  function escapeHtml(str) {
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
  }

  function todayYmd() {
    const t = new Date();
    const y = t.getFullYear();
    const m = String(t.getMonth() + 1).padStart(2, '0');
    const d = String(t.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
  }

  function filterEvents(list) {
    const today = todayYmd();
    if (filter === 'upcoming') {
      return list.filter((e) => e.event_date >= today);
    }
    if (filter === 'past') {
      return list.filter((e) => e.event_date < today);
    }
    return list.slice();
  }

  function formatWhen(ev) {
    const [y, m, d] = ev.event_date.split('-').map(Number);
    const date = new Date(y, m - 1, d);
    let text = dayNames[date.getDay()] + ', ' + monthNames[m - 1] + ' ' + d + ', ' + y;
    if (ev.event_time) {
      const [hh, mm] = ev.event_time.split(':');
      const hour = parseInt(hh, 10);
      const ampm = hour >= 12 ? 'PM' : 'AM';
      const h12 = hour % 12 || 12;
      text += ' · ' + h12 + ':' + mm + ' ' + ampm;
    }
    return text;
  }

  function monthKey(eventDate) {
    const [y, m] = eventDate.split('-');
    return y + '-' + m;
  }

  function monthLabel(key) {
    const [y, m] = key.split('-').map(Number);
    return monthNames[m - 1] + ' ' + y;
  }

  function locationIcon() {
    return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>';
  }

  function renderCard(ev, isPast) {
    const [, m, d] = ev.event_date.split('-').map(Number);
    const monthShort = monthNames[m - 1].slice(0, 3);
    const reg =
      ev.registration_url && ev.registration_url.trim()
        ? `<a class="ev-register" href="${escapeHtml(ev.registration_url)}" target="_blank" rel="noopener noreferrer">${escapeHtml(ev.registration_label || 'Register')}</a>`
        : '';
    const price =
      ev.price_display && ev.price_display.trim()
        ? `<span class="ev-price">${escapeHtml(ev.price_display)}</span>`
        : '<span></span>';

    return `
      <article class="ev-card${isPast ? ' is-past' : ''}">
        <div class="ev-card-date">
          <div class="ev-date-badge" aria-hidden="true">
            <span class="day">${d}</span>
            <span class="month">${monthShort}</span>
          </div>
          <div class="ev-card-meta">
            <h2>${escapeHtml(ev.title)}</h2>
            <p class="ev-card-when">${escapeHtml(formatWhen(ev))}</p>
          </div>
        </div>
        <p class="ev-location">${locationIcon()}<span>${escapeHtml(ev.location)}</span></p>
        <p class="ev-description">${escapeHtml(ev.description)}</p>
        <div class="ev-card-footer">
          ${price}
          ${reg}
        </div>
      </article>
    `;
  }

  function render() {
    const today = todayYmd();
    let list = filterEvents(allEvents);
    if (filter === 'upcoming') {
      list.sort((a, b) => a.event_date.localeCompare(b.event_date) || (a.event_time || '').localeCompare(b.event_time || ''));
    } else if (filter === 'past') {
      list.sort((a, b) => b.event_date.localeCompare(a.event_date) || (b.event_time || '').localeCompare(a.event_time || ''));
    } else {
      list.sort((a, b) => b.event_date.localeCompare(a.event_date));
    }

    if (list.length === 0) {
      const msg =
        filter === 'upcoming'
          ? 'No upcoming events right now. Check back soon!'
          : filter === 'past'
            ? 'No past events to show.'
            : 'No events published yet.';
      calendarEl.innerHTML = `<p class="ev-empty">${msg}</p>`;
      if (statusEl) statusEl.textContent = '';
      return;
    }

    const groups = new Map();
    list.forEach((ev) => {
      const key = monthKey(ev.event_date);
      if (!groups.has(key)) groups.set(key, []);
      groups.get(key).push(ev);
    });

    const keys = Array.from(groups.keys()).sort((a, b) =>
      filter === 'past' || filter === 'all' ? b.localeCompare(a) : a.localeCompare(b)
    );

    calendarEl.innerHTML = keys
      .map((key) => {
        const cards = groups
          .get(key)
          .map((ev) => renderCard(ev, ev.event_date < today))
          .join('');
        return `<section class="ev-month"><h2 class="ev-month-title">${escapeHtml(monthLabel(key))}</h2><div class="ev-grid">${cards}</div></section>`;
      })
      .join('');

    if (statusEl) {
      statusEl.textContent =
        list.length === 1 ? '1 event' : list.length + ' events';
    }
  }

  tabs.forEach((tab) => {
    tab.addEventListener('click', () => {
      filter = tab.getAttribute('data-filter') || 'upcoming';
      tabs.forEach((t) => {
        const active = t === tab;
        t.classList.toggle('is-active', active);
        t.setAttribute('aria-selected', active ? 'true' : 'false');
      });
      render();
    });
  });

  async function load() {
    if (statusEl) statusEl.textContent = 'Loading events…';
    try {
      const res = await fetch('api/events_list');
      const data = await res.json();
      if (!res.ok || data.error) throw new Error('load failed');
      allEvents = Array.isArray(data.events) ? data.events : [];
      render();
    } catch {
      calendarEl.innerHTML =
        '<p class="ev-empty">Could not load events. Please refresh the page.</p>';
      if (statusEl) statusEl.textContent = '';
    }
  }

  load();
})();
