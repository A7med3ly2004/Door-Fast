<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('DF_logo_2026.PNG') }}">
    <title>لوحة الدلفري الاحتياطي - DoorFast</title>

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
            -webkit-tap-highlight-color: transparent;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-dark);
            height: 100vh;
            display: flex;
            overflow: hidden;
        }

        /* Touch targets */
        /* button, a, .clickable {
            min-height: 44px;
            min-width: 44px;
        } */

        /* Typography scaling */
        @media (max-width: 767px) {
            body {
                font-size: 14px;
            }

            .kpi-value {
                font-size: 24px;
            }

            .order-number {
                font-size: 18px;
            }

            .total-amount {
                font-size: 20px;
                font-weight: 800;
            }
        }

        /* Sidebar structure (Desktop/Tablet) */
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
            width: 100%;
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
            display: flex;
            align-items: center;
            gap: 15px;
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
            text-align: center;
            padding: 20px;
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
            width: 100%;
            max-width: 300px;
        }

        .btn-start-shift:active {
            transform: scale(0.98);
        }

        /* Bottom Nav (Mobile Only) */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 65px;
            background-color: var(--sidebar-bg);
            border-top: 1px solid var(--border-color);
            display: none;
            justify-content: space-around;
            align-items: center;
            z-index: 40;
            padding-bottom: env(safe-area-inset-bottom);
        }

        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: var(--text-muted);
            font-weight: 600;
            font-size: 12px;
            position: relative;
            flex: 1;
            height: 100%;
        }

        .nav-item.active {
            color: var(--primary);
        }

        .nav-icon {
            font-size: 20px;
            margin-bottom: -5px;
        }

        .nav-badge {
            position: absolute;
            top: 5px;
            right: 25%;
            background-color: var(--secondary);
            color: white;
            font-size: 10px;
            padding: 2px 5px;
            border-radius: 10px;
        }

        /* Mobile specific styles */
        @media (max-width: 767px) {
            .sidebar {
                display: none;
            }

            .bottom-nav {
                display: flex;
            }

            .main-content {
                padding-bottom: 60px;
            }

            /* MOBILE: reduce content area padding + add bottom nav clearance */
            .content-area {
                padding: 14px;
                padding-bottom: 110px;
            }

            /* New Topbar Mobile Fixes */
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
        }

        @media (min-width: 768px) {
            .bottom-nav {
                display: none;
            }

            .sidebar {
                display: flex;
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

    <!-- Desktop Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2 style="color: var(--primary);">DoorFast <span style="color: var(--secondary);">احتياطي</span></h2>
        </div>
        <div class="sidebar-menu">
            <a href="{{ route('reserve.dashboard') }}" class="menu-item" data-spa="true">
                إحصائياتي
            </a>
            <a href="{{ route('reserve.orders.new') }}" class="menu-item" data-spa="true">
                طلبات جديدة
                <span id="new-orders-badge-desktop" class="badge" style="display: none;">0</span>
            </a>
            <a href="{{ route('reserve.orders.received') }}" class="menu-item" data-spa="true">
                مستلمة
            </a>
            <a href="{{ route('reserve.orders.delivered') }}" class="menu-item" data-spa="true">
                مكتملة
            </a>
            <a href="{{ route('reserve.wallet.index') }}" class="menu-item" data-spa="true">
                كشف حسابي
            </a>
        </div>
    </div>

    <!-- Main Content Box -->
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

    <!-- Mobile Bottom Navigation -->
    <div class="bottom-nav">
        <a href="{{ route('reserve.dashboard') }}" class="nav-item" data-spa="true">
            <div class="nav-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 20V10"></path>
                    <path d="M12 20V4"></path>
                    <path d="M6 20v-6"></path>
                </svg>
            </div>
            <span>إحصائياتي</span>
        </a>
        <a href="{{ route('reserve.orders.new') }}" class="nav-item" data-spa="true">
            <div class="nav-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="16"></line>
                    <line x1="8" y1="12" x2="16" y2="12"></line>
                </svg>
            </div>
            <span>جديدة</span>
            <span id="new-orders-badge-mobile" class="nav-badge" style="display: none;">0</span>
        </a>
        <a href="{{ route('reserve.orders.received') }}" class="nav-item" data-spa="true">
            <div class="nav-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <line x1="16.5" y1="9.4" x2="7.5" y2="4.21"></line>
                    <path
                        d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z">
                    </path>
                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                    <line x1="12" y1="22.08" x2="12" y2="12"></line>
                </svg>
            </div>
            <span>مستلمة</span>
        </a>
        <a href="{{ route('reserve.orders.delivered') }}" class="nav-item" data-spa="true">
            <div class="nav-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
            </div>
            <span>مكتملة</span>
        </a>
        <a href="{{ route('reserve.wallet.index') }}" class="nav-item" data-spa="true">
            <div class="nav-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"></path>
                    <path d="M3 5v14a2 2 0 0 0 2 2h16v-5"></path>
                    <path d="M18 12a2 2 0 0 0 0 4h4v-4Z"></path>
                </svg>
            </div>
            <span>حسابي</span>
        </a>
    </div>

    <script>
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
            document.querySelectorAll('.menu-item[data-spa], .nav-item[data-spa]').forEach(a => {
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
        document.querySelectorAll('.menu-item[data-spa], .nav-item[data-spa]').forEach(a => {
            a.addEventListener('click', e => { e.preventDefault(); navigate(a.href); });
        });
        window.addEventListener('popstate', e => { if (e.state && e.state.url) navigate(e.state.url, false); });

        let isShiftActive = false;

        document.addEventListener('DOMContentLoaded', () => {
            checkShiftStatus();
            updateActiveLink(location.href);
            history.replaceState({ url: location.href }, document.title, location.href);
            addPolling(setInterval(checkNewOrdersBadge, 30000));
            checkNewOrdersBadge();
        });

        function checkShiftStatus() {
            axios.get('{{ route("reserve.shift.status") }}').then(res => {
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
            axios.post('{{ route("reserve.shift.start") }}').then(res => {
                if (res.data.success) {
                    isShiftActive = true;
                    updateShiftUI();
                    Swal.fire({
                        icon: 'success', title: 'تم بدء الشفت بنجاح', toast: true,
                        position: 'top-end', showConfirmButton: false, timer: 3000
                    });
                    if (typeof onShiftStarted === 'function') onShiftStarted();
                } else {
                    Swal.fire('خطأ', res.data.message || 'حدث خطأ', 'error');
                }
            });
        }

        function endShift() {
            Swal.fire({
                title: 'هل تريد إنهاء شفتك؟',
                text: "لن تتمكن من استلام طلبات احتياطية",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'نعم، إنهاء',
                cancelButtonText: 'إلغاء'
            }).then((result) => {
                if (result.isConfirmed) {
                    axios.post('{{ route("reserve.shift.end") }}').then(res => {
                        if (res.data.success) {
                            isShiftActive = false;
                            updateShiftUI();
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
            axios.get('{{ route("reserve.orders.new-data") }}').then(res => {
                if (res.data.orders) {
                    const badgeMobile = document.getElementById('new-orders-badge-mobile');
                    const badgeDesktop = document.getElementById('new-orders-badge-desktop');
                    const count = res.data.orders.length;

                    if (count > 0) {
                        if (badgeMobile) { badgeMobile.style.display = 'block'; badgeMobile.innerText = count; }
                        if (badgeDesktop) { badgeDesktop.style.display = 'inline-block'; badgeDesktop.innerText = count; }
                    } else {
                        if (badgeMobile) badgeMobile.style.display = 'none';
                        if (badgeDesktop) badgeDesktop.style.display = 'none';
                    }
                }
            }).catch(e => console.log(e));
        }

        window.myDeliveryId = {{ auth()->id() }};
    </script>

    @yield('scripts')
</body>

</html>