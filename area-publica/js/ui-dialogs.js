(function () {
  if (window.__ipikkDialogsInit) return;
  window.__ipikkDialogsInit = true;

  function ensureStyles() {
    if (document.getElementById('ipikk-dialog-styles')) return;
    const css = `
#ipikkDialogOverlay{position:fixed;inset:0;background:rgba(4,18,40,.55);display:none;align-items:center;justify-content:center;z-index:2147483647;padding:16px}
#ipikkDialogOverlay.ativo{display:flex}
.ipikk-dialog{background:#fff;max-width:460px;width:100%;border-radius:14px;box-shadow:0 20px 45px rgba(0,0,0,.25);overflow:hidden;font-family:inherit}
.ipikk-dialog header{padding:16px 18px;background:linear-gradient(135deg,#003072,#0a4da8);color:#fff;font-weight:700}
.ipikk-dialog .corpo{padding:18px;color:#203040;line-height:1.45}
.ipikk-dialog .acoes{padding:14px 18px;display:flex;gap:10px;justify-content:flex-end;background:#f6f8fb}
.ipikk-btn{border:none;border-radius:8px;padding:9px 14px;font-weight:600;cursor:pointer}
.ipikk-btn-sec{background:#e6ebf2;color:#314055}
.ipikk-btn-pri{background:#003072;color:#fff}
.ipikk-input{width:100%;margin-top:10px;border:1px solid #c7d4e6;border-radius:8px;padding:10px}
`;
    const style = document.createElement('style');
    style.id = 'ipikk-dialog-styles';
    style.textContent = css;
    document.head.appendChild(style);
  }

  function ensureOverlay() {
    ensureStyles();
    let overlay = document.getElementById('ipikkDialogOverlay');
    if (overlay) return overlay;
    overlay = document.createElement('div');
    overlay.id = 'ipikkDialogOverlay';
    overlay.innerHTML = '<div class="ipikk-dialog"><header id="ipikkDialogTitle"></header><div class="corpo" id="ipikkDialogBody"></div><div class="acoes" id="ipikkDialogActions"></div></div>';
    document.body.appendChild(overlay);
    overlay.addEventListener('click', (e) => { if (e.target === overlay && overlay.dataset.dismiss === '1') close(false); });
    return overlay;
  }

  let resolver = null;
  function close(val) {
    const overlay = document.getElementById('ipikkDialogOverlay');
    if (overlay) overlay.classList.remove('ativo');
    if (resolver) { resolver(val); resolver = null; }
  }

  function openDialog({ title, message, type = 'alert', defaultValue = '' }) {
    const overlay = ensureOverlay();
    const titleEl = document.getElementById('ipikkDialogTitle');
    const bodyEl = document.getElementById('ipikkDialogBody');
    const actions = document.getElementById('ipikkDialogActions');
    titleEl.textContent = title || (type === 'confirm' ? 'Confirmar ação' : 'Mensagem do sistema');
    bodyEl.innerHTML = '';
    bodyEl.appendChild(document.createTextNode(message || ''));
    actions.innerHTML = '';

    let input = null;
    if (type === 'prompt') {
      input = document.createElement('input');
      input.className = 'ipikk-input';
      input.value = defaultValue || '';
      bodyEl.appendChild(input);
    }

    return new Promise((resolve) => {
      resolver = resolve;
      if (type !== 'alert') {
        const cancel = document.createElement('button');
        cancel.className = 'ipikk-btn ipikk-btn-sec';
        cancel.textContent = 'Cancelar';
        cancel.onclick = () => close(type === 'prompt' ? null : false);
        actions.appendChild(cancel);
      }
      const ok = document.createElement('button');
      ok.className = 'ipikk-btn ipikk-btn-pri';
      ok.textContent = type === 'confirm' ? 'Confirmar' : 'OK';
      ok.onclick = () => close(type === 'prompt' ? input.value : true);
      actions.appendChild(ok);

      overlay.dataset.dismiss = type === 'alert' ? '1' : '0';
      overlay.classList.add('ativo');
      if (input) setTimeout(() => input.focus(), 0);
    });
  }

  window.alert = function (message) { openDialog({ message: String(message ?? '') }); };
  window.ipikkAlert = function (message, title) { return openDialog({ message: String(message ?? ''), title }); };

  window.confirmAsync = function (message, title) { return openDialog({ type: 'confirm', title, message: String(message ?? '') }); };
  window.promptAsync = function (message, defaultValue, title) { return openDialog({ type: 'prompt', title, message: String(message ?? ''), defaultValue: defaultValue ?? '' }); };
})();