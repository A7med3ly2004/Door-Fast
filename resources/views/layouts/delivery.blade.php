<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('DF_logo_2026.PNG') }}">
    <title>لوحة الكابتن - DoorFast</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- ── Core CDNs ── --}}
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --primary: #f59e0b;
            /* Yellow */
            --secondary: #dc2626;
            /* Red */
            --bg-color: #f3f4f6;
            --sidebar-bg: #ffffff;
            --text-dark: #1f2937;
            --text-muted: #6b7280;
            --success: #10b981;
            --border-color: #e5e7eb;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Cairo', sans-serif;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-dark);
            height: 100vh;
            display: flex;
            overflow: hidden;
        }

        /* Sidebar structure */
        .sidebar {
            width: 250px;
            background-color: var(--sidebar-bg);
            border-left: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            z-index: 10;
        }

        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid var(--border-color);
        }

        .sidebar-header img {
            max-width: 150px;
        }

        .sidebar-menu {
            flex-grow: 1;
            padding: 20px 0;
        }

        .menu-item {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: var(--text-dark);
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
            position: relative;
        }

        .menu-item:hover,
        .menu-item.active {
            background-color: #fef3c7;
            color: var(--primary);
            border-right: 4px solid var(--primary);
        }

        .badge {
            background-color: var(--secondary);
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            margin-right: auto;
        }

        /* Topbar structure */
        .main-content {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .topbar {
            height: 70px;
            background-color: #ffffff;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 30px;
            z-index: 10;
        }

        .topbar-left {
            font-size: 22px;
            font-weight: 800;
            color: var(--text-dark);
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .shift-status-pill {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
            transition: all 0.3s ease;
        }

        .shift-status-pill.active {
            background-color: #d1fae5;
            color: #065f46;
        }

        .shift-status-pill.inactive {
            background-color: #f3f4f6;
            color: #4b5563;
        }

        .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .shift-status-pill.active .dot {
            background-color: #10b981;
        }

        .shift-status-pill.inactive .dot {
            background-color: #9ca3af;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            padding-right: 16px;
            border-right: 1px solid var(--border-color);
        }

        .user-name {
            font-weight: 700;
            font-size: 15px;
            color: var(--text-dark);
        }

        .btn-top-action {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 8px;
            font-family: 'Cairo', sans-serif;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            transition: 0.2s;
            border: none;
            outline: none;
        }

        .btn-end-shift {
            background-color: #fee2e2;
            color: #b91c1c;
        }

        .btn-end-shift:hover {
            background-color: #fecaca;
        }

        .btn-logout {
            background-color: transparent;
            color: var(--text-muted);
        }

        .btn-logout:hover {
            background-color: #f3f4f6;
            color: var(--secondary);
        }

        /* Page Content */
        .content-area {
            flex-grow: 1;
            padding: 30px;
            overflow-y: auto;
            position: relative;
        }

        /* Full Screen Overlay for Inactive Shift */
        .shift-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(255, 255, 255, 0.95);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 50;
            backdrop-filter: blur(5px);
            display: none;
        }

        .shift-overlay h2 {
            font-size: 32px;
            color: var(--secondary);
            margin-bottom: 10px;
        }

        .shift-overlay p {
            font-size: 18px;
            color: var(--text-muted);
            margin-bottom: 30px;
        }

        .btn-start-shift {
            background-color: var(--success);
            color: white;
            padding: 15px 40px;
            font-size: 24px;
            font-weight: 700;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.4);
            transition: 0.3s;
        }

        .btn-start-shift:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.4);
        }

        /* MOBILE: bottom navigation bar — hidden on desktop */
        .bottom-nav {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 60px;
            background: #ffffff;
            border-top: 1px solid var(--border-color);
            z-index: 100;
            font-family: 'Cairo', sans-serif;
        }

        .bottom-nav-inner {
            display: flex;
            height: 100%;
        }

        .bottom-nav-item {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: var(--text-muted);
            font-size: 11px;
            font-weight: 600;
            position: relative;
            transition: color 0.2s;
        }

        .bottom-nav-item.active {
            color: var(--primary);
        }

        .bottom-nav-item .bnav-icon {
            font-size: 20px;
            line-height: 1;
        }

        .bottom-nav-item .bnav-badge {
            position: absolute;
            top: 4px;
            left: 50%;
            margin-left: 8px;
            background: var(--secondary);
            color: #fff;
            font-size: 10px;
            font-weight: 800;
            min-width: 18px;
            height: 18px;
            line-height: 18px;
            text-align: center;
            border-radius: 9px;
            padding: 0 4px;
        }

        /* MOBILE: responsive overrides for screens ≤768px */
        @media (max-width: 768px) {

            /* MOBILE: hide desktop sidebar */
            .sidebar {
                display: none !important;
            }

            /* MOBILE: show bottom nav */
            .bottom-nav {
                display: block;
            }

            /* MOBILE: full-width main content */
            .main-content {
                width: 100%;
            }

            /* MOBILE: compact topbar */
            .topbar {
                height: 60px;
                padding: 0 10px;
            }

            .topbar-left {
                font-size: 16px;
                gap: 8px;
            }

            .topbar-right {
                gap: 6px;
            }

            .user-name {
                display: none;
            }

            .user-profile {
                padding-right: 8px;
                gap: 0;
            }

            .shift-status-pill {
                padding: 8px 8px;
                gap: 0;
            }

            .shift-status-pill span {
                display: none;
            }

            .btn-top-action {
                padding: 8px;
                border-radius: 50%;
            }

            .btn-top-action span {
                display: none;
            }

            .btn-top-action svg {
                width: 20px;
                height: 20px;
            }

            /* MOBILE: reduce content area padding + add bottom nav clearance */
            .content-area {
                padding: 14px;
                padding-bottom: 110px;
            }

            /* MOBILE: compact shift overlay */
            .shift-overlay h2 {
                font-size: 22px;
            }

            .shift-overlay p {
                font-size: 14px;
            }

            .btn-start-shift {
                padding: 12px 30px;
                font-size: 18px;
            }
        }
    </style>
    @yield('styles')

    @vite(['resources/js/app.js'])
</head>

<body>
    <div id="spa-loading-bar"
        style="position:fixed;top:0;left:0;right:0;height:3px;background:var(--primary);z-index:9999;width:0;transition:width 0.3s ease;display:none;">
    </div>

    <div class="sidebar">
        <div class="sidebar-header">
            <!-- Delivery Logo Text as Placeholder -->
            <h2 style="color: var(--primary);">DoorFast <span style="color: var(--secondary);">كابتن</span></h2>
        </div>
        <div class="sidebar-menu">
            <a href="{{ route('delivery.dashboard') }}" class="menu-item" data-spa="true">
                إحصائياتي اليوم
            </a>
            <a href="{{ route('delivery.orders.new') }}" class="menu-item" data-spa="true">
                طلبات جديدة
                <span id="new-orders-badge" class="badge" style="display: none;">0</span>
            </a>
            <a href="{{ route('delivery.orders.received') }}" class="menu-item" data-spa="true">
                الطلبات المستلمة
            </a>
            <a href="{{ route('delivery.orders.delivered') }}" class="menu-item" data-spa="true">
                تم التوصيل
            </a>
            <a href="{{ route('delivery.wallet.index') }}" class="menu-item" data-spa="true">
                💰 كشف حسابي
            </a>
        </div>
    </div>

    {{-- MOBILE: bottom navigation bar — visible only on ≤768px --}}
    <nav class="bottom-nav" dir="rtl">
        <div class="bottom-nav-inner">
            <a href="{{ route('delivery.dashboard') }}" class="bottom-nav-item" data-spa="true" data-bnav="true">
                <span class="bnav-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 20V10"></path>
                        <path d="M12 20V4"></path>
                        <path d="M6 20v-6"></path>
                    </svg>
                </span>
                <span>إحصائياتي</span>
            </a>
            <a href="{{ route('delivery.orders.new') }}" class="bottom-nav-item" data-spa="true" data-bnav="true">
                <span class="bnav-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="16"></line>
                        <line x1="8" y1="12" x2="16" y2="12"></line>
                    </svg>
                </span>
                <span>جديدة</span>
                <span id="bnav-new-badge" class="bnav-badge" style="display:none;">0</span>
            </a>
            <a href="{{ route('delivery.orders.received') }}" class="bottom-nav-item" data-spa="true" data-bnav="true">
                <span class="bnav-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <line x1="16.5" y1="9.4" x2="7.5" y2="4.21"></line>
                        <path
                            d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z">
                        </path>
                        <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                        <line x1="12" y1="22.08" x2="12" y2="12"></line>
                    </svg>
                </span>
                <span>المستلمة</span>
            </a>
            <a href="{{ route('delivery.orders.delivered') }}" class="bottom-nav-item" data-spa="true" data-bnav="true">
                <span class="bnav-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </span>
                <span>الموصلة</span>
            </a>
            <a href="{{ route('delivery.wallet.index') }}" class="bottom-nav-item" data-spa="true" data-bnav="true">
                <span class="bnav-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"></path>
                        <path d="M3 5v14a2 2 0 0 0 2 2h16v-5"></path>
                        <path d="M18 12a2 2 0 0 0 0 4h4v-4Z"></path>
                    </svg>
                </span>
                <span>حسابي</span>
            </a>
        </div>
    </nav>

    <div class="main-content">
        <div class="topbar">
            <div class="topbar-left">
                <span id="spa-page-title">@yield('page_title', 'لوحة التحكم')</span>
            </div>
            <div class="topbar-right">
                <div class="shift-status-pill inactive" id="shift-status-pill">
                    <div class="dot"></div>
                    <span id="status-text">غير نشط</span>
                </div>

                <div class="user-profile">
                    <div class="user-name">{{ auth()->user()->name }}</div>
                </div>

                <button id="end-shift-btn" class="btn-top-action btn-end-shift" style="display: none;"
                    onclick="endShift()" title="إنهاء الشفت">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18.36 6.64a9 9 0 1 1-12.73 0"></path>
                        <line x1="12" y1="2" x2="12" y2="12"></line>
                    </svg>
                    <span>إنهاء الشفت</span>
                </button>

                <form method="POST" action="{{ route('logout') }}" id="logout-form">
                    @csrf
                    <button type="button" class="btn-top-action btn-logout" onclick="confirmLogout()"
                        title="تسجيل الخروج">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                        <span>خروج</span>
                    </button>
                </form>
            </div>
        </div>

        <div class="content-area">

            <div id="shift-gate-overlay" class="shift-overlay">
                <h2>أنت غير نشط اليوم</h2>
            </div>

            <div id="page-content">
                @yield('content')
            </div>

        </div>
    </div>

    <script>
        // Setup Pusher values for Echo
        window.PUSHER_APP_KEY = "{{ config('broadcasting.connections.pusher.key') }}";
        window.PUSHER_APP_CLUSTER = "{{ config('broadcasting.connections.pusher.options.cluster') }}";

        // Setup Axios defaults
        axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

        // ── SPA Polling lifecycle ──
        window._spaPollingIds = [];
        function addPolling(id) { window._spaPollingIds.push(id); }
        function clearAllPolling() {
            window._spaPollingIds.forEach(id => clearInterval(id));
            window._spaPollingIds = [];
        }
        function executeScripts(container) {
            container.querySelectorAll('script').forEach(old => {
                const s = document.createElement('script');
                [...old.attributes].forEach(a => s.setAttribute(a.name, a.value));
                s.textContent = old.textContent;
                old.parentNode.replaceChild(s, old);
            });
        }
        function updateActiveLink(url) {
            const path = new URL(url, location.origin).pathname;
            /* MOBILE: update both sidebar and bottom-nav active states */
            document.querySelectorAll('.menu-item[data-spa]').forEach(a => {
                const aPath = new URL(a.href, location.origin).pathname;
                a.classList.toggle('active', path === aPath);
            });
            document.querySelectorAll('.bottom-nav-item[data-bnav]').forEach(a => {
                const aPath = new URL(a.href, location.origin).pathname;
                a.classList.toggle('active', path === aPath);
            });
        }
        async function navigate(url, pushState = true) {
            if (!url) return;
            const bar = document.getElementById('spa-loading-bar');
            bar.style.display = 'block'; bar.style.width = '30%';
            try {
                clearAllPolling();
                const res = await axios.get(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-SPA-Navigation': '1' }
                });
                bar.style.width = '80%';
                const { html, title, csrf_token } = res.data;
                if (csrf_token) {
                    document.querySelector('meta[name="csrf-token"]').setAttribute('content', csrf_token);
                    axios.defaults.headers.common['X-CSRF-TOKEN'] = csrf_token;
                }
                const content = document.getElementById('page-content');
                content.innerHTML = html;
                executeScripts(content);
                if (title) {
                    document.getElementById('spa-page-title').textContent = title;
                    document.title = title + ' - DoorFast';
                }
                if (pushState) history.pushState({ url }, title || '', url);
                updateActiveLink(url);
                content.parentElement.scrollTop = 0;
            } catch (err) { window.location.href = url; }
            finally {
                bar.style.width = '100%';
                setTimeout(() => { bar.style.display = 'none'; bar.style.width = '0'; }, 300);
            }
        }
        document.querySelectorAll('.menu-item[data-spa]').forEach(a => {
            a.addEventListener('click', e => { e.preventDefault(); navigate(a.href); });
        });
        /* MOBILE: bind SPA navigation to bottom nav items */
        document.querySelectorAll('.bottom-nav-item[data-bnav]').forEach(a => {
            a.addEventListener('click', e => { e.preventDefault(); navigate(a.href); });
        });
        window.addEventListener('popstate', e => { if (e.state && e.state.url) navigate(e.state.url, false); });

        // Global variables for shift status
        let isShiftActive = false;

        document.addEventListener('DOMContentLoaded', () => {
            checkShiftStatus();
            updateActiveLink(location.href);
            history.replaceState({ url: location.href }, document.title, location.href);
            // Badge polling — registered globally so it survives SPA navigation
            addPolling(setInterval(checkNewOrdersBadge, 30000));
            checkNewOrdersBadge();
        });

        function checkShiftStatus() {
            axios.get('{{ route("delivery.shift.status") }}').then(res => {
                isShiftActive = res.data.is_active;
                updateShiftUI();
                if (isShiftActive && typeof window.onShiftStarted === 'function') {
                    window.onShiftStarted();
                }
            });
        }

        function updateShiftUI() {
            const overlay = document.getElementById('shift-gate-overlay');
            const pill = document.getElementById('shift-status-pill');
            const statusText = document.getElementById('status-text');
            const endBtn = document.getElementById('end-shift-btn');

            if (isShiftActive) {
                overlay.style.display = 'none';
                pill.className = 'shift-status-pill active';
                statusText.innerText = 'نشط';
                endBtn.style.display = 'flex';
            } else {
                overlay.style.display = 'flex';
                pill.className = 'shift-status-pill inactive';
                statusText.innerText = 'غير نشط';
                endBtn.style.display = 'none';
            }
        }

        function startShift() {
            axios.post('{{ route("delivery.shift.start") }}').then(res => {
                if (res.data.success) {
                    isShiftActive = true;
                    updateShiftUI();
                    Swal.fire({
                        icon: 'success',
                        title: 'تم بدء الشفت بنجاح',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000
                    });

                    if (typeof onShiftStarted === 'function') {
                        onShiftStarted();
                    }
                } else {
                    Swal.fire('خطأ', res.data.message || 'حدث خطأ', 'error');
                }
            });
        }

        function endShift() {
            Swal.fire({
                title: 'هل تريد إنهاء شفتك؟',
                text: "لن تتمكن من استلام طلبات جديدة",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'نعم، إنهاء',
                cancelButtonText: 'إلغاء'
            }).then((result) => {
                if (result.isConfirmed) {
                    axios.post('{{ route("delivery.shift.end") }}').then(res => {
                        if (res.data.success) {
                            isShiftActive = false;
                            updateShiftUI();

                            // Let the current page handle the state optionally, or redirect
                            window.location.href = "{{ route('login') }}";
                        }
                    });
                }
            });
        }

        function confirmLogout() {
            if (isShiftActive) {
                Swal.fire({
                    title: 'تنبيه!',
                    text: 'شفتك لا يزال نشطاً. هل أنت متأكد من تسجيل الخروج؟',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'تسجيل الخروج',
                    cancelButtonText: 'إلغاء'
                }).then((res) => {
                    if (res.isConfirmed) document.getElementById('logout-form').submit();
                });
            } else {
                document.getElementById('logout-form').submit();
            }
        }

        function checkNewOrdersBadge() {
            if (!isShiftActive) return;
            axios.get('{{ route("delivery.orders.new-data") }}').then(res => {
                if (res.data.orders) {
                    const badge = document.getElementById('new-orders-badge');
                    /* MOBILE: also update bottom-nav badge */
                    const bnavBadge = document.getElementById('bnav-new-badge');
                    if (res.data.orders.length > 0) {
                        badge.style.display = 'inline-block';
                        badge.innerText = res.data.orders.length;
                        if (bnavBadge) { bnavBadge.style.display = 'block'; bnavBadge.innerText = res.data.orders.length; }
                    } else {
                        badge.style.display = 'none';
                        if (bnavBadge) { bnavBadge.style.display = 'none'; }
                    }
                }
            }).catch(e => console.log(e));
        }

        // Setup global user ID for components scripts
        window.myDeliveryId = {{ auth()->id() }};
    </script>

    @yield('scripts')
</body>

</html>