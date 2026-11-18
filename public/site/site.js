/* Farm website interactions (from csskabba-portal's school website): sticky header, mobile menu,
   hero slider, counters, reveal-on-scroll, gallery lightbox, back-to-top — plus the animation layer
   (scroll progress, word-by-word headings, staggered lists, parallax, card tilt, button ripples,
   floating eggs) and the live farm numbers refresh. No libraries. */
(function () {
  'use strict';
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));

  // Header shadow on scroll + back to top
  const header = $('.header');
  const toTop = $('.to-top');
  const onScroll = () => {
    const y = window.scrollY;
    if (header) header.classList.toggle('scrolled', y > 10);
    if (toTop) toTop.classList.toggle('show', y > 600);
  };
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();
  if (toTop) toTop.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));

  // Mobile menu
  const nav = $('.nav');
  const scrim = $('.scrim');
  const setNav = (open) => {
    if (!nav) return;
    nav.classList.toggle('open', open);
    if (scrim) scrim.classList.toggle('show', open);
    document.body.style.overflow = open ? 'hidden' : '';
  };
  $$('.burger').forEach((b) => b.addEventListener('click', () => setNav(true)));
  $$('.nav-close, .scrim').forEach((b) => b.addEventListener('click', () => setNav(false)));
  $$('.nav > .item.has-drop > a').forEach((a) =>
    a.addEventListener('click', (e) => {
      if (window.innerWidth > 1060) return;
      e.preventDefault();
      a.parentElement.classList.toggle('open');
    })
  );

  // Hero slider
  const slides = $$('.hero .slide');
  if (slides.length > 1) {
    const dots = $$('.hero .dots button');
    let i = 0;
    let timer;
    const show = (n) => {
      i = (n + slides.length) % slides.length;
      slides.forEach((s, k) => s.classList.toggle('on', k === i));
      dots.forEach((d, k) => d.classList.toggle('on', k === i));
    };
    const play = () => { clearInterval(timer); timer = setInterval(() => show(i + 1), 6500); };
    dots.forEach((d, k) => d.addEventListener('click', () => { show(k); play(); }));
    play();
  }

  // Reveal on scroll + counters
  const count = (el) => {
    const target = parseInt(el.dataset.count, 10) || 0;
    const suffix = el.dataset.suffix || '';
    const start = performance.now();
    const dur = 1600;
    const step = (t) => {
      const p = Math.min(1, (t - start) / dur);
      const eased = 1 - Math.pow(1 - p, 3);
      el.textContent = Math.round(target * eased).toLocaleString() + (p === 1 ? suffix : '');
      if (p < 1) requestAnimationFrame(step); else el.classList.add('done');
    };
    requestAnimationFrame(step);
  };
  if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver((entries) => {
      entries.forEach((e) => {
        if (!e.isIntersecting) return;
        e.target.classList.add('in');
        if (e.target.dataset.count !== undefined) count(e.target);
        io.unobserve(e.target);
      });
    }, { threshold: 0.15 });
    $$('.reveal, [data-count], [data-stagger], .split-words').forEach((el) => io.observe(el));
  } else {
    $$('.reveal, [data-stagger], .split-words').forEach((el) => el.classList.add('in'));
    $$('[data-count]').forEach(count);
  }

  // Lightbox for gallery links: <a data-lightbox="group" href="big.jpg" data-caption="...">
  const groups = {};
  $$('a[data-lightbox]').forEach((a) => {
    const g = a.dataset.lightbox;
    (groups[g] = groups[g] || []).push(a);
  });
  if (Object.keys(groups).length) {
    const box = document.createElement('div');
    box.className = 'lightbox';
    box.innerHTML = '<button class="close" aria-label="Close"><i class="ri-close-line"></i></button>' +
      '<button class="prev" aria-label="Previous"><i class="ri-arrow-left-s-line"></i></button>' +
      '<img alt=""><div class="cap"></div>' +
      '<button class="next" aria-label="Next"><i class="ri-arrow-right-s-line"></i></button>';
    document.body.appendChild(box);
    const img = $('img', box);
    const cap = $('.cap', box);
    let list = [];
    let at = 0;
    const open = (g, k) => { list = groups[g]; at = k; render(); box.classList.add('show'); document.body.style.overflow = 'hidden'; };
    const close = () => { box.classList.remove('show'); document.body.style.overflow = ''; };
    const render = () => { const a = list[at]; img.src = a.href; cap.textContent = a.dataset.caption || ''; };
    const move = (d) => { at = (at + d + list.length) % list.length; render(); };
    Object.entries(groups).forEach(([g, items]) => items.forEach((a, k) => a.addEventListener('click', (e) => { e.preventDefault(); open(g, k); })));
    $('.close', box).addEventListener('click', close);
    $('.prev', box).addEventListener('click', () => move(-1));
    $('.next', box).addEventListener('click', () => move(1));
    box.addEventListener('click', (e) => { if (e.target === box) close(); });
    document.addEventListener('keydown', (e) => {
      if (!box.classList.contains('show')) return;
      if (e.key === 'Escape') close();
      if (e.key === 'ArrowLeft') move(-1);
      if (e.key === 'ArrowRight') move(1);
    });
    let sx = null;
    box.addEventListener('touchstart', (e) => { sx = e.touches[0].clientX; }, { passive: true });
    box.addEventListener('touchend', (e) => { if (sx === null) return; const dx = e.changedTouches[0].clientX - sx; if (Math.abs(dx) > 50) move(dx < 0 ? 1 : -1); sx = null; });
  }
})();


/* ── animation layer ─────────────────────────────────────────────── */
(function () {
  'use strict';
  const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));
  const still = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // Scroll progress bar
  const bar = document.querySelector('.progress-bar');
  const onScroll = () => {
    const h = document.documentElement;
    const p = h.scrollTop / Math.max(1, h.scrollHeight - h.clientHeight);
    if (bar) bar.style.setProperty('--p', p.toFixed(4));
  };
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  // Headings that rise word by word: <h1 class="split-words">
  $$('.split-words').forEach((el) => {
    if (el.dataset.split) return;
    el.dataset.split = '1';
    const words = el.textContent.trim().split(/\s+/);
    el.innerHTML = words.map((w, i) => `<span class="w"><span style="transition-delay:${(i * 0.07).toFixed(2)}s">${w.replace(/[&<>]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;' }[c]))}</span></span>`).join(' ');
  });

  // Stagger: children of [data-stagger] come in one after another
  $$('[data-stagger]').forEach((el) => {
    Array.from(el.children).forEach((c, i) => { c.style.transitionDelay = (i * 0.09).toFixed(2) + 's'; });
  });

  if (still) return;

  // Parallax: [data-parallax="0.2"] moves at a fraction of the scroll speed
  const par = $$('[data-parallax]');
  if (par.length) {
    let ticking = false;
    const run = () => {
      const y = window.scrollY;
      par.forEach((el) => {
        const r = el.getBoundingClientRect();
        if (r.bottom < -200 || r.top > window.innerHeight + 200) return;
        el.style.transform = `translate3d(0, ${(y * parseFloat(el.dataset.parallax || 0.2)).toFixed(1)}px, 0)`;
      });
      ticking = false;
    };
    window.addEventListener('scroll', () => { if (!ticking) { ticking = true; requestAnimationFrame(run); } }, { passive: true });
    run();
  }

  // 3D tilt on cards: [data-tilt]
  if (window.matchMedia('(hover: hover)').matches) {
    $$('[data-tilt]').forEach((card) => {
      card.addEventListener('mousemove', (e) => {
        const r = card.getBoundingClientRect();
        const x = (e.clientX - r.left) / r.width - 0.5;
        const y = (e.clientY - r.top) / r.height - 0.5;
        card.style.transform = `perspective(900px) rotateY(${(x * 8).toFixed(2)}deg) rotateX(${(-y * 8).toFixed(2)}deg) translateY(-6px)`;
      });
      card.addEventListener('mouseleave', () => { card.style.transform = ''; });
    });
  }

  // Ripple on buttons
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.btn');
    if (!btn) return;
    const r = btn.getBoundingClientRect();
    const s = document.createElement('span');
    const d = Math.max(r.width, r.height);
    s.className = 'ripple';
    s.style.cssText = `width:${d}px;height:${d}px;left:${e.clientX - r.left - d / 2}px;top:${e.clientY - r.top - d / 2}px`;
    btn.appendChild(s);
    setTimeout(() => s.remove(), 700);
  });

  // Floating eggs in the photo-less hero scene
  $$('.hero .scene').forEach((scene) => {
    for (let i = 0; i < 9; i++) {
      const egg = document.createElement('span');
      egg.className = 'egg';
      const size = 22 + Math.random() * 26;
      egg.style.cssText = `left:${(Math.random() * 100).toFixed(1)}%;width:${size.toFixed(0)}px;height:${(size * 1.3).toFixed(0)}px;animation-duration:${(14 + Math.random() * 14).toFixed(1)}s;animation-delay:${(-Math.random() * 20).toFixed(1)}s`;
      scene.appendChild(egg);
    }
  });
})();

/* ── live farm numbers: refresh the counters every few minutes ───────── */
(function () {
  'use strict';
  const box = document.querySelector('[data-live-url]');
  if (!box || !window.fetch) return;
  const refresh = () => fetch(box.dataset.liveUrl, { headers: { Accept: 'application/json' } })
    .then((r) => (r.ok ? r.json() : null))
    .then((d) => {
      if (!d || !d.stats) return;
      d.stats.forEach((s) => {
        const el = document.querySelector(`[data-stat-label="${CSS.escape(s.label)}"]`);
        if (!el || parseInt(el.dataset.count, 10) === s.value) return;
        el.dataset.count = s.value;
        el.textContent = Number(s.value).toLocaleString() + (s.suffix || '');
        el.classList.remove('done'); void el.offsetWidth; el.classList.add('done');
      });
    }).catch(() => {});
  setInterval(refresh, 5 * 60 * 1000);
})();
