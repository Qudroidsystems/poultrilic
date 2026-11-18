{{-- Shared UI behaviour: theme menu, Apple-style confirmations, flash toasts, Spotlight (⌘K). --}}
<div id="spotlight-overlay" class="pl-spotlight" hidden>
    <div class="pl-spotlight-box" role="dialog" aria-modal="true" aria-label="Search">
        <div class="pl-spotlight-input">
            <i class="ri-search-line"></i>
            <input id="spotlight-input" type="text" placeholder="Search flocks, ponds, customers, reports, pages…" autocomplete="off" spellcheck="false">
            <kbd>esc</kbd>
        </div>
        <div id="spotlight-results" class="pl-spotlight-results" role="listbox"></div>
        <div class="pl-spotlight-foot"><span><kbd>↑</kbd><kbd>↓</kbd> move</span><span><kbd>↵</kbd> open</span><span><kbd>⌘</kbd><kbd>K</kbd> toggle</span></div>
    </div>
</div>
<script>
(function () {
    'use strict';
    var qsa = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };

    /* ── Theme menu (appearance + style are applied by js/portal-theme.js in <head>) ── */
    var PT = window.PortalTheme;
    // The template's app.js restores its own theme from sessionStorage; the portal theme wins.
    if (PT) { PT.apply(); }
    var ICON = { light: 'ri-sun-line', dark: 'ri-moon-line', auto: 'ri-contrast-2-line' };
    function syncTheme() {
        var mode = PT ? PT.mode : 'light', ui = PT ? PT.ui : 'default';
        var icon = document.getElementById('theme-icon');
        if (icon) { icon.className = (ui === 'apple' && mode === 'light' ? 'ri-apple-line' : (ICON[mode] || ICON.light)) + ' align-middle fs-3xl'; }
        qsa('.theme-mode-item').forEach(function (a) {
            var on = a.hasAttribute('data-ui') ? a.getAttribute('data-ui') === ui : a.getAttribute('data-mode') === mode;
            a.classList.toggle('is-active', on);
            a.setAttribute('aria-checked', on ? 'true' : 'false');
        });
    }
    qsa('.theme-mode-item').forEach(function (a) {
        a.addEventListener('click', function (e) {
            e.preventDefault();
            if (!PT) { return; }
            if (a.hasAttribute('data-ui')) { PT.setUi(a.getAttribute('data-ui')); } else { PT.setMode(a.getAttribute('data-mode')); }
        });
    });
    document.addEventListener('portal-theme-change', syncTheme);
    syncTheme();

    /* ── Confirmations: <form data-confirm="…"> uses an Apple-style dialog ── */
    var hasAlert = typeof AppleAlert !== 'undefined' && window.Swal;
    document.addEventListener('submit', function (e) {
        var form = e.target, msg = form.getAttribute('data-confirm');
        if (!msg || form.__confirmed) { return; }
        e.preventDefault();
        e.stopImmediatePropagation();
        var destructive = /delete|remove|cannot be undone/i.test(msg);
        var safe = msg.replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; });
        var go = function () { form.__confirmed = true; form.requestSubmit ? form.requestSubmit(e.submitter || undefined) : form.submit(); };
        if (!hasAlert) { if (window.confirm(msg)) { go(); } return; }
        var p = destructive
            ? AppleAlert.destructive(safe, null, { confirmText: 'Delete' })
            : AppleAlert.confirm(safe, null, { confirmText: 'Continue' });
        p.then(function (r) { if (r && r.isConfirmed) { go(); } });
    }, true);

    /* ── Flash messages as a toast as well as the inline alert ── */
    @if (session('success'))
        if (hasAlert) { AppleAlert.toast(@json(e(session('success'))), 'success', 2800); }
    @endif

    /* ── Spotlight ── */
    var overlay = document.getElementById('spotlight-overlay');
    var input = document.getElementById('spotlight-input');
    var list = document.getElementById('spotlight-results');
    var trigger = document.getElementById('spotlight-trigger');
    if (!overlay || !input) { return; }

    var pages = qsa('#navbar-nav a.nav-link[href]').filter(function (a) { return a.getAttribute('href').indexOf('http') === 0 || a.getAttribute('href').indexOf('/') === 0; })
        .map(function (a) {
            var i = a.querySelector('i');
            return { group: 'Pages', title: (a.textContent || '').trim(), url: a.href, icon: i ? i.className : 'ri-file-line' };
        });
    var recentKey = 'pl-spotlight-recent';
    function recent() { try { return JSON.parse(localStorage.getItem(recentKey) || '[]'); } catch (e) { return []; } }
    function remember(item) {
        try {
            var r = recent().filter(function (x) { return x.url !== item.url; });
            r.unshift({ group: 'Recent', title: item.title, sub: item.sub || '', url: item.url, icon: item.icon });
            localStorage.setItem(recentKey, JSON.stringify(r.slice(0, 6)));
        } catch (e) {}
    }

    var items = [], active = 0, timer = null, seq = 0;
    function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
    function render() {
        if (!items.length) { list.innerHTML = '<div class="pl-spotlight-empty">' + (input.value.trim() ? 'No matches' : 'Type to search') + '</div>'; return; }
        var html = '', group = null;
        items.forEach(function (it, i) {
            if (it.group !== group) { group = it.group; html += '<div class="pl-spotlight-group">' + esc(group) + '</div>'; }
            html += '<a class="pl-spotlight-item' + (i === active ? ' is-active' : '') + '" role="option" data-i="' + i + '" href="' + esc(it.url) + '">'
                + '<span class="pl-spotlight-ico"><i class="' + esc(it.icon || 'ri-arrow-right-line') + '"></i></span>'
                + '<span class="pl-spotlight-text"><span>' + esc(it.title) + '</span>' + (it.sub ? '<small>' + esc(it.sub) + '</small>' : '') + '</span>'
                + '<i class="ri-corner-down-left-line pl-spotlight-enter"></i></a>';
        });
        list.innerHTML = html;
        var el = list.querySelector('.is-active'); if (el) { el.scrollIntoView({ block: 'nearest' }); }
    }
    function search() {
        var q = input.value.trim().toLowerCase();
        active = 0;
        if (!q) { items = recent().concat(pages.slice(0, 8)); render(); return; }
        var local = pages.filter(function (p) { return p.title.toLowerCase().indexOf(q) > -1; });
        items = local; render();
        clearTimeout(timer);
        var mine = ++seq;
        timer = setTimeout(function () {
            fetch(@json(route('search')) + '?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.ok ? r.json() : { results: [] }; })
                .then(function (d) { if (mine !== seq) { return; } items = (d.results || []).concat(local); render(); })
                .catch(function () {});
        }, 160);
    }
    function open() { overlay.hidden = false; document.body.classList.add('pl-spotlight-open'); input.value = ''; search(); setTimeout(function () { input.focus(); }, 10); }
    function close() { overlay.hidden = true; document.body.classList.remove('pl-spotlight-open'); }
    function go(i) { var it = items[i]; if (!it) { return; } remember(it); window.location.href = it.url; }

    if (trigger) { trigger.addEventListener('click', open); }
    document.addEventListener('keydown', function (e) {
        if ((e.metaKey || e.ctrlKey) && (e.key === 'k' || e.key === 'K')) { e.preventDefault(); overlay.hidden ? open() : close(); return; }
        if (overlay.hidden) { return; }
        if (e.key === 'Escape') { close(); }
        else if (e.key === 'ArrowDown') { e.preventDefault(); active = Math.min(items.length - 1, active + 1); render(); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); active = Math.max(0, active - 1); render(); }
        else if (e.key === 'Enter') { e.preventDefault(); go(active); }
    });
    overlay.addEventListener('mousedown', function (e) { if (e.target === overlay) { close(); } });
    list.addEventListener('click', function (e) { var a = e.target.closest('[data-i]'); if (a) { e.preventDefault(); go(+a.getAttribute('data-i')); } });
    input.addEventListener('input', search);
})();
</script>
