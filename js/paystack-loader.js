/**
 * Load Paystack inline.js only when checkout is needed.
 */
window.loadPaystack = function loadPaystack() {
  if (window.PaystackPop) {
    return Promise.resolve(window.PaystackPop);
  }
  if (window.__paystackLoadPromise) {
    return window.__paystackLoadPromise;
  }
  window.__paystackLoadPromise = new Promise(function (resolve, reject) {
    var s = document.createElement('script');
    s.src = 'https://js.paystack.co/v1/inline.js';
    s.async = true;
    s.onload = function () {
      resolve(window.PaystackPop);
    };
    s.onerror = function () {
      reject(new Error('Paystack failed to load'));
    };
    document.head.appendChild(s);
  });
  return window.__paystackLoadPromise;
};
