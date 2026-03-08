document.addEventListener('DOMContentLoaded', () => {
  const MOBILE_BREAKPOINT = 991.98;
  const body = document.body;

  const isMobile = () => window.innerWidth <= MOBILE_BREAKPOINT;

  const clearSidebarOverlay = () => {
    document.querySelectorAll('.sidebar-overlay').forEach((el) => {
      el.remove();
    });
  };

  const syncSidebarState = () => {
    if (isMobile()) {
      body.classList.add('sidebar-collapse');
      body.classList.remove('sidebar-open');
      clearSidebarOverlay();
      return;
    }

    body.classList.remove('sidebar-open');
    clearSidebarOverlay();
  };

  syncSidebarState();

  window.addEventListener('resize', () => {
    syncSidebarState();
  });

  document.addEventListener('click', (e) => {
    const overlay = e.target.closest('.sidebar-overlay');
    if (!overlay) return;
    body.classList.remove('sidebar-open');
    clearSidebarOverlay();
  });

  document.querySelectorAll('.nav-sidebar .nav-link').forEach((link) => {
    link.addEventListener('click', () => {
      if (!isMobile()) return;
      const href = (link.getAttribute('href') || '').trim();
      const isTreeToggle = href === '#' || !!link.nextElementSibling || link.parentElement?.classList.contains('has-treeview');
      if (isTreeToggle) return;
      body.classList.remove('sidebar-open');
      body.classList.add('sidebar-collapse');
      clearSidebarOverlay();
    });
  });

  const sidebar = document.querySelector('.main-sidebar');
  if (!sidebar) return;

  if (localStorage.getItem('sidebar-dark') === '1') {
    document.body.classList.add('sidebar-dark-only');
  }

  sidebar.addEventListener('dblclick', () => {
    document.body.classList.toggle('sidebar-dark-only');
    localStorage.setItem(
      'sidebar-dark',
      document.body.classList.contains('sidebar-dark-only') ? '1' : '0'
    );
  });

  /* ===============================
   * ABSENSI DOBEL ALERT (FINAL)
   * =============================== */
  const badge   = document.getElementById('badgeDobel');
  const menuAbs = document.getElementById('menuAbsensi');
  const menuDob = document.getElementById('menuDobel');

  if (!badge || !menuAbs || !menuDob) return;

  const check = () => {
    fetch('/admin/absensi-dobel/count', {
      headers: {'X-Requested-With':'XMLHttpRequest'}
    })
    .then(r=>r.json())
    .then(d=>{
      if(d.total>0){
        badge.textContent = d.total;
        badge.classList.remove('d-none');
        badge.classList.add('pulse');

        menuAbs.classList.add('absensi-alert');
        menuDob.classList.add('absensi-alert');
      }else{
        badge.classList.add('d-none');
        badge.classList.remove('pulse');

        menuAbs.classList.remove('absensi-alert');
        menuDob.classList.remove('absensi-alert');
      }
    });
  };

  check();
  setInterval(check,15000);
});
