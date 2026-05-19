/**
 * Fact Check — flip Q&A cards; card references shown as glossary below.
 */
(function () {
  const searchInput = document.getElementById('fc-search');
  const categorySelect = document.getElementById('fc-category');
  const resultsEl = document.getElementById('fc-results');
  const referencesEl = document.getElementById('fc-references');
  const countEl = document.getElementById('fc-count');
  if (!searchInput || !resultsEl) return;

  const CATEGORY_LABELS = {
    culture_history: 'Culture & History',
    general_knowledge: 'General Knowledge',
    movies: 'Movies',
    places: 'Places',
    riddles: 'Riddles',
    sports: 'Sports',
  };

  let factCards = [];
  let references = [];
  let debounceTimer;

  function escapeHtml(s) {
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
  }

  function normalizeFact(c) {
    const category = c.category || '';
    return {
      id: c.id,
      category,
      categoryLabel: c.categoryLabel || c.category_label || CATEGORY_LABELS[category] || category,
      question: c.question || '',
      answer: c.answer || '',
      keywords: c.keywords || '',
    };
  }

  function normalizeRef(r) {
    return {
      id: r.id,
      term: r.term || r.question || '',
      definition: r.definition || r.answer || '',
      keywords: r.keywords || '',
    };
  }

  function populateCategoryFilter() {
    const cats = {};
    factCards.forEach((c) => {
      cats[c.category] = c.categoryLabel;
    });
    categorySelect.innerHTML = '<option value="">All categories</option>';
    Object.entries(cats).forEach(([value, label]) => {
      const opt = document.createElement('option');
      opt.value = value;
      opt.textContent = label;
      categorySelect.appendChild(opt);
    });
  }

  function normalizeQuery(s) {
    return (s || '').toLowerCase();
  }

  function filterFacts() {
    const q = normalizeQuery(searchInput.value.trim());
    const cat = categorySelect.value;
    return factCards.filter((c) => {
      if (cat && c.category !== cat) return false;
      if (!q) return true;
      const hay = normalizeQuery([c.question, c.answer, c.keywords, c.categoryLabel].join(' '));
      return hay.includes(q);
    });
  }

  function filterReferences() {
    const q = normalizeQuery(searchInput.value.trim());
    const cat = categorySelect.value;
    if (cat) return [];
    if (!q) return references;
    return references.filter((r) => {
      const hay = normalizeQuery([r.term, r.definition, r.keywords].join(' '));
      return hay.includes(q);
    });
  }

  function renderFlipCard(c) {
    const ariaQ = escapeHtml(c.question).replace(/"/g, '&quot;');
    return `
      <div class="fc-card-wrap">
        <p class="fc-card-category">${escapeHtml(c.categoryLabel)}</p>
        <button type="button" class="fc-flip" aria-expanded="false" aria-label="Reveal answer for ${ariaQ}">
          <span class="fc-flip-hint" aria-hidden="true">Tap to reveal answer</span>
          <span class="fc-flip-inner">
            <span class="fc-flip-face fc-flip-front">
              <span class="fc-label">Card</span>
              <span class="fc-flip-text">${escapeHtml(c.question)}</span>
            </span>
            <span class="fc-flip-face fc-flip-back">
              <span class="fc-label">Answer</span>
              <span class="fc-flip-text">${escapeHtml(c.answer)}</span>
            </span>
          </span>
        </button>
      </div>`;
  }

  function renderGrouped(list) {
    let html = '';
    let lastCat = null;
    list.forEach((c) => {
      if (c.category !== lastCat) {
        lastCat = c.category;
        html += `<h2 class="fc-section-title">${escapeHtml(c.categoryLabel)}</h2>`;
      }
      html += renderFlipCard(c);
    });
    return html;
  }

  function renderReferences(list) {
    if (!referencesEl) return;
    if (!list.length) {
      referencesEl.hidden = true;
      referencesEl.innerHTML = '';
      return;
    }
    referencesEl.hidden = false;
    referencesEl.innerHTML = `
      <header class="fc-references-header">
        <h2 class="fc-references-title">Card references</h2>
        <p class="fc-references-lead">What terms on the game cards mean.</p>
      </header>
      <dl class="fc-ref-list">
        ${list
          .map(
            (r) => `
          <div class="fc-ref-item">
            <dt>${escapeHtml(r.term)}</dt>
            <dd>${escapeHtml(r.definition)}</dd>
          </div>`
          )
          .join('')}
      </dl>`;
  }

  function render() {
    const facts = filterFacts();
    const refs = filterReferences();
    const q = searchInput.value.trim();
    const cat = categorySelect.value;

    if (countEl) {
      countEl.textContent = facts.length
        ? facts.length + (facts.length === 1 ? ' question' : ' questions') + ' — tap to flip'
        : q || cat
          ? 'No matching questions'
          : factCards.length
            ? factCards.length + ' questions — tap to reveal answers'
            : '';
    }

    if (facts.length === 0) {
      resultsEl.innerHTML = q || cat
        ? '<div class="fc-empty"><strong>No questions found</strong>Try another keyword or category. Card references may still appear below.</div>'
        : '<div class="fc-empty"><strong>No questions yet</strong>Add Fact Check cards in admin.</div>';
    } else {
      const showSections = !q && !cat;
      resultsEl.innerHTML = showSections ? renderGrouped(facts) : facts.map(renderFlipCard).join('');
    }

    renderReferences(refs);
  }

  function onFlipClick(e) {
    const btn = e.target.closest('.fc-flip');
    if (!btn || !resultsEl.contains(btn)) return;
    const flipped = btn.classList.toggle('is-flipped');
    btn.setAttribute('aria-expanded', flipped ? 'true' : 'false');
    const hint = btn.querySelector('.fc-flip-hint');
    if (hint) hint.textContent = flipped ? 'Tap to hide' : 'Tap to reveal answer';
  }

  function onFlipKeydown(e) {
    if (e.key !== 'Enter' && e.key !== ' ') return;
    const btn = e.target.closest('.fc-flip');
    if (!btn) return;
    e.preventDefault();
    btn.click();
  }

  resultsEl.addEventListener('click', onFlipClick);
  resultsEl.addEventListener('keydown', onFlipKeydown);
  searchInput.addEventListener('input', () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(render, 200);
  });
  categorySelect.addEventListener('change', render);

  async function loadCards() {
    resultsEl.innerHTML = '<div class="fc-loading">Loading…</div>';
    if (referencesEl) referencesEl.hidden = true;

    try {
      const res = await fetch('api/fact_check_list');
      const data = await res.json();
      if (res.ok && (data.facts?.length || data.references?.length)) {
        factCards = (data.facts || []).map(normalizeFact);
        references = (data.references || []).map(normalizeRef);
        populateCategoryFilter();
        render();
        return;
      }
    } catch (_) {
      /* static fallback */
    }

    const staticFacts = (window.HGAY_FACT_CARDS || []).map(normalizeFact);
    const staticRefs = (window.HGAY_CARD_REFERENCES || []).map(normalizeRef);
    if (staticFacts.length || staticRefs.length) {
      factCards = staticFacts;
      references = staticRefs;
      populateCategoryFilter();
      render();
      return;
    }

    resultsEl.innerHTML =
      '<div class="fc-empty"><strong>No content available</strong>Add cards in admin or seed the guide.</div>';
  }

  loadCards();
})();
