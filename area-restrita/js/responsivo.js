(function () {
  if (window.__ipikkResponsivoInit) return;
  window.__ipikkResponsivoInit = true;

  function ensureTableWrappers() {
    document.querySelectorAll('table').forEach((table) => {
      const parent = table.parentElement;
      if (!parent) return;
      if (parent.classList.contains('table-responsive') || parent.classList.contains('tabela-wrapper')) return;
      const wrapper = document.createElement('div');
      wrapper.className = 'table-responsive';
      parent.insertBefore(wrapper, table);
      wrapper.appendChild(table);
    });
  }

  function syncMobileLayout() {
    const mobile = window.matchMedia('(max-width: 1024px)').matches;
    const main = document.querySelector('.conteudo-principal');
    if (mobile && main) main.style.marginLeft = '0';

    document.querySelectorAll('.sidebar, #sidebar').forEach((sb) => {
      if (!mobile) {
        sb.classList.remove('visivel');
        sb.classList.remove('visible');
      }
    });

    const overlay = document.getElementById('overlaySidebar');
    if (overlay && !mobile) {
      overlay.classList.remove('visivel');
      overlay.classList.remove('visible');
    }

    document.querySelectorAll('canvas').forEach((c) => {
      c.style.maxWidth = '100%';
      c.style.height = 'auto';
    });
  }

  function init() {
    ensureTableWrappers();
    syncMobileLayout();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  window.addEventListener('resize', syncMobileLayout);
})();
