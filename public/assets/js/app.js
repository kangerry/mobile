document.addEventListener('DOMContentLoaded', () => {
  const toggleBtn = document.querySelector('[data-sidebar-toggle]');
  const sidebar = document.querySelector('.sidebar');
  if (toggleBtn && sidebar) {
    toggleBtn.addEventListener('click', () => {
      sidebar.classList.toggle('open');
      document.body.classList.toggle('sidebar-collapsed');
    });
  }

  if (window.Chart && document.getElementById('dashboardChart')) {
    const canvas = document.getElementById('dashboardChart');
    const ctx = canvas.getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, canvas.height || 180);
    gradient.addColorStop(0, 'rgba(30, 115, 232, 0.35)');
    gradient.addColorStop(1, 'rgba(77, 163, 255, 0)');
    const chart = new Chart(ctx, {
      type: 'line',
      data: {
        labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
        datasets: [{
          label: 'Transaksi',
          data: [12, 19, 3, 5, 2, 3, 9],
          borderColor: '#1e73e8',
          backgroundColor: gradient,
          tension: 0.4,
          fill: true
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { grid: { color: 'rgba(0,0,0,0.05)' } }, x: { grid: { display: false } } }
      }
    });
    window.dashboardChart = chart;
    const sk = document.querySelector('[data-chart-skeleton]');
    if (sk) sk.style.display = 'none';
  }

  document.querySelectorAll('input[type="file"][data-preview-target]').forEach((input) => {
    const target = input.getAttribute('data-preview-target');
    const container = document.querySelector(target);
    if (!container) return;
    const render = (files) => {
      container.innerHTML = '';
      Array.from(files).slice(0, 5).forEach((file) => {
        if (file && file.type && file.type.startsWith('image/')) {
          const img = document.createElement('img');
          img.className = 'preview-thumb';
          const url = URL.createObjectURL(file);
          img.src = url;
          img.onload = () => URL.revokeObjectURL(url);
          container.appendChild(img);
        }
      });
    };
    input.addEventListener('change', (e) => render(e.target.files));
  });

  const rippleTargets = Array.from(document.querySelectorAll('.btn-brand, .dash-card'));
  rippleTargets.forEach((el) => {
    el.addEventListener('click', (e) => {
      const rect = el.getBoundingClientRect();
      const r = Math.max(rect.width, rect.height);
      const ripple = document.createElement('span');
      ripple.className = 'ripple';
      ripple.style.width = ripple.style.height = r + 'px';
      ripple.style.left = (e.clientX - rect.left - r / 2) + 'px';
      ripple.style.top = (e.clientY - rect.top - r / 2) + 'px';
      el.appendChild(ripple);
      ripple.addEventListener('animationend', () => ripple.remove());
    });
  });

  const sidebarEl = document.querySelector('.sidebar');
  if (sidebarEl) {
    let backdrop;
    const ensureBackdrop = () => {
      if (!backdrop) {
        backdrop = document.createElement('div');
        backdrop.className = 'sidebar-backdrop';
        document.body.appendChild(backdrop);
        backdrop.addEventListener('click', () => {
          sidebarEl.classList.remove('open');
          document.body.classList.remove('sidebar-collapsed');
          backdrop.style.display = 'none';
        });
      }
      return backdrop;
    };
    document.querySelector('[data-sidebar-toggle]')?.addEventListener('click', () => {
      const bp = window.matchMedia('(max-width: 1024px)').matches;
      if (bp) {
        const b = ensureBackdrop();
        b.style.display = sidebarEl.classList.contains('open') ? 'none' : 'block';
      }
    });
    document.addEventListener('keyup', (e) => {
      if (e.key === 'Escape') {
        sidebarEl.classList.remove('open');
        document.body.classList.remove('sidebar-collapsed');
        const b = document.querySelector('.sidebar-backdrop');
        if (b) b.style.display = 'none';
      }
    });
  }

  const animateCount = (el, to, duration = 700) => {
    to = isNaN(to) ? 0 : to;
    const from = parseInt((el.textContent || '0').replace(/\D/g, ''), 10) || 0;
    const start = performance.now();
    const step = (now) => {
      const p = Math.min((now - start) / duration, 1);
      const val = Math.floor(from + (to - from) * p);
      el.textContent = val.toLocaleString('id-ID');
      if (p < 1) requestAnimationFrame(step);
    };
    el.classList.remove('bump');
    void el.offsetWidth;
    el.classList.add('bump');
    requestAnimationFrame(step);
  };

  document.querySelectorAll('[data-count-to]').forEach((el) => {
    const to = parseInt(el.getAttribute('data-count-to') || '0', 10);
    animateCount(el, to);
  });

  window.updateDashboardStats = (detail) => {
    const ev = new CustomEvent('dashboard:stats', { detail });
    window.dispatchEvent(ev);
  };

  window.addEventListener('dashboard:stats', (e) => {
    const d = e.detail || {};
    const updateVal = (key, value) => {
      const el = document.querySelector(`.dash-card .value[data-stat-key="${key}"]`);
      if (el) animateCount(el, parseInt(value, 10) || 0);
    };
    Object.keys(d).forEach((k) => {
      if (['chartLabels', 'chartData'].includes(k)) return;
      updateVal(k, d[k]);
    });
    if (window.dashboardChart && Array.isArray(d.chartData)) {
      if (Array.isArray(d.chartLabels)) window.dashboardChart.data.labels = d.chartLabels;
      window.dashboardChart.data.datasets[0].data = d.chartData;
      window.dashboardChart.update();
    }
  });

  const chartSkeleton = document.querySelector('[data-chart-skeleton]');
  if (chartSkeleton && document.getElementById('dashboardChart')) {
    setTimeout(() => { chartSkeleton.style.display = 'none'; }, 300);
  }

  const applyUIMode = (mode) => {
    document.body.classList.remove('mode-mobile', 'mode-desktop');
    if (mode === 'mobile') document.body.classList.add('mode-mobile');
    if (mode === 'desktop') document.body.classList.add('mode-desktop');
    try { localStorage.setItem('uiMode', mode); } catch (e) {}
    document.querySelectorAll('[data-ui-mode]').forEach((btn) => {
      btn.classList.toggle('active', btn.getAttribute('data-ui-mode') === mode);
    });
  };
  const savedMode = (() => {
    try { return localStorage.getItem('uiMode') || 'auto'; } catch (e) { return 'auto'; }
  })();
  applyUIMode(savedMode);
  document.querySelectorAll('[data-ui-mode]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const m = btn.getAttribute('data-ui-mode');
      applyUIMode(m);
    });
  });
});
