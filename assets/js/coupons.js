(function ($) {
  'use strict';
  const opened = new Set();
  $(document).on('click.thaimoonCoupon', '[data-coupon-apply]', function (event) {
    const native = document.querySelector('[data-coupon-native-submit]');
    if (!native || typeof wc_cart_params === 'undefined') return;
    event.preventDefault();
    if (native.form && native.form.classList.contains('processing')) return;
    native.click();
  });
  $(document).on('keydown.thaimoonCoupon', '#coupon_code', function (event) {
    if (event.key === 'Enter' && !event.isComposing) {
      const button = document.querySelector('[data-coupon-apply]');
      if (button) { event.preventDefault(); button.click(); }
    }
  });
  function enhanceRemoval() {
    document.querySelectorAll('[data-coupon-removal]').forEach(function (notice) {
      if (notice.dataset.couponEnhanced === 'yes') return;
      const id = notice.dataset.couponRemoval;
      let seen = opened.has(id);
      try { seen = seen || sessionStorage.getItem('thaimoon-coupon-' + id) === 'seen'; } catch (error) {}
      if (seen) { notice.remove(); return; }
      opened.add(id);
      notice.dataset.couponEnhanced = 'yes';
      try { sessionStorage.setItem('thaimoon-coupon-' + id, 'seen'); } catch (error) {}
      const close = notice.querySelector('[data-coupon-removal-close]');
      close.hidden = false;
      if (typeof HTMLDialogElement === 'undefined' || typeof HTMLDialogElement.prototype.showModal !== 'function') {
        close.addEventListener('click', function () { notice.remove(); });
        return;
      }
      const dialog = document.createElement('dialog');
      dialog.className = 'coupon-removal__dialog';
      dialog.setAttribute('aria-label', 'Code promotionnel désactivé');
      notice.replaceWith(dialog);
      dialog.append(notice);
      close.addEventListener('click', function () { dialog.close(); });
      dialog.addEventListener('close', function () {
        dialog.remove();
        const input = document.querySelector('#coupon_code');
        const target = input || document.querySelector('.commerce-totals .woocommerce-remove-coupon') || document.querySelector('#order_review_heading');
        if (target) target.focus({ preventScroll: true });
      });
      dialog.showModal();
      notice.querySelector('.coupon-removal__message').focus({ preventScroll: true });
    });
  }
  $(document.body).on('updated_wc_div updated_cart_totals updated_checkout', enhanceRemoval);
  $(enhanceRemoval);
})(jQuery);
