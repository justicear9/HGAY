/**
 * How Ghanaian Are You — Landing Page
 * Theme toggle, scroll animations, nav, form
 */

document.addEventListener('DOMContentLoaded', () => {
  initNav();
  initScrollAnimations();
  initPreorderForm();
  initSmoothScroll();
});

/** Nav scroll effect & mobile menu */
function initNav() {
  const nav = document.querySelector('.nav');
  const mobileToggle = document.querySelector('.nav-mobile-toggle');
  const navLinks = document.querySelector('.nav-links');
  const navOverlay = document.getElementById('nav-overlay');

  if (nav) {
    const handleScroll = () => {
      nav.classList.toggle('scrolled', window.scrollY > 60);
    };
    window.addEventListener('scroll', handleScroll, { passive: true });
  }

  function closeMobileMenu() {
    if (navLinks) navLinks.classList.remove('open');
    if (mobileToggle) mobileToggle.classList.remove('active');
    if (mobileToggle) mobileToggle.setAttribute('aria-expanded', 'false');
    if (navOverlay) navOverlay.classList.remove('open');
    if (nav) nav.classList.remove('nav-menu-open');
    navOverlay?.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  }

  function openMobileMenu() {
    if (navLinks) navLinks.classList.add('open');
    if (mobileToggle) mobileToggle.classList.add('active');
    if (mobileToggle) mobileToggle.setAttribute('aria-expanded', 'true');
    if (navOverlay) navOverlay.classList.add('open');
    if (nav) nav.classList.add('nav-menu-open');
    navOverlay?.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  }

  function toggleMobileMenu(e) {
    e.preventDefault();
    e.stopPropagation();
    const isOpen = navLinks?.classList.contains('open');
    if (isOpen) {
      closeMobileMenu();
    } else {
      openMobileMenu();
    }
  }

  if (mobileToggle) {
    mobileToggle.setAttribute('aria-expanded', 'false');
    mobileToggle.setAttribute('aria-controls', 'nav-links');
    mobileToggle.addEventListener('click', toggleMobileMenu);
  }

  if (navOverlay) {
    navOverlay.addEventListener('click', closeMobileMenu);
  }

  navLinks?.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', closeMobileMenu);
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && navLinks?.classList.contains('open')) {
      closeMobileMenu();
    }
  });
}

/** Scroll-triggered animations */
function initScrollAnimations() {
  const sections = document.querySelectorAll('.section');
  const options = {
    root: null,
    rootMargin: '0px 0px -80px 0px',
    threshold: 0.1,
  };

  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
      }
    });
  }, options);

  sections.forEach((section) => observer.observe(section));
}

/** Paystack public key (safe to expose). Secret key is server-side only in paystack_config.php */
//const PAYSTACK_PUBLIC_KEY = 'pk_live_500785115d70068dca0116c234513c6944de3ba1';

//TEST KEY
const PAYSTACK_PUBLIC_KEY = 'pk_test_df46ad3b11334755c7ae647ba76186fda3af8620';

/** Preorder multi-step form: countries, steps, create_order, then Paystack. Delivery country = phone country. */
function initPreorderForm() {
  const form = document.getElementById('preorder-form');
  if (!form) return;

  const step1 = form.querySelector('[data-step="1"]');
  const step2 = form.querySelector('[data-step="2"]');
  const formError = document.getElementById('preorder-form-error');
  const deliveryCountryHidden = document.getElementById('delivery_country');
  const postcodeGroup = document.getElementById('postcode-group');
  const regionInput = document.getElementById('delivery_region_input');
  const regionListEl = document.getElementById('delivery_region_list');
  const quantityInput = document.getElementById('quantity');
  const priceTotalEl = document.getElementById('preorder-price-total');
  const qtyValueEl = document.getElementById('preorder-qty-value');
  const cardsStackEl = document.querySelector('.preorder-cards-stack');
  const qtyMinusBtn = document.getElementById('qty-minus');
  const qtyPlusBtn = document.getElementById('qty-plus');
  const phoneNumberInput = document.getElementById('phone_number');
  const phoneHintEl = document.getElementById('phone-hint');

  let amountPerGame = parseFloat(form.getAttribute('data-amount-ghs')) || 100;
  const pricePerEl = document.querySelector('.preorder-price-per');

  fetch('api/get_settings.php')
    .then((r) => r.json())
    .then((s) => {
      if (s.price_ghs) {
        amountPerGame = parseFloat(s.price_ghs);
        if (pricePerEl) pricePerEl.textContent = amountPerGame.toFixed(0) + ' GHS per game';
        updateQuantity(currentQty);
      }
    })
    .catch(() => {});

  let countryPostcodeMap = {};
  let countryPhoneLengths = {}; // code -> { min_digits, max_digits }
  let regionOptions = [];
  let regionListBlurTimer = null;
  let currentQty = 1;

  function updateQuantity(qty) {
    qty = Math.max(1, Math.min(99, parseInt(qty, 10) || 1));
    currentQty = qty;
    if (quantityInput) quantityInput.value = String(qty);
    if (qtyValueEl) qtyValueEl.textContent = qty;
    const total = (amountPerGame * qty).toFixed(0);
    if (priceTotalEl) {
      priceTotalEl.innerHTML = total + ' <span class="preorder-currency">GHS</span>';
    }
    if (cardsStackEl) {
      cardsStackEl.innerHTML = '';
      for (let i = 0; i < qty; i++) {
        const box = document.createElement('img');
        box.className = 'preorder-card-box';
        box.src = 'HGAY ASSETS/Card Pictures and Video/box.png';
        box.alt = 'Game box';
        box.loading = 'lazy';
        cardsStackEl.appendChild(box);
      }
    }
  }

  function updatePhoneHint() {
    const code = form.phone_country?.value;
    const len = code ? countryPhoneLengths[code] : null;
    if (!phoneHintEl) return;
    if (len) {
      phoneHintEl.textContent = len.min_digits === len.max_digits
        ? len.min_digits + ' digits'
        : len.min_digits + '–' + len.max_digits + ' digits';
      if (phoneNumberInput) phoneNumberInput.setAttribute('maxlength', len.max_digits);
    } else {
      phoneHintEl.textContent = '';
      if (phoneNumberInput) phoneNumberInput.setAttribute('maxlength', '15');
    }
  }

  if (qtyMinusBtn) qtyMinusBtn.addEventListener('click', () => updateQuantity(currentQty - 1));
  if (qtyPlusBtn) qtyPlusBtn.addEventListener('click', () => updateQuantity(currentQty + 1));
  updateQuantity(1);

  function showError(msg) {
    if (formError) {
      formError.textContent = msg;
      formError.hidden = false;
    }
  }
  function clearError() {
    if (formError) formError.hidden = true;
  }

  function goToStep(step) {
    clearError();
    step1.hidden = step !== 1;
    step2.hidden = step !== 2;
    if (step === 2) {
      const phoneCountry = form.phone_country.value;
      deliveryCountryHidden.value = phoneCountry;
      const needsPostcode = countryPostcodeMap[phoneCountry];
      postcodeGroup.hidden = !needsPostcode;
      regionOptions = [];
      regionInput.value = '';
      regionListEl.innerHTML = '';
      regionListEl.hidden = true;
      if (phoneCountry) {
        fetch('api/get_regions.php?country=' + encodeURIComponent(phoneCountry))
          .then((r) => r.json())
          .then((list) => {
            regionOptions = Array.isArray(list) ? list : [];
            filterRegionList(regionInput.value);
          })
          .catch(() => { regionOptions = []; });
      }
    }
  }

  function filterRegionList(query) {
    const q = (query || '').trim().toLowerCase();
    const filtered = q
      ? regionOptions.filter((r) => r.toLowerCase().includes(q))
      : regionOptions.slice();
    regionListEl.innerHTML = '';
    filtered.forEach((name) => {
      const li = document.createElement('li');
      li.textContent = name;
      li.role = 'option';
      li.tabIndex = -1;
      li.addEventListener('click', () => {
        regionInput.value = name;
        regionListEl.hidden = true;
        regionInput.setAttribute('aria-expanded', 'false');
        if (regionListBlurTimer) clearTimeout(regionListBlurTimer);
      });
      regionListEl.appendChild(li);
    });
    regionListEl.hidden = filtered.length === 0;
    if (filtered.length > 0) regionInput.setAttribute('aria-expanded', 'true');
  }

  function showRegionList() {
    filterRegionList(regionInput.value);
  }

  if (regionInput && regionListEl) {
    regionInput.addEventListener('focus', showRegionList);
    regionInput.addEventListener('input', () => filterRegionList(regionInput.value));
    regionInput.addEventListener('blur', () => {
      regionListBlurTimer = setTimeout(() => {
        regionListEl.hidden = true;
        regionInput.setAttribute('aria-expanded', 'false');
      }, 150);
    });
    regionListEl.addEventListener('mousedown', (e) => e.preventDefault());
  }

  fetch('api/get_countries.php')
    .then((r) => r.json())
    .then((data) => {
      const phoneSelect = document.getElementById('phone_country');
      (data.phone_countries || []).forEach((c) => {
        const o = document.createElement('option');
        o.value = c.code;
        o.textContent = c.name + ' ' + c.dial;
        phoneSelect.appendChild(o);
        if (c.min_digits != null && c.max_digits != null) {
          countryPhoneLengths[c.code] = { min_digits: c.min_digits, max_digits: c.max_digits };
        }
      });
      (data.delivery_countries || []).forEach((c) => {
        countryPostcodeMap[c.code] = c.has_postcode;
      });
      updatePhoneHint();
    })
    .catch(() => showError('Could not load countries. Please refresh.'));

  form.phone_country?.addEventListener('change', updatePhoneHint);

  form.querySelectorAll('.preorder-next, .preorder-back').forEach((btn) => {
    btn.addEventListener('click', () => {
      const step = parseInt(btn.getAttribute('data-goto'), 10);
      if (step === 2) {
        if (!step1.querySelector('#name').value.trim() || !step1.querySelector('#email').value.trim() || !form.phone_country.value || !phoneNumberInput.value.trim()) {
          showError('Please fill in name, email, and phone.');
          return;
        }
        const digits = (phoneNumberInput.value.match(/\d/g) || []).length;
        const len = countryPhoneLengths[form.phone_country.value];
        if (len && (digits < len.min_digits || digits > len.max_digits)) {
          showError('Phone number should be ' + len.min_digits + (len.min_digits === len.max_digits ? '' : '–' + len.max_digits) + ' digits for this country.');
          return;
        }
        const qty = parseInt(quantityInput?.value || form.quantity?.value, 10);
        if (isNaN(qty) || qty < 1 || qty > 99) {
          showError('Quantity must be between 1 and 99.');
          return;
        }
      }
      goToStep(step);
    });
  });

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    clearError();

    if (!deliveryCountryHidden.value || !form.delivery_region.value.trim() || !form.delivery_address.value.trim()) {
      showError('Please fill in state/region and address.');
      return;
    }

    const btn = form.querySelector('#preorder-submit');
    const originalText = btn.textContent;
    btn.textContent = 'Creating order…';
    btn.disabled = true;

    const formData = new FormData(form);
    formData.append('quantity', form.quantity.value);

    fetch('create_order.php', {
      method: 'POST',
      body: formData,
      credentials: 'same-origin',
    })
      .then((r) => r.json())
      .then((data) => {
        if (!data.success) {
          btn.textContent = originalText;
          btn.disabled = false;
          showError(data.errors ? data.errors.join(' ') : data.error || 'Something went wrong.');
          return;
        }
        btn.textContent = 'Opening payment…';
        const ref = 'HGAY-' + data.order_id + '-' + Math.random().toString(36).replace(/[^a-z0-9]/gi, '').slice(0, 8);
        const handler = PaystackPop.setup({
          key: PAYSTACK_PUBLIC_KEY,
          email: data.email,
          amount: data.amount_pesewas,
          currency: 'GHS',
          ref: ref,
          metadata: {
            custom_fields: [
              { display_name: 'Order', variable_name: 'order_id', value: String(data.order_id) },
            ],
          },
          callback: function (response) {
            window.location.href = 'verify.php?reference=' + encodeURIComponent(response.reference);
          },
          onClose: function () {
            btn.textContent = originalText;
            btn.disabled = false;
          },
        });
        handler.openIframe();
      })
      .catch(() => {
        btn.textContent = originalText;
        btn.disabled = false;
        showError('Network error. Please try again.');
      });
  });
}

/** Smooth scroll for anchor links */
function initSmoothScroll() {
  document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
    anchor.addEventListener('click', function (e) {
      const href = this.getAttribute('href');
      if (href === '#') return;

      const target = document.querySelector(href);
      if (target) {
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });
}
