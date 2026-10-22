(function ($) {
  'use strict';
  let focusedField = '';

  function updateSlots(section, clearSelection) {
    const date = section.querySelector('[name="thaimoon_fulfilment_date"]');
    const select = section.querySelector('[name="thaimoon_fulfilment_slot"]');
    const data = section.querySelector('.checkout-fulfilment__data');
    if (!date || !select || !data) return;

    let available;
    try { available = JSON.parse(data.textContent); } catch (error) { return; }
    const previous = clearSelection ? '' : select.value;
    const slots = available[date.value]?.slots || {};
    select.replaceChildren(new Option(date.value ? select.dataset.emptyLabel : select.dataset.dayLabel, ''));
    Object.entries(slots).forEach(function ([value, label]) {
      const combined = date.value + '|' + value;
      select.add(new Option(label, combined, false, combined === previous));
    });
    select.disabled = !date.value;
  }

  function refresh() {
    document.querySelectorAll('.checkout-fulfilment').forEach(function (section) {
      updateSlots(section, false);
    });
    if (focusedField && document.activeElement === document.body) {
      document.getElementById(focusedField)?.focus({ preventScroll: true });
    }
    focusedField = '';
  }

  $(document).on('change.thaimoonSlots', '[name="thaimoon_fulfilment_date"]', function () {
    updateSlots(this.closest('.checkout-fulfilment'), true);
    $(document.body).trigger('update_checkout');
  });
  $(document).on('change.thaimoonSlots', '[name="thaimoon_fulfilment_slot"]', function () {
    $(document.body).trigger('update_checkout');
  });
  $(document.body).on('updated_checkout', refresh);
  $(document.body).on('update_checkout', function () {
    const focused = document.activeElement;
    focusedField = focused?.closest('.checkout-fulfilment') ? focused.id : '';
  });
  $(refresh);
})(jQuery);
