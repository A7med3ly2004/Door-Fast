<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('DF_logo_2026.PNG') }}">
    <title>@yield('page-title', 'كول سنتر') - دور فاست</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- ── Core CDNs ── --}}
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --yellow: #f59e0b;
            --yellow-light: #fef3c7;
            --yellow-dark: #d97706;
            --red: #dc2626;
            --red-light: #fee2e2;
            --red-dark: #b91c1c;
            --bg: #0f172a;
            --sidebar-bg: #1e293b;
            --card-bg: #1e293b;
            --border: #334155;
            --text: #f1f5f9;
            --text-muted: #94a3b8;
            --input-bg: #0f172a;
            --success: #22c55e;
            --info: #3b82f6;
            --sidebar-width: 220px;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Cairo', sans-serif;
            background: var(--bg);
            color: var(--text);
            display: flex;
            min-height: 100vh;
        }

        /* ── Sidebar ── */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            height: 100vh;
            position: fixed;
            top: 0;
            right: 0;
            display: flex;
            flex-direction: column;
            border-left: 1px solid var(--border);
            z-index: 100;
            overflow-y: auto;
            scrollbar-width: none;
            /* Firefox */
            -ms-overflow-style: none;
            /* IE and Edge */
        }

        .sidebar::-webkit-scrollbar {
            display: none;
            /* Chrome, Safari, and Opera */
        }

        .sidebar-logo {
            padding: 0px 5px;
        }

        .sidebar-logo .logo-text {
            font-size: 25px;
            font-weight: 800;
            color: var(--red);
            display: block;
            text-align: center;
        }

        .sidebar-logo .logo-sub {
            font-size: 15px;
            color: var(--text-muted);
            display: block;
            margin-top: 5px;
            text-align: center;
        }

        .sidebar-nav {
            flex: 1;
            padding: 10px 8px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 10px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s ease;
            margin-bottom: 5px;
        }

        .nav-link:hover {
            background: var(--border);
            color: var(--text);
        }

        .nav-link.active {
            background: linear-gradient(135deg, #ba272d, #323232);
            color: #fcfcfcff;
        }

        .nav-link .icon {
            font-size: 17px;
            width: 22px;
            text-align: center;
        }

        .sidebar-footer {
            padding: 14px;
            border-top: 1px solid var(--border);
        }

        .btn-logout {
            background: var(--red);
            color: #fff;
            border: 1px solid var(--red);
            padding: 7px 14px;
            border-radius: 8px;
            font-family: 'Cairo', sans-serif;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: all 0.2s;
        }

        .btn-logout:hover {
            background: var(--red-light);
            color: var(--red);
        }

        /* ── Main ── */
        .main-wrap {
            flex: 1;
            margin-right: var(--sidebar-width);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .topbar {
            background: var(--sidebar-bg);
            border-bottom: 1px solid var(--border);
            padding: 0 24px;
            height: 58px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .topbar-title {
            font-size: 17px;
            font-weight: 700;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--border);
            padding: 5px 12px;
            border-radius: 20px;
        }

        .user-badge .dot {
            width: 7px;
            height: 7px;
            background: var(--success);
            border-radius: 50%;
        }

        .user-badge span {
            font-size: 13px;
            font-weight: 600;
        }

        .page-content {
            flex: 1;
            padding: 24px;
        }

        /* ── Buttons ── */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 7px 16px;
            border-radius: 8px;
            font-family: 'Cairo', sans-serif;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background: var(--yellow);
            color: #000;
        }

        .btn-primary:hover {
            background: var(--yellow-dark);
        }

        .btn-danger {
            background: var(--red);
            color: #fff;
        }

        .btn-danger:hover {
            background: var(--red-dark);
        }

        .btn-secondary {
            background: var(--border);
            color: var(--text);
        }

        .btn-secondary:hover {
            background: #475569;
        }

        .btn-info {
            background: var(--info);
            color: #fff;
        }

        .btn-success {
            background: var(--success);
            color: #fff;
        }

        .btn-sm {
            padding: 4px 10px;
            font-size: 12px;
            border-radius: 6px;
        }

        /* ── Cards ── */
        .card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 18px;
        }

        .card-title {
            font-size: 14px;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 14px;
        }

        /* ── Tables ── */
        .table-wrap {
            overflow-x: auto;
            border-radius: 10px;
            border: 1px solid var(--border);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead th {
            background: var(--bg);
            padding: 11px 14px;
            font-size: 12px;
            font-weight: 700;
            color: var(--text-muted);
            text-align: right;
            white-space: nowrap;
            border-bottom: 1px solid var(--border);
        }

        tbody td {
            padding: 11px 14px;
            font-size: 13px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        tbody tr:hover {
            background: rgba(255, 255, 255, 0.03);
        }

        tfoot td {
            padding: 10px 14px;
            font-size: 13px;
            background: var(--bg);
        }

        /* ── Badges ── */
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }

        .badge-yellow {
            background: var(--yellow-light);
            color: #92400e;
        }

        .badge-blue {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-green {
            background: #dcfce7;
            color: #166534;
        }

        .badge-red {
            background: var(--red-light);
            color: var(--red-dark);
        }

        .badge-gray {
            background: #e2e8f0;
            color: #475569;
        }

        /* ── Forms ── */
        .form-group {
            margin-bottom: 14px;
        }

        .form-label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            color: var(--text-muted);
            margin-bottom: 5px;
        }

        .form-control {
            width: 100%;
            background: var(--input-bg);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 8px 11px;
            color: var(--text);
            font-family: 'Cairo', sans-serif;
            font-size: 13px;
            outline: none;
            transition: border-color 0.2s;
        }

        .form-control:focus {
            border-color: var(--yellow);
        }

        .form-select {
            width: 100%;
            background: var(--input-bg);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 8px 11px;
            color: var(--text);
            font-family: 'Cairo', sans-serif;
            font-size: 13px;
            outline: none;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .form-row-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 10px;
        }

        /* ── Modals ── */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.open {
            display: flex;
        }

        .modal {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 18px;
            width: 90%;
            max-width: 640px;
            max-height: 90vh;
            overflow-y: auto;
            animation: modalIn 0.22s ease;
        }

        .modal-lg {
            max-width: 860px;
        }

        .modal-xl {
            max-width: 1040px;
        }

        @keyframes modalIn {
            from {
                transform: translateY(-18px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 22px;
            border-bottom: 1px solid var(--border);
        }

        .modal-header h3 {
            font-size: 15px;
            font-weight: 700;
        }

        .modal-body {
            padding: 22px;
        }

        .modal-footer {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
            padding: 14px 22px;
            border-top: 1px solid var(--border);
        }

        .btn-close {
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            font-size: 20px;
            line-height: 1;
            padding: 4px;
        }

        .btn-close:hover {
            color: var(--text);
        }

        /* ── Toggle ── */
        .toggle {
            position: relative;
            width: 42px;
            height: 23px;
        }

        .toggle input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            inset: 0;
            background: var(--border);
            border-radius: 34px;
            cursor: pointer;
            transition: 0.2s;
        }

        .toggle-slider::before {
            content: '';
            position: absolute;
            height: 17px;
            width: 17px;
            right: 3px;
            bottom: 3px;
            background: #fff;
            border-radius: 50%;
            transition: 0.2s;
        }

        .toggle input:checked+.toggle-slider {
            background: var(--success);
        }

        .toggle input:checked+.toggle-slider::before {
            transform: translateX(-19px);
        }

        /* ── KPIs ── */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
        }

        .kpi-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 16px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .kpi-card.red {
            border-color: var(--red) !important;
            background: rgba(220, 38, 38, 0.15) !important;
            border-right-width: 5px !important;
            border-right-style: solid !important;
        }
        .kpi-card.cyan {
            border-color: #00e5ff !important;
            background: rgba(0, 229, 255, 0.15) !important;
            border-right-width: 5px !important;
            border-right-style: solid !important;
        }

        .kpi-card.green {
            border-color: var(--success) !important;
            background: rgba(34, 197, 94, 0.15) !important;
            border-right-width: 5px !important;
            border-right-style: solid !important;
        }

        .kpi-card.blue {
            border-color: var(--info) !important;
            background: rgba(59, 130, 246, 0.15) !important;
            border-right-width: 5px !important;
            border-right-style: solid !important;
        }

        .kpi-card.yellow {
            border-color: var(--yellow) !important;
            background: rgba(245, 158, 11, 0.15) !important;
            border-right-width: 5px !important;
            border-right-style: solid !important;
        }

        .kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .kpi-label {
            font-size: 11px;
            color: var(--text-muted);
            font-weight: 600;
            margin-bottom: 6px;
        }

        .kpi-value {
            font-size: 24px;
            font-weight: 800;
            color: var(--text);
        }

        .kpi-sub {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 4px;
        }

        /* ── Shift Overlay ── */
        .shift-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(15, 23, 42, 0.95);
            display: none;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 50;
            backdrop-filter: blur(5px);
        }

        .shift-overlay h2 {
            font-size: 32px;
            color: var(--yellow);
            margin-bottom: 10px;
        }

        .shift-overlay p {
            font-size: 18px;
            color: var(--text-muted);
            margin-bottom: 30px;
        }

        /* ── Misc ── */
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
        }

        .section-header h2 {
            font-size: 17px;
            font-weight: 700;
        }

        .filter-bar {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }

        .filter-bar .form-control,
        .filter-bar .form-select {
            width: auto;
            min-width: 130px;
        }

        .pagination {
            display: flex;
            align-items: center;
            gap: 5px;
            justify-content: center;
            margin-top: 18px;
        }

        .pagination a,
        .pagination span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border-radius: 7px;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted);
            text-decoration: none;
            border: 1px solid var(--border);
            background: var(--card-bg);
            transition: all 0.2s;
            cursor: pointer;
        }

        .pagination a:hover {
            background: var(--border);
            color: var(--text);
        }

        .pagination .active {
            background: var(--yellow);
            color: #000;
            border-color: var(--yellow);
        }

        .pagination .disabled {
            opacity: 0.4;
            pointer-events: none;
        }

        .chart-container {
            position: relative;
            width: 100%;
        }

        .divider {
            border: none;
            border-top: 1px solid var(--border);
            margin: 14px 0;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 7px 0;
            border-bottom: 1px solid var(--border);
            font-size: 13px;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: var(--text-muted);
            font-weight: 600;
        }

        .loading-overlay {
            position: absolute;
            inset: 0;
            background: rgba(30, 41, 59, 0.7);
            border-radius: inherit;
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }

        .loading-overlay.show {
            display: flex;
        }

        .spin {
            display: inline-block;
            width: 24px;
            height: 24px;
            border: 3px solid rgba(255, 255, 255, 0.2);
            border-top-color: var(--yellow);
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
    </style>
</head>

<body>
    {{-- ── Loading bar ── --}}
    <div id="spa-loading-bar"
        style="position:fixed;top:0;left:0;right:0;height:3px;background:var(--yellow);z-index:9999;width:0;transition:width 0.3s ease;display:none;">
    </div>

    <aside class="sidebar">
        <div class="sidebar-logo"
            style="display: flex; justify-content: center; align-items: center;">
            <img src="{{ asset('DF_logo_for_sb.png') }}" alt="Door Fast Logo" style="max-width: 100%; height: auto;">
        </div>
        <nav class="sidebar-nav">
            <a href="{{ route('callcenter.orders.create') }}" class="nav-link" data-spa="true">
                <span class="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 18px; height: 18px; vertical-align: middle;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                </span> إنشاء طلب
            </a>
            <a href="{{ route('callcenter.orders.index') }}" class="nav-link" data-spa="true">
                <span class="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 18px; height: 18px; vertical-align: middle;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </span> قائمة الطلبات
            </a>
            <a href="{{ route('callcenter.orders.global-search') }}" class="nav-link" id="nav-global-search" data-spa="true">
                <span class="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 18px; height: 18px; vertical-align: middle;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span> بحث الطلبات
            </a>
            <a href="{{ route('callcenter.clients.index') }}" class="nav-link" data-spa="true">
                <span class="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 18px; height: 18px; vertical-align: middle;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </span> العملاء
            </a>
            <a href="{{ route('callcenter.shops.index') }}" class="nav-link" data-spa="true">
                <span class="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 18px; height: 18px; vertical-align: middle;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </span> المتاجر
            </a>
            <a href="{{ route('callcenter.delivery.index') }}" class="nav-link" data-spa="true">
                <span class="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 18px; height: 18px; vertical-align: middle;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
                    </svg>
                </span> الدلفري
            </a>
            <a href="{{ route('callcenter.stats.index') }}" class="nav-link" data-spa="true">
                <span class="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 18px; height: 18px; vertical-align: middle;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </span> إحصائياتي
            </a>
            <a href="{{ route('callcenter.wallet.index') }}" class="nav-link" data-spa="true">
                <span class="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 18px; height: 18px; vertical-align: middle;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m4 0h1m-7 4h12a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </span> كشف حسابي
            </a>
        </nav>
        <div class="sidebar-footer">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn-logout">تسجيل الخروج</button>
            </form>
        </div>
    </aside>

    <div class="main-wrap">
        <header class="topbar">
            <span class="topbar-title" id="spa-page-title">@yield('page-title', 'كول سنتر')</span>
            <div class="topbar-right">
                <button id="cc-shift-btn" onclick="toggleCCShift()" class="btn" style="display:none;font-size:12px;padding:6px 14px;border-radius:20px;font-weight:700;border:none;cursor:pointer;transition:all .2s ease;"></button>
                <div class="user-badge">
                    <span class="dot"></span>
                    <span>{{ auth()->user()->name }}</span>
                </div>
            </div>
        </header>
        <div style="position:relative; flex:1; display:flex; flex-direction:column;">
            <div id="shift-gate-overlay" class="shift-overlay">
                <h2>أنت غير نشط الآن</h2>
                <p>يرجى التوجه إلى المديرين</p>
            </div>
            <main class="page-content" id="page-content">
                @yield('content')
            </main>
        </div>
    </div>

    <script>
        axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        axios.defaults.headers.common['Accept'] = 'application/json';

        const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3500, timerProgressBar: true });
        function showSuccess(msg) { Toast.fire({ icon: 'success', title: msg }); }
        function showError(msg) { Toast.fire({ icon: 'error', title: msg }); }
        function showWarning(msg) { Toast.fire({ icon: 'warning', title: msg }); }

        async function confirmAction(title, text = '', confirmText = 'نعم') {
            const r = await Swal.fire({ title, text, icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc2626', cancelButtonColor: '#475569', confirmButtonText: confirmText, cancelButtonText: 'إلغاء', background: '#1e293b', color: '#f1f5f9' });
            return r.isConfirmed;
        }

        async function inputAction(title, inputPlaceholder = '') {
            const r = await Swal.fire({ title, input: 'text', inputPlaceholder, showCancelButton: true, confirmButtonText: 'تأكيد', cancelButtonText: 'إلغاء', background: '#1e293b', color: '#f1f5f9', confirmButtonColor: '#dc2626' });
            return r.isConfirmed ? r.value : null;
        }

        function openModal(id) { document.getElementById(id).classList.add('open'); }
        function closeModal(id) { document.getElementById(id).classList.remove('open'); }
        document.addEventListener('click', e => { if (e.target.classList.contains('modal-overlay')) e.target.classList.remove('open'); });

        function formatDate(str) {
            if (!str) return '—';
            const d = new Date(str);
            return d.toLocaleDateString('ar-EG', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
        }

        function statusBadge(status) {
            const map = { pending: ['باقي', 'badge-yellow'], received: ['مُسلَّم للمندوب', 'badge-blue'], delivered: ['مُوصَّل', 'badge-green'], cancelled: ['ملغي', 'badge-red'] };
            const [label, cls] = map[status] || [status, 'badge-gray'];
            return `<span class="badge ${cls}">${label}</span>`;
        }

        function renderPagination(lastPage, current, callback) {
            if (lastPage <= 1) return '';
            let html = '<div class="pagination">';
            html += `<a class="${current === 1 ? 'disabled' : ''}" onclick="${callback}(${current - 1})">‹</a>`;
            for (let i = 1; i <= lastPage; i++) {
                if (i === 1 || i === lastPage || Math.abs(i - current) <= 2) html += `<a class="${i === current ? 'active' : ''}" onclick="${callback}(${i})">${i}</a>`;
                else if (Math.abs(i - current) === 3) html += '<span>…</span>';
            }
            html += `<a class="${current === lastPage ? 'disabled' : ''}" onclick="${callback}(${current + 1})">›</a></div>`;
            return html;
        }

        async function fetchCCShiftStatus() {
            try {
                const { data } = await axios.get('{{ route("callcenter.shift.status") }}');
                updateCCShiftBtn(data.is_active);
            } catch(e) { console.error('shift status error', e); }
        }

        function updateCCShiftBtn(isActive) {
            const btn = document.getElementById('cc-shift-btn');
            const overlay = document.getElementById('shift-gate-overlay');
            btn.style.display = 'inline-flex';
            if (isActive) {
                overlay.style.display = 'none';
                btn.style.background = 'rgba(220,38,38,.12)';
                btn.style.color = 'var(--red)';
                btn.innerHTML = '⏸ إنهاء الوردية';
            } else {
                overlay.style.display = 'flex';
                btn.style.background = 'rgba(34,197,94,.15)';
                btn.style.color = 'var(--success)';
                btn.innerHTML = '⏱ بدء الوردية';
            }
        }

        window.toggleCCShift = async function() {
            const btn = document.getElementById('cc-shift-btn');
            btn.disabled = true;
            const originalText = btn.innerHTML;
            btn.innerHTML = 'جاري...';
            try {
                const { data } = await axios.post('{{ route("callcenter.shift.toggle") }}');
                if (data.success) {
                    updateCCShiftBtn(data.is_active);
                    if(typeof showSuccess === 'function') showSuccess(data.message);
                } else {
                    btn.innerHTML = originalText;
                }
            } catch(e) {
                btn.innerHTML = originalText;
                const err = e.response?.data?.message || 'حدث خطأ';
                if(typeof showError === 'function') showError(err);
            } finally {
                btn.disabled = false;
            }
        };

        document.addEventListener('DOMContentLoaded', fetchCCShiftStatus);
    </script>

    {{-- ── SPA Navigation Engine ── --}}
    <script>
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
            const links = document.querySelectorAll('.sidebar .nav-link[data-spa]');

            let bestMatch = null;
            let maxLength = -1;

            links.forEach(a => {
                a.classList.remove('active');
                const aPath = new URL(a.href, location.origin).pathname;

                // Match if exact match OR if path starts with aPath followed by / (to match sub-routes)
                const isMatch = (path === aPath) || (path.startsWith(aPath + '/') && aPath.length > 1);

                if (isMatch && aPath.length > maxLength) {
                    maxLength = aPath.length;
                    bestMatch = a;
                }
            });

            if (bestMatch) {
                bestMatch.classList.add('active');
            }
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
                    document.title = title + ' - دور فاست';
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
        document.querySelectorAll('.sidebar .nav-link[data-spa]').forEach(a => {
            a.addEventListener('click', e => { e.preventDefault(); navigate(a.href); });
        });
        window.addEventListener('popstate', e => { if (e.state && e.state.url) navigate(e.state.url, false); });
        document.addEventListener('DOMContentLoaded', () => {
            updateActiveLink(location.href);
            history.replaceState({ url: location.href }, document.title, location.href);
        });
    </script>

    @stack('scripts')
</body>

</html>