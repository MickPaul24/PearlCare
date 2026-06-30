// assets/app.js — Kampala Skin Clinic

// ---- Theme Toggle ------------------------------------------
const themeToggle = document.getElementById('themeToggle');
const html = document.documentElement;

function applyTheme(t) {
  html.setAttribute('data-theme', t);
  document.cookie = `theme=${t};path=/;max-age=31536000`;
  if (themeToggle) {
    themeToggle.innerHTML = t === 'dark'
      ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>'
      : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>';
  }
}

if (themeToggle) {
  const current = html.getAttribute('data-theme') || 'light';
  applyTheme(current);
  themeToggle.addEventListener('click', () => {
    const next = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    applyTheme(next);
  });
}

// ---- Auto-dismiss flash messages ---------------------------
setTimeout(() => {
  const f = document.querySelector('.flash');
  if (f) f.style.display = 'none';
}, 5000);

// ---- Modal helpers -----------------------------------------
function openModal(id) { document.getElementById(id)?.classList.add('open'); }
function closeModal(id) { document.getElementById(id)?.classList.remove('open'); }

document.querySelectorAll('[data-modal-open]').forEach(btn => {
  btn.addEventListener('click', () => openModal(btn.dataset.modalOpen));
});
document.querySelectorAll('[data-modal-close]').forEach(btn => {
  btn.addEventListener('click', () => closeModal(btn.dataset.modalClose));
});

// ---- Queue live timer (updates every second) ---------------
function updateTimers() {
  document.querySelectorAll('[data-start-time]').forEach(el => {
    const start = new Date(el.dataset.startTime);
    const diff  = Math.floor((Date.now() - start) / 1000);
    const m = Math.floor(diff / 60);
    const s = diff % 60;
    el.textContent = `Duration: ${m}m ${s}s`;
  });
}
if (document.querySelector('[data-start-time]')) {
  setInterval(updateTimers, 1000);
  updateTimers();
}

// ---- Simple bar chart renderer -----------------------------
function renderBarChart(canvasId, data, color = 'var(--primary)') {
  const wrap = document.getElementById(canvasId);
  if (!wrap) return;
  const max = Math.max(...data.map(d => d.v));
  wrap.innerHTML = '';
  data.forEach(d => {
    const pct = max ? (d.v / max * 100) : 0;
    const bar = document.createElement('div');
    bar.style.cssText = `flex:1;background:${color};border-radius:4px 4px 0 0;height:${pct}%;opacity:.75;transition:opacity .15s;position:relative;`;
    bar.title = `${d.l}: ${d.v}`;
    bar.addEventListener('mouseenter', () => bar.style.opacity = '1');
    bar.addEventListener('mouseleave', () => bar.style.opacity = '.75');
    wrap.appendChild(bar);
  });
}

// ---- Confirm delete ----------------------------------------
document.querySelectorAll('[data-confirm]').forEach(el => {
  el.addEventListener('click', e => {
    e.preventDefault();
    showConfirm(el.dataset.confirm || 'Are you sure?', function(ok) {
      if (!ok) return;
      // If it's a link, navigate
      if (el.tagName.toLowerCase() === 'a' && el.href) {
        window.location.href = el.href;
        return;
      }
      // If it's inside a form, submit the form
      var f = el.closest('form');
      if (f) {
        f.submit();
        return;
      }
      // Fallback: trigger click (may re-trigger but usually fine)
      el.click();
    });
  });
});

// ---- Print patient form ------------------------------------
function printPatientForm() {
  window.print();
}
