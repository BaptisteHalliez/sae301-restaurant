(function ($) {
  'use strict';
  let updateTimer;
  function syncQuantity(wrapper) {
    const input = wrapper.querySelector('input.qty');
    if (!input) return;
    const value = Number(input.value);
    const min = input.min === '' ? 0 : Number(input.min);
    const max = input.max === '' ? Infinity : Number(input.max);
    wrapper.querySelectorAll('[data-cart-step]').forEach(function (button) {
      button.hidden = false;
      button.disabled = input.disabled || input.readOnly || (Number(button.dataset.cartStep) > 0 ? value >= max : value <= min);
    });
  }
  function sync() {
    document.querySelectorAll('.cart-quantity').forEach(syncQuantity);
    const cards = Array.from(document.querySelectorAll('.commerce-checkout [data-commerce-pickup]'));
    const pickup = cards.length > 0 && cards.every(card => card.dataset.commercePickup === 'yes');
    document.querySelectorAll('[data-commerce-instructions-title]').forEach(function (title) {
      title.textContent = pickup ? 'Instructions pour le retrait' : 'Instructions pour la livraison';
    });
    document.querySelectorAll('[data-commerce-instructions]').forEach(function (paragraph) {
      paragraph.hidden = (paragraph.dataset.commerceInstructions === 'pickup') !== pickup;
    });
  }
  $(document).on('click.thaimoonCart', '[data-cart-step]', function () {
    const wrapper = this.closest('.cart-quantity');
    const input = wrapper && wrapper.querySelector('input.qty');
    if (!input || input.disabled || input.readOnly) return;
    if (input.value === '' || !Number.isFinite(Number(input.value))) input.value = input.min || '0';
    try { input.stepUp(Number(this.dataset.cartStep)); } catch (error) { return; }
    $(input).trigger('input').trigger('change');
  });
  $(document).on('input.thaimoonCart change.thaimoonCart', '.woocommerce-cart-form input.qty', function () {
    const wrapper = this.closest('.cart-quantity');
    if (wrapper) syncQuantity(wrapper);
    clearTimeout(updateTimer);
    updateTimer = setTimeout(function () {
      const button = document.querySelector('.woocommerce-cart-form [name="update_cart"]');
      if (button && !button.disabled) button.click();
    }, 550);
  });
  $(document.body).on('updated_wc_div updated_cart_totals updated_checkout', sync);
  $(sync);
})(jQuery);
