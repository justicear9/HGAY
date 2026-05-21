/**
 * Gallery page + home preview — loads from api/gallery_list
 */
(function () {
  function escapeHtml(str) {
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
  }

  /** Encode each path segment for URLs (spaces, unicode filenames). */
  function assetUrl(relativePath) {
    if (!relativePath) return '';
    return relativePath.split('/').map((part) => encodeURIComponent(part)).join('/');
  }

  function layoutClass(layout, mediaType) {
    if (mediaType === 'video' || layout === 'video') return 'gallery-full-item gallery-full-item-video';
    if (layout === 'wide') return 'gallery-full-item gallery-full-item-wide';
    return 'gallery-full-item';
  }

  function renderItem(item, previewMode) {
    const path = escapeHtml(assetUrl(item.file_path));
    const alt = escapeHtml(item.alt_text || '');
    const poster = item.poster_path ? escapeHtml(assetUrl(item.poster_path)) : '';

    if (previewMode) {
      return `<div class="gallery-item"><img src="${path}" alt="${alt}" loading="lazy"></div>`;
    }

    const cls = layoutClass(item.layout, item.media_type);
    if (item.media_type === 'video') {
      const label = item.caption ? escapeHtml(item.caption) : alt;
      const posterAttr = poster ? ` poster="${poster}"` : '';
      return `<div class="${cls}"><video src="${path}" controls playsinline${posterAttr}></video><span class="video-label">${label}</span></div>`;
    }
    return `<div class="${cls}"><img src="${path}" alt="${alt}" loading="lazy"></div>`;
  }

  async function fetchItems(options) {
    const params = new URLSearchParams();
    if (options.imagesOnly) params.set('images_only', '1');
    if (options.limit) params.set('limit', String(options.limit));
    const qs = params.toString();
    const url = 'api/gallery_list' + (qs ? '?' + qs : '');
    try {
      const res = await fetch(url);
      const data = await res.json();
      if (!res.ok || data.error) throw new Error('api');
      if (Array.isArray(data.items) && data.items.length > 0) return data.items;
    } catch {
      /* fallback */
    }
    let items = window.HGAY_GALLERY_ITEMS || [];
    if (options.imagesOnly) items = items.filter((i) => i.media_type === 'image');
    if (options.limit) items = items.slice(0, options.limit);
    return items;
  }

  async function initFullGallery() {
    const grid = document.getElementById('gallery-full-grid');
    if (!grid) return;
    grid.innerHTML = '<p class="gallery-loading" style="grid-column:1/-1;text-align:center;color:var(--text-muted)">Loading gallery…</p>';
    const items = await fetchItems({});
    if (!items.length) {
      grid.innerHTML = '<p class="gallery-empty" style="grid-column:1/-1;text-align:center;color:var(--text-muted)">No gallery items yet.</p>';
      return;
    }
    grid.innerHTML = items.map((item) => renderItem(item, false)).join('');
  }

  async function initPreview() {
    const grid = document.getElementById('gallery-preview-grid');
    if (!grid) return;
    const items = await fetchItems({ imagesOnly: true, limit: 4 });
    if (!items.length) return;
    grid.innerHTML = items.map((item) => renderItem(item, true)).join('');
  }

  initFullGallery();
  initPreview();
})();
