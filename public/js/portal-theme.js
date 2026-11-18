/* ==========================================================================
   Portal theme engine
   --------------------------------------------------------------------------
   Loaded in <head> of layouts/master.blade.php, before the page paints.

   • Appearance (light / dark / auto)  — localStorage "app-theme"
   • Style      (default / apple)      — localStorage "app-ui"
     → sets <html data-bs-theme="…" data-ui="…">  (styles: css/portal-theme.css)

   • Dark adapter: many pages carry their own CSS with hard-coded whites,
     light greys and near-black text, which ignore the template's dark mode.
     When dark mode is on, this reads those rules (and inline style="…"
     attributes, including content added later by AJAX) and maps light
     backgrounds / dark text / light borders to dark equivalents, keeping the
     colour's hue so tinted chips stay tinted. Generated rules are scoped to
     html[data-bs-theme=dark], so light mode is untouched.

   Public API:  PortalTheme.setMode('light'|'dark'|'auto')
                PortalTheme.setUi('default'|'apple')
                PortalTheme.mode / PortalTheme.ui / PortalTheme.scheme
   Opt-out:     add data-pt-skip to any element to leave its subtree alone.
   ========================================================================== */
(function () {
    'use strict';

    var root = document.documentElement;
    var KEY_MODE = 'app-theme', KEY_UI = 'app-ui';
    var mq = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;

    function read(k, d) { try { return localStorage.getItem(k) || d; } catch (e) { return d; } }
    function write(k, v) { try { localStorage.setItem(k, v); } catch (e) {} }

    function schemeFor(mode) {
        return mode === 'auto' ? (mq && mq.matches ? 'dark' : 'light') : (mode === 'dark' ? 'dark' : 'light');
    }

    /* ── colour maths ──────────────────────────────────────────────────── */
    var NAMED = {
        white: [255, 255, 255], snow: [255, 250, 250], ghostwhite: [248, 248, 255], whitesmoke: [245, 245, 245],
        aliceblue: [240, 248, 255], azure: [240, 255, 255], ivory: [255, 255, 240], floralwhite: [255, 250, 240],
        linen: [250, 240, 230], seashell: [255, 245, 238], mintcream: [245, 255, 250], honeydew: [240, 255, 240],
        lavender: [230, 230, 250], beige: [245, 245, 220], gainsboro: [220, 220, 220], lightgray: [211, 211, 211],
        lightgrey: [211, 211, 211], black: [0, 0, 0], dimgray: [105, 105, 105], dimgrey: [105, 105, 105],
        darkslategray: [47, 79, 79], navy: [0, 0, 128], darkblue: [0, 0, 139], maroon: [128, 0, 0],
        darkgreen: [0, 100, 0], midnightblue: [25, 25, 112], gray: [128, 128, 128], grey: [128, 128, 128]
    };

    function parseColor(str) {
        if (!str) return null;
        var s = String(str).trim().toLowerCase();
        if (NAMED[s]) return { r: NAMED[s][0], g: NAMED[s][1], b: NAMED[s][2], a: 1 };
        var m = s.match(/^#([0-9a-f]{3,8})$/);
        if (m) {
            var h = m[1];
            if (h.length === 3 || h.length === 4) h = h.split('').map(function (c) { return c + c; }).join('');
            if (h.length !== 6 && h.length !== 8) return null;
            return {
                r: parseInt(h.slice(0, 2), 16), g: parseInt(h.slice(2, 4), 16), b: parseInt(h.slice(4, 6), 16),
                a: h.length === 8 ? parseInt(h.slice(6, 8), 16) / 255 : 1
            };
        }
        m = s.match(/^rgba?\(\s*([\d.]+)[\s,]+([\d.]+)[\s,]+([\d.]+)(?:\s*[,/]\s*([\d.]+%?))?\s*\)$/);
        if (m) {
            var a = m[4] === undefined ? 1 : (m[4].slice(-1) === '%' ? parseFloat(m[4]) / 100 : parseFloat(m[4]));
            return { r: +m[1], g: +m[2], b: +m[3], a: a };
        }
        return null;
    }

    function toHsl(c) {
        var r = c.r / 255, g = c.g / 255, b = c.b / 255;
        var max = Math.max(r, g, b), min = Math.min(r, g, b), l = (max + min) / 2, h = 0, s = 0;
        if (max !== min) {
            var d = max - min;
            s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
            h = max === r ? (g - b) / d + (g < b ? 6 : 0) : max === g ? (b - r) / d + 2 : (r - g) / d + 4;
            h *= 60;
        }
        return { h: h, s: s, l: l, a: c.a, chroma: max - min };
    }

    function hsl(h, s, l, a) {
        var str = Math.round(h) + ' ' + Math.round(s * 100) + '% ' + Math.round(l * 100) + '%';
        return a < 1 ? 'hsl(' + str + ' / ' + (+a.toFixed(3)) + ')' : 'hsl(' + str + ')';
    }

    // Light background → dark surface. Neutrals use theme variables so the
    // Apple style can retune them; tints keep their hue.
    function mapBg(str) {
        var c = parseColor(str); if (!c || c.a === 0) return null;
        var x = toHsl(c);
        if (x.l < 0.82) return null;
        if (x.chroma < 0.05 || x.l > 0.985) {
            var v = x.l >= 0.985 ? 'var(--pt-surface)' : x.l >= 0.93 ? 'var(--pt-surface-2)' : 'var(--pt-surface-3)';
            return c.a < 1 ? 'color-mix(in srgb, ' + v + ' ' + Math.round(c.a * 100) + '%, transparent)' : v;
        }
        return hsl(x.h, Math.min(x.s, 0.55) * 0.6, 0.15 + (1 - x.l) * 0.25, c.a);
    }

    // Dark / mid-grey text → light text (hue kept for coloured text).
    function mapText(str) {
        var c = parseColor(str); if (!c) return null;
        var x = toHsl(c);
        if (x.l <= 0.22) return 'var(--pt-text)';                 // near-black, even if slightly tinted
        if (x.chroma < 0.16) {                                        // greys / slates
            if (x.l <= 0.32) return 'var(--pt-text)';
            if (x.l <= 0.55) return 'var(--pt-text-muted)';
            return null;
        }
        if (x.l <= 0.42) return hsl(x.h, Math.min(x.s, 0.75), 0.72, c.a);  // dark coloured text
        return null;
    }

    // Light border → dark border.
    function mapBorder(str) {
        var c = parseColor(str); if (!c || c.a === 0) return null;
        var x = toHsl(c);
        if (x.l < 0.78) return null;
        if (x.chroma < 0.08) return 'var(--pt-border)';
        return hsl(x.h, Math.min(x.s, 0.5) * 0.5, 0.26, c.a);
    }

    var COLOR_TOKEN = /#[0-9a-fA-F]{3,8}\b|rgba?\([^)]*\)|\b(?:white|whitesmoke|ghostwhite|snow|aliceblue|ivory|azure|mintcream|honeydew|floralwhite|seashell|linen|lavender|gainsboro|lightgr[ae]y)\b/g;

    // Gradients made only of light colours (e.g. a white→grey card header).
    function mapGradient(str) {
        if (!/gradient\(/i.test(str)) return null;
        var tokens = str.match(COLOR_TOKEN);
        if (!tokens || !tokens.length) return null;
        for (var i = 0; i < tokens.length; i++) {
            var c = parseColor(tokens[i]);
            if (!c || (c.a > 0.05 && toHsl(c).l < 0.82)) return null;
        }
        return str.replace(COLOR_TOKEN, function (t) { return mapBg(t) || t; });
    }

    // Returns [[property, value], …] dark replacements for one declaration block.
    function darkDecls(style) {
        var out = [];
        var bgc = style.getPropertyValue('background-color');
        var bg = style.getPropertyValue('background');
        var bgi = style.getPropertyValue('background-image');
        var v;
        if (bgc && (v = mapBg(bgc))) out.push(['background-color', v, style.getPropertyPriority('background-color')]);
        else if (bg && !bgc) {
            var single = bg.replace(/\s*!important\s*/, '').trim();
            if ((v = mapBg(single))) out.push(['background-color', v, style.getPropertyPriority('background')]);
            else if ((v = mapGradient(single))) out.push(['background-image', v, style.getPropertyPriority('background')]);
        }
        if (bgi && (v = mapGradient(bgi))) out.push(['background-image', v, style.getPropertyPriority('background-image')]);
        var col = style.getPropertyValue('color');
        if (col && (v = mapText(col))) out.push(['color', v, style.getPropertyPriority('color')]);
        ['border-color', 'border-top-color', 'border-right-color', 'border-bottom-color', 'border-left-color'].forEach(function (p) {
            var val = style.getPropertyValue(p);
            if (val && (v = mapBorder(val))) out.push([p, v, style.getPropertyPriority(p)]);
        });
        return out;
    }

    /* ── stylesheet pass ───────────────────────────────────────────────── */
    var SKIP_SHEET = /bootstrap\.min\.css|app\.min\.css|icons\.min\.css|portal-theme\.css|cb-ui\.css/;
    var SKIP_SELECTOR = /data-bs-theme|data-sidebar|data-topbar|data-ui|\.app-menu|#navbar-nav|\.navbar-menu|\.navbar-brand-box|#scrollbar|sidebar-|::-webkit-scrollbar|\.swal2-icon|\.text-white|\.bg-dark|\.btn-light|@page/i;

    function scopeSelector(sel) {
        return sel.split(',').map(function (part) {
            part = part.trim();
            if (!part) return null;
            if (/^(html|:root)\b/.test(part)) return part.replace(/^(html|:root)/, 'html[data-bs-theme=dark]');
            return 'html[data-bs-theme=dark] ' + part;
        }).filter(Boolean).join(',\n');
    }

    function collectRules(rules, out) {
        for (var i = 0; i < rules.length; i++) {
            var r = rules[i];
            if (r.type === 4 /* media */) {
                if (/print/.test(r.conditionText || (r.media && r.media.mediaText) || '')) continue;
                var inner = [];
                collectRules(r.cssRules, inner);
                if (inner.length) out.push('@media ' + r.media.mediaText + '{' + inner.join('\n') + '}');
                continue;
            }
            if (r.type === 12 /* supports */ && r.cssRules) { collectRules(r.cssRules, out); continue; }
            if (r.type !== 1 || !r.selectorText || SKIP_SELECTOR.test(r.selectorText)) continue;
            var decls = darkDecls(r.style);
            if (!decls.length) continue;
            out.push(scopeSelector(r.selectorText) + '{' + decls.map(function (d) {
                return d[0] + ':' + d[1] + (d[2] ? ' !important' : '');
            }).join(';') + '}');
        }
    }

    var seenSheets = (typeof WeakSet !== 'undefined') ? new WeakSet() : null;
    function buildSheet() {
        var out = [];
        for (var i = 0; i < document.styleSheets.length; i++) {
            var sh = document.styleSheets[i];
            if (seenSheets && seenSheets.has(sh)) continue;
            if (sh.ownerNode && (sh.ownerNode.id === 'pt-dark-adapt' || sh.ownerNode.hasAttribute('data-pt-skip'))) continue;
            if (sh.href && SKIP_SHEET.test(sh.href)) continue;
            var rules;
            try { rules = sh.cssRules; } catch (e) { continue; } // cross-origin (CDN) sheets
            if (!rules) continue;
            if (seenSheets) seenSheets.add(sh);
            collectRules(rules, out);
        }
        if (!out.length) return;
        var el = document.createElement('style');
        el.setAttribute('data-pt-generated', '');
        el.id = 'pt-dark-adapt';
        el.textContent = out.join('\n');
        (document.head || root).appendChild(el);
    }

    /* ── inline style="" pass ──────────────────────────────────────────── */
    var touched = [];
    var INLINE_PROPS = ['background-color', 'background', 'background-image', 'color',
        'border-color', 'border-top-color', 'border-right-color', 'border-bottom-color', 'border-left-color'];

    function adaptElement(el) {
        if (!el.style || !el.style.length) return;
        if (el.closest && el.closest('[data-pt-skip], .app-menu, svg')) return;
        var current = el.getAttribute('style') || '';
        if (el.__ptApplied === current) return;          // our own write
        var has = false;
        for (var i = 0; i < INLINE_PROPS.length; i++) if (el.style.getPropertyValue(INLINE_PROPS[i])) { has = true; break; }
        if (!has) return;
        var decls = darkDecls(el.style);
        if (!decls.length) return;
        if (el.__ptOrig === undefined) touched.push(el);
        el.__ptOrig = current;
        decls.forEach(function (d) {
            if (d[0] === 'background-color' && el.style.getPropertyValue('background') && !/gradient/i.test(el.style.getPropertyValue('background'))) {
                el.style.removeProperty('background');
            }
            el.style.setProperty(d[0], d[1], d[2]);
        });
        el.__ptApplied = el.getAttribute('style') || '';
    }

    function adaptTree(node) {
        if (!node || node.nodeType !== 1) return;
        if (node.hasAttribute('style')) adaptElement(node);
        var list = node.querySelectorAll('[style]');
        for (var i = 0; i < list.length; i++) adaptElement(list[i]);
    }

    function restoreInline() {
        touched.forEach(function (el) {
            if (el.__ptOrig !== undefined) {
                if ((el.getAttribute('style') || '') === el.__ptApplied) el.setAttribute('style', el.__ptOrig);
                el.__ptOrig = undefined; el.__ptApplied = undefined;
            }
        });
        touched = [];
    }

    var observer = null, pending = [], scheduled = false, sheetDirty = false;
    function flush() {
        scheduled = false;
        if (!isDark()) { pending = []; return; }
        if (sheetDirty) { sheetDirty = false; buildSheet(); }
        var batch = pending; pending = [];
        batch.forEach(function (n) { n.nodeType === 1 && (n.isConnected === false || adaptTree(n)); });
    }
    function queue(n) {
        pending.push(n);
        if (!scheduled) { scheduled = true; (window.requestAnimationFrame || setTimeout)(flush); }
    }
    function observe() {
        if (observer || !window.MutationObserver || !document.body) return;
        observer = new MutationObserver(function (muts) {
            muts.forEach(function (m) {
                if (m.type === 'attributes') { queue(m.target); return; }
                m.addedNodes.forEach(function (n) {
                    if (n.nodeType !== 1) return;
                    if (n.tagName === 'STYLE' || (n.tagName === 'LINK' && /stylesheet/i.test(n.rel))) {
                        if (n.id !== 'pt-dark-adapt') { sheetDirty = true; queue(document.body); }
                        if (n.tagName === 'LINK') n.addEventListener('load', function () { sheetDirty = true; queue(document.body); });
                        return;
                    }
                    queue(n);
                });
            });
        });
        observer.observe(document.documentElement, { childList: true, subtree: true, attributes: true, attributeFilter: ['style'] });
    }

    /* ── apply ─────────────────────────────────────────────────────────── */
    var sheetBuilt = false;
    function isDark() { return root.getAttribute('data-bs-theme') === 'dark'; }

    function runAdapter() {
        if (!document.body) return;
        if (isDark()) {
            if (!sheetBuilt) { sheetBuilt = true; buildSheet(); }
            adaptTree(document.body);
            observe();
        } else {
            restoreInline();
        }
    }

    function apply() {
        var mode = read(KEY_MODE, 'light');
        var ui = read(KEY_UI, 'default') === 'apple' ? 'apple' : 'default';
        var scheme = schemeFor(mode);
        root.setAttribute('data-bs-theme', scheme);
        root.setAttribute('data-topbar', scheme === 'dark' ? 'dark' : 'light');
        root.setAttribute('data-ui', ui);
        root.style.colorScheme = scheme;

        // ApexCharts picks this up for charts created afterwards.
        window.Apex = window.Apex || {};
        window.Apex.theme = Object.assign({}, window.Apex.theme, { mode: scheme });
        window.Apex.chart = Object.assign({}, window.Apex.chart, { background: 'transparent' });

        api.mode = mode; api.ui = ui; api.scheme = scheme;
        runAdapter();
        try {
            document.dispatchEvent(new CustomEvent('portal-theme-change', { detail: { mode: mode, ui: ui, scheme: scheme } }));
        } catch (e) {}
    }

    var api = {
        mode: 'light', ui: 'default', scheme: 'light',
        setMode: function (m) { write(KEY_MODE, ['light', 'dark', 'auto'].indexOf(m) > -1 ? m : 'light'); apply(); },
        setUi: function (u) { write(KEY_UI, u === 'apple' ? 'apple' : 'default'); apply(); },
        apply: apply,
        refresh: function () { if (isDark()) { sheetDirty = true; queue(document.body); } }
    };
    window.PortalTheme = api;

    apply();                                       // before first paint (attributes only)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', runAdapter);
    }
    window.addEventListener('load', function () { if (isDark()) { sheetDirty = true; queue(document.body); } });
    if (mq) {
        var onSys = function () { if (read(KEY_MODE, 'light') === 'auto') apply(); };
        mq.addEventListener ? mq.addEventListener('change', onSys) : mq.addListener(onSys);
    }
    window.addEventListener('storage', function (e) { if (e.key === KEY_MODE || e.key === KEY_UI) apply(); });
})();
