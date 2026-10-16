(function ($) {
  'use strict';

  const forms = '.restaurant.single-product .summary form.cart';

  function syncQuantity(wrapper) {
    const input = wrapper.querySelector('input.qty');
    if (!input) return;
    const value = Number(input.value);
    const minimum = input.min === '' ? 1 : Number(input.min);
    const maximum = input.max === '' ? Infinity : Number(input.max);
    wrapper.querySelectorAll('[data-quantity-step]').forEach(function (button) {
      const increase = Number(button.dataset.quantityStep) > 0;
      button.hidden = false;
      button.disabled = input.disabled || input.readOnly || (increase ? value >= maximum : value <= minimum);
    });
    input.style.width = Math.max(13, String(input.value).length * 9) + 'px';
  }

  function syncFormQuantities(form) {
    form.querySelectorAll('.quantity-control').forEach(syncQuantity);
  }

  function setVariationState(form, variation, purchasable) {
    const button = form.querySelector('.single_add_to_cart_button');
    if (!button) return;
    const selected = Boolean(variation);
    const available = selected && Boolean(purchasable && variation.is_purchasable && variation.is_in_stock && variation.variation_is_visible);
    button.disabled = !available;
    button.setAttribute('aria-disabled', String(!available));
    button.textContent = !selected ? 'Choisir une option' : available ? 'Ajouter au panier' : 'Indisponible';
    button.classList.toggle('purchase__unavailable', selected && !available);
    form.querySelectorAll('.quantity').forEach(function (quantity) {
      quantity.hidden = !available || variation.is_sold_individually === 'yes';
    });
    const amount = form.querySelector('.purchase__amount');
    if (amount) {
      if (form.thaimoonOriginalPrice === undefined) form.thaimoonOriginalPrice = amount.innerHTML;
      // HTML issu exclusivement du prix WooCommerce, pas de calcul monétaire dans le navigateur.
      amount.innerHTML = selected ? variation.thaimoon_price_html || variation.price_html || form.thaimoonOriginalPrice : form.thaimoonOriginalPrice;
    }
    syncFormQuantities(form);
  }

  $(document).on('click.thaimoonPurchase', forms + ' [data-quantity-step]', function () {
    const wrapper = this.closest('.quantity-control');
    const input = wrapper && wrapper.querySelector('input.qty');
    if (!input || input.disabled || input.readOnly) return;
    const direction = Number(this.dataset.quantityStep);
    const minimum = input.min === '' ? 1 : Number(input.min);
    const maximum = input.max === '' ? Infinity : Number(input.max);
    if (!Number.isFinite(Number(input.value)) || input.value === '') input.value = String(minimum);
    try {
      input.stepUp(direction);
    } catch (error) {
      const step = Number(input.step) || 1;
      input.value = String(Math.max(minimum, Math.min(maximum, Number(input.value) + direction * step)));
    }
    $(input).trigger('input').trigger('change');
  });

  $(document).on('input.thaimoonPurchase change.thaimoonPurchase', forms + ' input.qty', function () {
    const wrapper = this.closest('.quantity-control');
    if (wrapper) syncQuantity(wrapper);
  });

  $(document).on('show_variation.thaimoonPurchase', '.summary .variations_form', function (event, variation, purchasable) {
    setVariationState(this, variation, purchasable);
  });

  $(document).on('reset_data.thaimoonPurchase hide_variation.thaimoonPurchase', '.summary .variations_form', function () {
    setVariationState(this, null, false);
  });

  $(document).on('submit.thaimoonPurchase', '.summary .variations_form', function (event) {
    if (this.querySelector('.single_add_to_cart_button')?.disabled) event.preventDefault();
  });

  $(function () {
    document.querySelectorAll(forms).forEach(function (form) {
      syncFormQuantities(form);
      if (form.classList.contains('variations_form')) setVariationState(form, null, false);
    });
  });
})(jQuery);
