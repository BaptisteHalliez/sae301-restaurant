(() => {
  'use strict';

  const panel = document.querySelector('.catalogue-filters');
  if (!panel) return;

  const sidebar = panel.closest('.catalogue-layout__sidebar');
  const layout = panel.closest('.catalogue-layout');
  const toggle = panel.querySelector('.catalogue-filters__toggle');
  const header = layout?.querySelector('.catalogue__header');
  const results = layout?.querySelector('.catalogue-results');
  if (!sidebar || !layout || !toggle) return;

  const compact = window.matchMedia('(max-width: 74.94rem)');
  const positionPanel = () => {
    if (compact.matches) {
      sidebar.style.removeProperty('--catalogue-filters-top');
      layout.style.removeProperty('--catalogue-filters-height');
      return;
    }

    const top = header
      ? Math.max(0, header.getBoundingClientRect().bottom - layout.getBoundingClientRect().top - toggle.offsetHeight + 12)
      : 0;
    sidebar.style.setProperty('--catalogue-filters-top', `${top}px`);
    layout.style.setProperty('--catalogue-filters-height', `${top + panel.offsetHeight}px`);
  };

  positionPanel();
  panel.addEventListener('toggle', positionPanel);
  window.addEventListener('resize', positionPanel);
  window.addEventListener('load', positionPanel, { once: true });
  if (document.fonts) document.fonts.ready.then(positionPanel);

  if ('ResizeObserver' in window) {
    const observer = new ResizeObserver(positionPanel);
    observer.observe(panel);
    if (header) observer.observe(header);
    if (results) observer.observe(results);
  }
})();
