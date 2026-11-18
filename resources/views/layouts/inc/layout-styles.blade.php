{{-- Layout styles from csskabba-portal (sidebar, footer user card, active states, animations, topbar, Spotlight). --}}
    <style>
        /* =====================================================
           NPROGRESS
           ===================================================== */
        #nprogress .bar  { background: #4f8ef7 !important; height: 3px !important; box-shadow: 0 0 8px rgba(79,142,247,.6) !important; }
        #nprogress .peg  { box-shadow: none !important; }
        #nprogress .spinner { display: none !important; }

        /* =====================================================
           PAGINATION
           ===================================================== */
        .pagination-wrap .page-item { margin: 0 5px; }
        .pagination-wrap .page-link { padding: 5px 10px; }
        .pagination-wrap .active .page-link { background-color: #007bff; color: white; }
        .pagination-wrap .disabled .page-link { pointer-events: none; opacity: 0.5; }

        /* =====================================================
           SPINNER
           ===================================================== */
        .spin { animation: spin 1s linear infinite; }
        @keyframes spin { 0%{transform:rotate(0deg)} 100%{transform:rotate(360deg)} }

        /* =====================================================
           MISC
           ===================================================== */
        .form-check-input:checked { background-color: #405189; border-color: #405189; }
        .swal2-toast { font-size: 14px !important; }
        .swal2-container.swal2-top-end { top: 70px !important; }
        .table tbody tr { transition: background-color .15s ease; }
        .table tbody tr:hover { background-color: rgba(67,97,238,.05); }
        .modal.fade  .modal-dialog { transform: translate(0,-50px); transition: transform .3s ease-out; }
        .modal.show  .modal-dialog { transform: translate(0,0); }

        /* =====================================================
           SIDEBAR LAYOUT — flex column so footer pins to bottom
           ===================================================== */
        .app-menu {
            position: fixed;
            top: 0; left: 0; bottom: 0;
            width: 250px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
        }
        #scrollbar {
            flex: 1;
            overflow-y: auto;
            scrollbar-width: thin;
            padding-bottom: 8px;
        }
        #scrollbar::-webkit-scrollbar { width: 4px; }
        #scrollbar::-webkit-scrollbar-track  { background: rgba(255,255,255,.05); border-radius: 4px; }
        #scrollbar::-webkit-scrollbar-thumb  { background: rgba(255,255,255,.2);  border-radius: 4px; }
        #scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,.3); }
        .bg-light::-webkit-scrollbar { width: 6px; }
        .bg-light::-webkit-scrollbar-track  { background: #f1f1f1; border-radius: 10px; }
        .bg-light::-webkit-scrollbar-thumb  { background: #888; border-radius: 10px; }
        .bg-light::-webkit-scrollbar-thumb:hover { background: #555; }
        .navbar-menu .container-fluid { padding: 0; }
        #navbar-nav { padding-bottom: 8px; }

        /* =====================================================
           SIDEBAR LOGOUT FOOTER — pushed down with margin-top auto
           ===================================================== */
        .sidebar-footer {
            flex-shrink: 0;
            border-top: 1px solid rgba(255,255,255,.1);
            padding: 20px 16px 24px;
            margin-top: auto;
            background: inherit;
        }
        .sidebar-footer-user {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
        }
        .sidebar-footer-user img {
            width: 36px; height: 36px;
            border-radius: 50%; object-fit: cover;
            border: 2px solid rgba(255,255,255,.18);
            flex-shrink: 0;
        }
        .sidebar-footer-avatar-initials {
            width: 36px; height: 36px;
            border-radius: 50%;
            background: #405189;
            color: #fff;
            font-size: 13px; font-weight: 600;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            border: 2px solid rgba(255,255,255,.18);
        }
        .sidebar-footer-user-info { min-width: 0; flex: 1; }
        .sidebar-footer-user-name {
            font-size: 13px; font-weight: 600; color: #fff;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .sidebar-footer-user-role {
            font-size: 11px; color: rgba(255,255,255,.45);
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .sidebar-logout-btn {
            display: flex; align-items: center; gap: 9px;
            width: 100%; padding: 9px 14px;
            border-radius: 8px;
            background: rgba(239,68,68,.12);
            border: 1px solid rgba(239,68,68,.22);
            color: #f87171; font-size: 13px; font-weight: 500;
            cursor: pointer; text-decoration: none;
            transition: background .2s, border-color .2s, color .2s;
        }
        .sidebar-logout-btn:hover {
            background: rgba(239,68,68,.24);
            border-color: rgba(239,68,68,.45);
            color: #fca5a5;
        }
        .sidebar-logout-btn i { font-size: 17px; flex-shrink: 0; }

        /* =====================================================
           SIDEBAR NAV ACTIVE STATES
           ===================================================== */
        #navbar-nav .menu-dropdown { overflow: hidden; }
        #navbar-nav .nav-link.menu-link .ri-arrow-down-s-line { transition: transform .25s ease; display: inline-block; }
        #navbar-nav .nav-link.menu-link[aria-expanded="true"] .ri-arrow-down-s-line { transform: rotate(180deg); }

        #navbar-nav .nav-link.menu-link.nav-active-parent {
            color: #fff !important;
            background: rgba(79,142,247,.18) !important;
            border-left: 3px solid #4f8ef7;
            padding-left: calc(1.3rem - 3px);
        }
        #navbar-nav .nav-link.menu-link.nav-active-parent i { color: #4f8ef7 !important; }

        #navbar-nav .nav-sm .nav-link.nav-active-child { color: #7eb8fb !important; font-weight: 500; }
        #navbar-nav .nav-sm .nav-link.nav-active-child::before {
            content: ''; display: inline-block;
            width: 5px; height: 5px; border-radius: 50%;
            background: #4f8ef7; margin-right: 8px;
            box-shadow: 0 0 0 3px rgba(79,142,247,.25);
            vertical-align: middle; flex-shrink: 0;
            animation: dotPop .25s ease;
        }
        @keyframes dotPop { from{transform:scale(0);opacity:0} to{transform:scale(1);opacity:1} }

        /* =====================================================
           SIDEBAR HOVER TRANSITIONS + RIPPLE
           ===================================================== */
        #navbar-nav .nav-link { position: relative; overflow: hidden; transition: color .18s, background-color .18s, padding-left .18s; }
        .nav-ripple {
            position: absolute; border-radius: 50%;
            background: rgba(255,255,255,.18);
            transform: scale(0); animation: ripple-anim .55s linear;
            pointer-events: none; z-index: 0;
        }
        @keyframes ripple-anim { to{transform:scale(5);opacity:0} }

        /* =====================================================
           BACK TO TOP
           ===================================================== */
        #back-to-top { opacity:0; visibility:hidden; transform:translateY(12px); transition:opacity .3s,transform .3s,visibility .3s; }
        #back-to-top.show { opacity:1; visibility:visible; transform:translateY(0); }
        #back-to-top:hover { transform:translateY(-3px) !important; }

        /* =====================================================
           PAGE FADE-IN
           ===================================================== */
        .page-content { animation: pageFadeIn .35s ease; }
        @keyframes pageFadeIn { from{opacity:0;transform:translateY(6px)} to{opacity:1;transform:translateY(0)} }

        /* =====================================================
           TOPBAR
           ===================================================== */
        #page-topbar .header-item { transition: color .2s ease, background-color .2s ease; }
        .header-profile-user-enhanced { transition: transform .25s ease, box-shadow .25s ease; }
        .header-profile-user-enhanced:hover { transform: scale(1.07); box-shadow: 0 0 0 3px rgba(79,142,247,.35) !important; }

        .topbar-user .dropdown-menu {
            min-width: 220px;
            z-index: 9999 !important;
            border-radius: 12px;
            overflow: hidden;
            margin-top: 8px !important;
            box-shadow: 0 8px 32px rgba(0,0,0,.18);
        }

        /* =====================================================
           MOBILE SIDEBAR
           ===================================================== */
        .vertical-overlay {
            position: fixed;
            inset: 0;
            z-index: 999;
            background: rgba(0,0,0,.45);
            display: none;
        }
        body.vertical-sidebar-enable .vertical-overlay { display: block; }

        @media (max-width: 1024.98px) {
            .app-menu {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                box-shadow: none;
            }
            body.vertical-sidebar-enable .app-menu {
                transform: translateX(0);
                box-shadow: 4px 0 24px rgba(0,0,0,.35);
            }
        }
        @media (min-width: 1025px) {
            body.vertical-sidebar-enable .app-menu { transform: none; }
        }

        /* =====================================================
           FINANCE MODULE
           ===================================================== */
        .finance-stat-card { background: linear-gradient(135deg,#667eea 0%,#764ba2 100%); border-radius: 12px; padding: 20px; color: white; transition: transform .3s,box-shadow .3s; }
        .finance-stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(102,126,234,.35); }
        .payment-progress { height: 8px; border-radius: 4px; background: #e2e8f0; }
        .payment-progress-bar { height: 100%; border-radius: 4px; transition: width .4s ease; }
        .scholarship-card { border-left: 4px solid #10b981; transition: transform .2s,box-shadow .2s; }
        .scholarship-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.1); }
        .payroll-table th { background: #1e293b; color: white; }

        /* =====================================================
           CARD / BUTTON MICRO-INTERACTIONS
           ===================================================== */
        .card { transition: box-shadow .25s, transform .25s; }
        .card:hover { box-shadow: 0 6px 20px rgba(0,0,0,.08); }
        .btn  { transition: transform .15s, box-shadow .15s; }
        .btn:active { transform: scale(.97); }

        /* =====================================================
           SIDEBAR STAGGER
           ===================================================== */
        #navbar-nav > li { animation: navItemFadeIn .4s ease both; }
        #navbar-nav > li:nth-child(1)  { animation-delay:.02s }
        #navbar-nav > li:nth-child(2)  { animation-delay:.04s }
        #navbar-nav > li:nth-child(3)  { animation-delay:.06s }
        #navbar-nav > li:nth-child(4)  { animation-delay:.08s }
        #navbar-nav > li:nth-child(5)  { animation-delay:.10s }
        #navbar-nav > li:nth-child(6)  { animation-delay:.12s }
        #navbar-nav > li:nth-child(7)  { animation-delay:.14s }
        #navbar-nav > li:nth-child(8)  { animation-delay:.16s }
        #navbar-nav > li:nth-child(9)  { animation-delay:.18s }
        #navbar-nav > li:nth-child(10) { animation-delay:.20s }
        #navbar-nav > li:nth-child(11) { animation-delay:.22s }
        #navbar-nav > li:nth-child(12) { animation-delay:.24s }
        #navbar-nav > li:nth-child(n+13) { animation-delay:.26s }
        @keyframes navItemFadeIn { from{opacity:0;transform:translateX(-8px)} to{opacity:1;transform:translateX(0)} }

        /* =====================================================
           DROPDOWN ANIMATIONS
           ===================================================== */
        .dropdown-menu { animation: dropdownFadeIn .25s cubic-bezier(.4,0,.2,1); transform-origin: top right; }
        @keyframes dropdownFadeIn { from{opacity:0;transform:translateY(-10px) scale(.97)} to{opacity:1;transform:translateY(0) scale(1)} }

        /* =====================================================
           PRINT
           ===================================================== */
        @media print { .no-print{display:none!important} body{padding:0;margin:0} }

        /* =====================================================
           SPOTLIGHT SEARCH — APPLE STYLE DARK
           ===================================================== */
        @keyframes spotlightOverlayFadeIn  { from{background:rgba(0,0,0,.2);backdrop-filter:blur(0)} to{background:rgba(0,0,0,.85);backdrop-filter:blur(20px)} }
        @keyframes spotlightOverlayFadeOut { from{background:rgba(0,0,0,.85);backdrop-filter:blur(20px)} to{background:rgba(0,0,0,.2);backdrop-filter:blur(0)} }
        @keyframes spotlightModalBounceIn  { 0%{opacity:0;transform:translateY(-40px) scale(.96)} 40%{opacity:.9;transform:translateY(8px) scale(1.01)} 70%{opacity:.95;transform:translateY(-2px) scale(.99)} 100%{opacity:1;transform:translateY(0) scale(1)} }
        @keyframes spotlightModalFadeOut   { 0%{opacity:1;transform:translateY(0) scale(1)} 100%{opacity:0;transform:translateY(-20px) scale(.95)} }
        @keyframes resultBounceIn { 0%{opacity:0;transform:translateX(-16px) scale(.96)} 60%{opacity:.8;transform:translateX(4px) scale(1.01)} 100%{opacity:1;transform:translateX(0) scale(1)} }
        @keyframes resultGlowPulse { 0%{box-shadow:0 0 0 0 rgba(79,142,247,.5)} 70%{box-shadow:0 0 0 8px rgba(79,142,247,0)} 100%{box-shadow:0 0 0 0 rgba(79,142,247,0)} }
        @keyframes loadingSpin    { 0%{transform:rotate(0)} 100%{transform:rotate(360deg)} }
        @keyframes historySlideIn { from{opacity:0;transform:translateX(-12px)} to{opacity:1;transform:translateX(0)} }
        @keyframes typingDot      { 0%,60%,100%{transform:translateY(0);opacity:.4} 30%{transform:translateY(-5px);opacity:1} }
        @keyframes suggestFadeIn  { from{opacity:0;transform:translateY(4px)} to{opacity:1;transform:translateY(0)} }

        .spotlight-result-item { animation:resultBounceIn .35s cubic-bezier(.34,1.3,.64,1) forwards; opacity:0; }
        .spotlight-result-item:nth-child(1){animation-delay:.00s}
        .spotlight-result-item:nth-child(2){animation-delay:.03s}
        .spotlight-result-item:nth-child(3){animation-delay:.06s}
        .spotlight-result-item.top-match { animation:resultBounceIn .4s cubic-bezier(.34,1.3,.64,1) forwards,resultGlowPulse .6s ease .3s; border-left:3px solid #4f8ef7; background:linear-gradient(90deg,rgba(79,142,247,.12) 0%,transparent 100%); }
        .spotlight-history-item { animation:historySlideIn .25s ease forwards; opacity:0; animation-fill-mode:forwards; }
        .spotlight-history-item:nth-child(1){animation-delay:.00s}
        .spotlight-history-item:nth-child(2){animation-delay:.04s}
        .spotlight-history-item:nth-child(3){animation-delay:.08s}
        .spotlight-suggest-chip { animation:suggestFadeIn .2s ease forwards; opacity:0; }
        .spotlight-suggest-chip:nth-child(1){animation-delay:.00s}
        .spotlight-suggest-chip:nth-child(2){animation-delay:.04s}
        .spotlight-suggest-chip:nth-child(3){animation-delay:.08s}
        .spotlight-suggest-chip:nth-child(4){animation-delay:.12s}
        .spotlight-suggest-chip:nth-child(5){animation-delay:.16s}
        .typing-dot { display:inline-block; animation:typingDot 1.4s infinite ease-in-out; }
        .typing-dot:nth-child(2){animation-delay:.2s}
        .typing-dot:nth-child(3){animation-delay:.4s}

        .search-tooltip { position:absolute; bottom:-38px; left:0; background:#1c1c1e; color:#fff; font-size:12px; padding:6px 12px; border-radius:10px; white-space:nowrap; opacity:0; transition:opacity .2s; pointer-events:none; z-index:100; backdrop-filter:blur(8px); border:0.5px solid rgba(255,255,255,0.1); font-weight:500; }
        .search-tooltip kbd { background:rgba(255,255,255,0.15); color:#fff; padding:2px 8px; border-radius:6px; font-size:11px; margin:0 2px; }
        kbd { background:rgba(0,0,0,0.08); border-radius:6px; padding:2px 8px; font-size:11px; font-family:monospace; }

        /* Spotlight trigger */
        #spotlight-trigger { background:rgba(0,0,0,0.6) !important; border:1px solid rgba(255,255,255,0.15) !important; border-radius:12px !important; padding:8px 16px !important; min-width:260px !important; backdrop-filter:blur(8px) !important; }
        #spotlight-trigger span { color:#ffffff !important; opacity:0.9 !important; font-weight:500 !important; }
        #spotlight-trigger kbd { background:rgba(255,255,255,0.2) !important; color:#ffffff !important; border:none !important; }

        /* Ensure modals don't conflict */
        .modal { z-index: 1055 !important; }
        .modal-backdrop { z-index: 1050 !important; }
    </style>
