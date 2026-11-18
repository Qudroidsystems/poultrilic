{{-- Layout behaviour from csskabba-portal: theme menu, top-bar dropdowns, active sidebar item, ripple,
     back-to-top, mobile sidebar, image modal, search tooltip, layout reset, NProgress page-load bar. --}}
<script>
(function () {
    'use strict';

    /* ── helpers ── */
    function qs(sel, ctx)  { return (ctx || document).querySelector(sel); }
    function qsa(sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); }

    /* ── manual dropdown factory ── */
    function makeDropdown(btnId, panelId) {
        var btn = document.getElementById(btnId), panel = document.getElementById(panelId);
        if (!btn || !panel) return;
        function open()  { panel.style.display = 'block'; btn.setAttribute('aria-expanded','true'); }
        function close() { panel.style.display = 'none';  btn.setAttribute('aria-expanded','false'); }
        btn.addEventListener('click', function(e){ e.stopPropagation(); panel.style.display === 'none' ? open() : close(); });
        document.addEventListener('click', function(e){ if (!btn.contains(e.target) && !panel.contains(e.target)) close(); });
        document.addEventListener('keydown', function(e){ if (e.key === 'Escape') close(); });
    }

    /* ── theme ── */
    function initTheme() {
        // Appearance + style are applied by js/portal-theme.js (loaded in <head>);
        // this only wires up the top-bar menu and keeps it in sync.
        var PT     = window.PortalTheme;
        var iconEl = document.getElementById('theme-icon');
        var ICON   = { light:'ri-sun-line align-middle fs-3xl', dark:'ri-moon-line align-middle fs-3xl', auto:'ri-contrast-2-line align-middle fs-3xl' };

        function setMode(m) {
            if (PT) return PT.setMode(m);
            var dark = m === 'dark' || (m === 'auto' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.setAttribute('data-bs-theme', dark ? 'dark' : 'light');
            try { localStorage.setItem('app-theme', m); } catch (e) {}
            sync();
        }
        function sync() {
            var mode = PT ? PT.mode : (localStorage.getItem('app-theme') || 'light');
            var ui   = PT ? PT.ui   : 'default';
            var dark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
            if (iconEl) iconEl.className = ICON[mode] || ICON.light;
            var r = document.getElementById(dark ? 'layout-mode-dark' : 'layout-mode-light');
            if (r) r.checked = true;
            qsa('.theme-mode-item').forEach(function(a){
                var on = a.hasAttribute('data-ui') ? a.getAttribute('data-ui') === ui : a.getAttribute('data-mode') === mode;
                a.classList.toggle('is-active', on);
                a.setAttribute('aria-checked', on ? 'true' : 'false');
            });
        }
        qsa('.theme-mode-item').forEach(function(a){
            a.addEventListener('click', function(e){
                e.preventDefault();
                if (a.hasAttribute('data-ui')) { if (PT) PT.setUi(a.getAttribute('data-ui')); }
                else setMode(a.getAttribute('data-mode'));
                var dd = document.getElementById('theme-dropdown');
                if (dd) dd.style.display = 'none';
            });
        });
        qsa('[name="data-bs-theme"]').forEach(function(r){ r.addEventListener('change', function(){ setMode(r.value); }); });
        document.addEventListener('portal-theme-change', sync);
        sync();
    }

    /* ── NProgress ── */
    function initNProgress() {
        if (typeof NProgress === 'undefined') return;
        NProgress.configure({ showSpinner: false, speed: 400, minimum: 0.1 });
        qsa('a[href]').forEach(function(a){
            var h = a.getAttribute('href') || '';
            if (h && h !== '#' && !h.startsWith('javascript') && !h.startsWith('mailto') && !h.startsWith('tel')
                && !a.hasAttribute('data-bs-toggle') && !a.hasAttribute('data-bs-dismiss') && a.getAttribute('target') !== '_blank') {
                a.addEventListener('click', function(){ NProgress.start(); });
            }
        });
        window.addEventListener('pageshow', function(){ NProgress.done(); });
        window.addEventListener('load',     function(){ NProgress.done(); });
    }

    /* ── active sidebar ── */
    function initActiveSidebar() {
        var cur = (window.location.pathname || '/').replace(/\/+$/, '') || '/';
        var best = null, bestLen = -1;
        // Consider every real sidebar link (top-level direct links AND submenu children).
        qsa('#navbar-nav a.nav-link[href]').forEach(function(link){
            var href = link.getAttribute('href') || '';
            if (!href || href.charAt(0) === '#' || href.indexOf('javascript') === 0) return;
            var lp;
            try { lp = (new URL(link.href, window.location.origin).pathname || '/').replace(/\/+$/, '') || '/'; }
            catch(e){ return; }
            var match = (lp === cur) || (lp.length > 1 && cur.indexOf(lp + '/') === 0);
            if (match && lp.length > bestLen) { best = link; bestLen = lp.length; }
        });
        if (!best) return;
        best.classList.add('active');
        if (best.closest('.nav-sm')) best.classList.add('nav-active-child');
        else best.classList.add('nav-active-parent');
        var col = best.closest('.collapse');
        if (col) {
            col.classList.add('show');
            var tog = qs('[data-bs-target="#'+col.id+'"],[href="#'+col.id+'"]');
            if (tog) {
                tog.setAttribute('aria-expanded','true');
                tog.classList.remove('collapsed');
                tog.classList.add('nav-active-parent','active');
            }
        }
        setTimeout(function(){ try { best.scrollIntoView({behavior:'smooth',block:'nearest'}); } catch(e){} }, 350);
    }

    /* ── ripple ── */
    function initRipple() {
        qsa('#navbar-nav .nav-link').forEach(function(link){
            link.addEventListener('click', function(e){
                if (link.hasAttribute('data-bs-toggle')) return;
                var r = document.createElement('span');
                var rect = link.getBoundingClientRect();
                var s = Math.max(rect.width, rect.height);
                r.className = 'nav-ripple';
                r.style.cssText = 'width:'+s+'px;height:'+s+'px;left:'+(e.clientX-rect.left-s/2)+'px;top:'+(e.clientY-rect.top-s/2)+'px;';
                link.appendChild(r);
                setTimeout(function(){ r.parentNode && r.parentNode.removeChild(r); }, 650);
            });
        });
    }

    /* ── back to top ── */
    function initBackToTop() {
        var btn = document.getElementById('back-to-top');
        if (!btn) return;
        window.addEventListener('scroll', function(){ btn.classList.toggle('show', window.scrollY > 300); }, {passive:true});
        btn.addEventListener('click', function(){ window.scrollTo({top:0,behavior:'smooth'}); });
    }

    /* ── hamburger / mobile sidebar ── */
    function initHamburger() {
        var ham     = document.getElementById('topnav-hamburger-icon');
        var overlay = document.getElementById('vertical-overlay');
        var body    = document.body;

        function closeSidebar() { body.classList.remove('vertical-sidebar-enable'); }
        function openSidebar()  { body.classList.add('vertical-sidebar-enable'); }

        if (ham) {
            var freshHam = ham.cloneNode(true);
            ham.parentNode.replaceChild(freshHam, ham);
            document.getElementById('topnav-hamburger-icon').addEventListener('click', function(e){
                e.preventDefault(); e.stopPropagation();
                body.classList.contains('vertical-sidebar-enable') ? closeSidebar() : openSidebar();
            });
        }
        if (overlay) overlay.addEventListener('click', closeSidebar);
        document.addEventListener('keydown', function(e){
            if (e.key === 'Escape' && body.classList.contains('vertical-sidebar-enable')) closeSidebar();
        });
    }

    /* ── image modal ── */
    function initImageModal() {
        var modal = document.getElementById('imageViewModal');
        if (!modal) return;
        modal.addEventListener('show.bs.modal', function(e){
            var img = document.getElementById('enlargedImage');
            var src = e.relatedTarget ? e.relatedTarget.getAttribute('data-image') : null;
            if (img && src) img.src = src;
        });
    }

    /* ── search tooltip ── */
    function initSearchTooltip() {
        var btn = document.getElementById('spotlight-trigger');
        var tip = qs('.search-tooltip');
        if (!btn || !tip) return;
        btn.addEventListener('mouseenter', function(){ tip.style.opacity='1'; });
        btn.addEventListener('mouseleave', function(){ tip.style.opacity='0'; });
    }

    /* ── reset layout ── */
    function initReset() {
        var btn = document.getElementById('reset-layout');
        if (btn) btn.addEventListener('click', function(){ sessionStorage.clear(); localStorage.removeItem('app-theme'); localStorage.removeItem('app-ui'); location.reload(); });
    }

    /* ── INIT ── */
    document.addEventListener('DOMContentLoaded', function(){
        initTheme();
        makeDropdown('theme-toggle-btn', 'theme-dropdown');
        makeDropdown('user-menu-btn',    'user-dropdown');
        initActiveSidebar();
        initRipple();
        initBackToTop();
        initHamburger();
        initImageModal();
        initSearchTooltip();
        initReset();
        initNProgress();
    });
})();
</script>

