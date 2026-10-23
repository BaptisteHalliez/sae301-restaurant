(() => {
  'use strict';
  const container = document.querySelector('[data-order-cancel-container]');
  if (!container || typeof HTMLDialogElement === 'undefined') return;
  const dialog = document.createElement('dialog');
  if (typeof dialog.showModal !== 'function') return;
  const panel = container.querySelector('[data-order-cancel-panel]');
  if (!panel) return;
  dialog.className = 'account-cancel__dialog';
  dialog.setAttribute('aria-labelledby', 'account-cancel-title');
  dialog.setAttribute('aria-describedby', 'account-cancel-description');
  const initiallyOpen = container.classList.contains('account-cancel--requested');
  container.replaceWith(dialog);
  dialog.append(panel);
  let opener = null;
  const open = (link) => {
    opener = link;
    if (!dialog.open) dialog.showModal();
    panel.focus({ preventScroll: true });
  };
  document.querySelectorAll('[data-order-cancel-open]').forEach((link) => {
    link.addEventListener('click', (event) => {
      event.preventDefault();
      open(link);
    });
  });
  panel.querySelector('[data-order-cancel-close]').addEventListener('click', (event) => {
    event.preventDefault();
    dialog.close();
  });
  dialog.addEventListener('close', () => {
    if (opener && opener.isConnected) opener.focus();
  });
  panel.querySelector('[data-order-cancel-form]').addEventListener('submit', () => {
    const button = panel.querySelector('[data-order-cancel-submit]');
    button.disabled = true;
    button.textContent = 'Envoi en cours…';
  });
  if (initiallyOpen) open(document.querySelector('[data-order-cancel-open]'));
})();
