<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('DF_logo_2026.PNG') }}">
    <title>@yield('page-title', 'لوحة التحكم') - دور فاست</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- ── Core CDNs ── --}}
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

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
            --cyan: #0891b2;
            --sidebar-width: 260px;
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
            padding: 1px 1px 1px;
        }

        .sidebar-logo .logo-text {
            font-size: 22px;
            font-weight: 800;
            color: var(--yellow);
            display: block;
            text-align: center;
        }

        .sidebar-logo .logo-sub {
            font-size: 12px;
            color: var(--text-muted);
            display: block;
            margin-top: 2px;
            text-align: center;
        }

        .sidebar-nav {
            flex: 1;
            padding: 12px 10px;
        }

        .nav-section-title {
            font-size: 11px;
            font-weight: 1000;
            color: #bb272c;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 10px 10px 6px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
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
            color: #ffffffff;
        }

        .nav-link svg {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
        }

        .sidebar-footer {
            padding: 16px;
            border-top: 1px solid var(--border);
        }

        /* ── Main ── */
        .main-wrap {
            flex: 1;
            margin-right: var(--sidebar-width);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* ── Topbar ── */
        .topbar {
            background: var(--sidebar-bg);
            padding: 0 28px;
            height: 86px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .topbar-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--text);
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .admin-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--border);
            padding: 6px 14px;
            border-radius: 20px;
        }

        .admin-badge .dot {
            width: 8px;
            height: 8px;
            background: var(--success);
            border-radius: 50%;
        }

        .admin-badge span {
            font-size: 13px;
            font-weight: 600;
        }

        .btn-logout {
            background: #bb272c;
            color: #fff;
            border: 1px solid var(--red);
            padding: 7px 18px;
            border-radius: 8px;
            font-family: 'Cairo', sans-serif;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-logout:hover {
            background: var(--red-light);
            color: var(--red);
        }

        /* ── Content ── */
        .page-content {
            flex: 1;
            padding: 28px;
        }

        /* ── Cards ── */
        .card {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 20px;
        }

        .card-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 16px;
        }

        /* ── Buttons ── */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 25px;
            border-radius: 8px;
            font-family: 'Cairo', sans-serif;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background: var(--yellow);
            color: #000;

        }

        .btn-primary:hover {
            background: var(--yellow-dark);
            transform: translateY(-2px);
        }

        .btn-danger {
            background: var(--red);
            color: #fff;
            text-decoration: none;
        }

        .btn-danger:hover {
            background: var(--red-dark);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: var(--border);
            color: var(--text);
        }

        .btn-secondary:hover {
            background: #475569;
            transform: translateY(-2px);
        }

        .btn-info {
            background: var(--info);
            color: #fff;
        }

        .btn-info:hover {
            background: var(--cyan);
            transform: translateY(-2px);
        }

        .btn-success {
            background: var(--success);
            color: #fff;
        }

        .btn-success:hover {
            transform: translateY(-2px);
        }

        .btn-sm {
            padding: 5px 12px;
            font-size: 15px;
            border-radius: 6px;
        }

        .btn-icon {
            padding: 6px;
            border-radius: 6px;
            background: var(--border);
            color: var(--text);
        }

        .btn-icon:hover {
            background: #475569;
            transform: translateY(-2px);
        }

        /* ── Tables ── */
        .table-wrap {
            overflow-x: auto;
            border-radius: 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead th {
            background: var(--bg);
            padding: 12px 16px;
            font-size: 14px;
            font-weight: 700;
            color: var(--text-muted);
            text-align: right;
            white-space: nowrap;
            border-bottom: 1px solid var(--border);
        }

        tbody td {
            padding: 12px 16px;
            font-size: 17px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        tbody tr:hover {
            background: rgba(255, 255, 255, 0.03);
        }

        /* ── Badges ── */
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
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
            margin-bottom: 16px;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 700;
            color: var(--text-muted);
            margin-bottom: 6px;
        }

        .form-control {
            width: 100%;
            background: var(--input-bg);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 9px 12px;
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
            padding: 9px 12px;
            color: var(--text);
            font-family: 'Cairo', sans-serif;
            font-size: 13px;
            outline: none;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .form-row-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 12px;
        }

        .error-text {
            color: var(--red);
            font-size: 11px;
            margin-top: 4px;
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
            border-radius: 20px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            animation: modalIn 0.25s ease;
        }

        .modal-lg {
            max-width: 800px;
        }

        .modal-xl {
            max-width: 1000px;
        }

        @keyframes modalIn {
            from {
                transform: translateY(-20px);
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
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
        }

        .modal-header h3 {
            font-size: 16px;
            font-weight: 700;
        }

        .modal-body {
            padding: 24px;
        }

        .modal-footer {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            padding: 16px 24px;
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

        /* ── Toggle switch ── */
        .toggle-wrap {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .toggle {
            position: relative;
            width: 44px;
            height: 24px;
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
            height: 18px;
            width: 18px;
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
            transform: translateX(-20px);
        }

        /* ── Filter bar ── */
        .filter-bar {
            display: flex;
            justify-content: flex-start;
            align-items: center;
            gap: 5px;
            flex-wrap: wrap;
        }

        .filter-bar .form-control,
        .filter-bar .form-select {
            width: auto;
            min-width: 170px;
        }

        /* ── KPI Cards ── */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .kpi-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 18px;
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

        .kpi-card.white {
            border-color: var(--white) !important;
            background: rgba(255, 255, 255, 0.15) !important;
            border-right-width: 5px !important;
            border-right-style: solid !important;
        }

        .kpi-card.cyan {
            border-color: var(--cyan) !important;
            background: rgba(8, 145, 178, 0.15) !important;
            border-right-width: 5px !important;
            border-right-style: solid !important;
        }
        
        .kpi-card.purple {
            border-color: #9333ea !important;
            background: rgba(147, 51, 234, 0.15) !important;
            border-right-width: 5px !important;
            border-right-style: solid !important;
        }

        .kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .kpi-label {
            font-size: 16px;
            color: var(--text-muted);
            font-weight: 600;
            margin-bottom: 8px;
        }

        .kpi-value {
            font-size: 26px;
            font-weight: 800;
            color: var(--text);
            text-align: right;
        }



        .kpi-sub {
            font-size: 14px;
            color: var(--text-muted);
            margin-top: 4px;
        }

        /* ── Pagination ── */
        .pagination {
            display: flex;
            align-items: center;
            gap: 6px;
            justify-content: center;
            margin-top: 20px;
        }

        .pagination a,
        .pagination span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
            text-decoration: none;
            border: 1px solid var(--border);
            background: var(--card-bg);
            transition: all 0.2s;
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

        /* ── Chart container ── */
        .chart-container {
            position: relative;
            width: 100%;
        }

        /* ── Misc ── */
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .section-header h2 {
            font-size: 18px;
            font-weight: 700;
        }

        .text-muted {
            color: var(--text-muted);
        }

        .text-right {
            text-align: right;
        }

        .mt-2 {
            margin-top: 8px;
        }

        .mt-4 {
            margin-top: 16px;
        }

        .mb-4 {
            margin-bottom: 16px;
        }

        .gap-10 {
            gap: 10px;
        }

        .flex {
            display: flex;
        }

        .items-center {
            align-items: center;
        }

        .justify-between {
            justify-content: space-between;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .grid-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 16px;
        }

        .spin {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid rgba(0, 0, 0, 0.3);
            border-top-color: currentColor;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .loading-overlay {
            position: absolute;
            inset: 0;
            background: rgba(30, 41, 59, 0.7);
            border-radius: inherit;
            display: none;
            align-items: center;
            justify-content: center;

            .loading-overlay.show {
                display: flex;
            }

            .divider {
                border: none;
                border-top: 1px solid var(--border);
                margin: 16px 0;
            }

            .info-row {
                display: flex;
                justify-content: space-between;
                padding: 8px 0;
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
    </style>
</head>

<body>
    {{-- ── Loading bar ── --}}
    <div id="spa-loading-bar"
        style="position:fixed;top:0;left:0;right:0;height:3px;background:var(--yellow);z-index:9999;width:0;transition:width 0.3s ease;display:none;">
    </div>

    {{-- ── Sidebar ── --}}
    <aside class="sidebar">
        <div class="sidebar-logo"
            style="display: flex; justify-content: center; align-items: center; padding: 1px 1px;">
            <img src="{{ asset('DF_logo_for_sb.png') }}" alt="Door Fast Logo" style="max-width: 70%; height: auto;">
        </div>
        <nav class="sidebar-nav">
            <span class="nav-section-title">الرئيسية</span>
            <a href="{{ route('admin.dashboard') }}" class="nav-link" data-spa="true">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                لوحة التحكم
            </a>

            <span class="nav-section-title">الطلبات والعمليات</span>
            <a href="{{ route('admin.orders.create') }}" class="nav-link" data-spa="true">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                إنشاء طلب
            </a>
            <a href="{{ route('admin.orders.index') }}" class="nav-link" data-spa="true">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                الطلبات
            </a>
            <a href="{{ route('admin.activity-log.index') }}" class="nav-link" data-spa="true">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                </svg>
                العمليات
            </a>

            <span class="nav-section-title">إدارة الجهات والمستخدمين</span>
            <a href="{{ route('admin.clients.index') }}" class="nav-link" data-spa="true">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                العملاء
            </a>
            <a href="{{ route('admin.shops.index') }}" class="nav-link" data-spa="true">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                المتاجر
            </a>
            <a href="{{ route('admin.delivery.index') }}" class="nav-link" data-spa="true">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
                </svg>
                المناديب
            </a>
            <a href="{{ route('admin.callcenter.index') }}" class="nav-link" data-spa="true">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                </svg>
                كول سنتر
            </a>
            <a href="{{ route('admin.admin-management.index') }}" class="nav-link" data-spa="true">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                المديرين
            </a>

            <span class="nav-section-title">الإدارة المالية</span>
            <a href="{{ route('admin.treasury.index') }}" class="nav-link" data-spa="true">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 10h18M7 15h1m4 0h1m4 0h1m-7 4h12a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                الخزينة
            </a>
            <a href="{{ route('admin.general-ledger.index') }}" class="nav-link" data-spa="true">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
                كشف حساب عام
            </a>
            <a href="{{ route('admin.report-trial-balance.index') }}" class="nav-link" data-spa="true">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                </svg>
                ميزان المراجعة
            </a>

            <span class="nav-section-title">التقارير والإحصائيات</span>
            <a href="{{ route('admin.reports.index') }}" class="nav-link" data-spa="true">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                التقارير العامة
            </a>
            <a href="{{ route('admin.report-delivery.index') }}" class="nav-link" data-spa="true">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
                </svg>
                تقارير المناديب
            </a>
            <a href="{{ route('admin.report-callcenter.index') }}" class="nav-link" data-spa="true">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                </svg>
                تقارير الكول سنتر
            </a>
            <a href="{{ route('admin.report-hops.index') }}" class="nav-link" data-spa="true">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
                تقارير المتاجر
            </a>
            <a href="{{ route('admin.report-discounts.index') }}" class="nav-link" data-spa="true">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                </svg>
                تقارير الخصومات
            </a>


            <span class="nav-section-title">النظام</span>

            <a href="{{ route('admin.settings.index') }}" class="nav-link" data-spa="true">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                الإعدادات
            </a>
        </nav>
        <div class="sidebar-footer">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn-logout" style="width:100%">تسجيل الخروج</button>
            </form>
        </div>
    </aside>

    {{-- ── Main Content ── --}}
    <div class="main-wrap">
        <header class="topbar">
            <span class="topbar-title" id="spa-page-title">@yield('page-title', 'لوحة التحكم')</span>
            <div class="topbar-right">
                <div style="position:relative;cursor:pointer" onclick="toggleNotifPanel()">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        style="width: 24px; height: 24px;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <span id="notif-count-badge" style="display:none;position:absolute;top:-4px;left:-4px;background:var(--red);color:#fff;
                               font-size:10px;font-weight:800;min-width:18px;height:18px;line-height:18px;
                               text-align:center;border-radius:9px;padding:0 4px;">0</span>
                </div>
                <div class="admin-badge">
                    <span class="dot"></span>
                    <span>{{ auth()->user()->name }}</span>
                </div>
            </div>
        </header>

        <main class="page-content" id="page-content">
            @yield('content')
        </main>
    </div>

    {{-- ── Custom Global Scripts ── --}}
    <script>
        // Set Axios CSRF token globally
        axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        axios.defaults.headers.common['Accept'] = 'application/json';

        // Toast helper
        const Toast = Swal.mixin({
            toast: true, position: 'top-end',
            showConfirmButton: false, timer: 3000,
            timerProgressBar: true
        });
        function showSuccess(msg) { Toast.fire({ icon: 'success', title: msg }); }
        function showError(msg) { Toast.fire({ icon: 'error', title: msg }); }

        // Confirm helper
        async function confirmAction(title = 'هل أنت متأكد؟', text = '', confirmText = 'نعم') {
            const result = await Swal.fire({
                title, text,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: confirmText,
                cancelButtonText: 'إلغاء',
                background: '#1e293b',
                color: '#f1f5f9'
            });
            return result.isConfirmed;
        }

        // Format money
        function formatMoney(val) {
            return parseFloat(val || 0).toLocaleString('ar-EG', { minimumFractionDigits: 2 }) + ' ج';
        }

        // Format date Arabic
        function formatDate(str) {
            if (!str) return '—';
            const d = new Date(str);
            return d.toLocaleDateString('ar-EG', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
        }

        // Status badge
        function statusBadge(status) {
            const map = {
                pending: ['باقي', 'badge-yellow'],
                received: ['مسلم للمندوب', 'badge-blue'],
                delivered: ['تم التوصيل', 'badge-green'],
                cancelled: ['ملغي', 'badge-red'],
            };
            const [label, cls] = map[status] || [status, 'badge-gray'];
            return `<span class="badge ${cls}">${label}</span>`;
        }

        // Modal helpers
        function openModal(id) { document.getElementById(id).classList.add('open'); }
        function closeModal(id) { document.getElementById(id).classList.remove('open'); }
        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('modal-overlay')) {
                e.target.classList.remove('open');
            }
        });
    </script>

    {{-- ── SPA Navigation Engine ── --}}
    <script>
        // ── Polling lifecycle management ──
        window._spaPollingIds = [];
        function addPolling(id) { window._spaPollingIds.push(id); }
        function clearAllPolling() {
            window._spaPollingIds.forEach(id => clearInterval(id));
            window._spaPollingIds = [];
        }

        // ── Re-execute injected <script> tags ──
        function executeScripts(container) {
            container.querySelectorAll('script').forEach(old => {
                const s = document.createElement('script');
                [...old.attributes].forEach(a => s.setAttribute(a.name, a.value));
                s.textContent = old.textContent;
                old.parentNode.replaceChild(s, old);
            });
        }

        // ── Update active sidebar link ──
        function updateActiveLink(url) {
            const path = new URL(url, location.origin).pathname;
            document.querySelectorAll('.sidebar .nav-link[data-spa]').forEach(a => {
                const aPath = new URL(a.href, location.origin).pathname;
                a.classList.toggle('active', path === aPath || (path.startsWith(aPath) && aPath !== '/admin'));
            });
        }

        // ── SPA Navigate ──
        async function navigate(url, pushState = true) {
            // Don't intercept non-SPA links
            if (!url) return;
            const bar = document.getElementById('spa-loading-bar');
            bar.style.display = 'block';
            bar.style.width = '30%';
            try {
                clearAllPolling();
                const res = await axios.get(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-SPA-Navigation': '1',
                    }
                });
                bar.style.width = '80%';
                const { html, title, csrf_token } = res.data;
                // Update CSRF token
                if (csrf_token) {
                    document.querySelector('meta[name="csrf-token"]').setAttribute('content', csrf_token);
                    axios.defaults.headers.common['X-CSRF-TOKEN'] = csrf_token;
                }
                // Inject content
                const content = document.getElementById('page-content');
                content.innerHTML = html;
                executeScripts(content);
                // Update topbar title
                if (title) {
                    document.getElementById('spa-page-title').textContent = title;
                    document.title = title + ' - دور فاست';
                }
                // Update URL & history
                if (pushState) history.pushState({ url }, title || '', url);
                updateActiveLink(url);
                // Scroll to top
                content.parentElement.scrollTop = 0;
            } catch (err) {
                // Fallback: full page nav on error
                window.location.href = url;
            } finally {
                bar.style.width = '100%';
                setTimeout(() => { bar.style.display = 'none'; bar.style.width = '0'; }, 300);
            }
        }

        // ── Intercept sidebar clicks ──
        document.querySelectorAll('.sidebar .nav-link[data-spa]').forEach(a => {
            a.addEventListener('click', e => {
                e.preventDefault();
                navigate(a.href);
            });
        });

        // ── Browser back/forward ──
        window.addEventListener('popstate', e => {
            if (e.state && e.state.url) navigate(e.state.url, false);
        });

        // ── Init: set active link on first load ──
        document.addEventListener('DOMContentLoaded', () => {
            updateActiveLink(location.href);
            history.replaceState({ url: location.href }, document.title, location.href);
        });
    </script>

    <div id="notif-panel" style="display:none;position:fixed;top:64px;left:0;width:360px;max-height:80vh;
         overflow-y:auto;background:var(--card-bg);border:1px solid var(--border);border-radius:0 0 16px 16px;
         z-index:500;box-shadow:0 8px 24px rgba(0,0,0,.4);">
        <div style="padding:14px 18px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between">
            <strong>التنبيهات</strong>
            <button onclick="markAllRead()" class="btn btn-sm btn-secondary"
                style="border:1px solid var(--border);border-radius:6px;background:none;cursor:pointer;padding:4px 8px;font-size:11px;">تحديد
                كمقروء</button>
        </div>
        <div id="notif-list" style="padding:8px 0;"></div>
    </div>

    <script>
        // ── Admin Notifications ──────────────────────────────────────
        var _notifOpen = false;
        var _notifCount = 0;

        function toggleNotifPanel() {
            _notifOpen = !_notifOpen;
            document.getElementById('notif-panel').style.display = _notifOpen ? 'block' : 'none';
            if (_notifOpen) loadNotifications();
        }

        async function loadNotifications() {
            try {
                var res = await axios.get('/admin/notifications');
                var list = document.getElementById('notif-list');
                _notifCount = res.data.unread_count;
                updateNotifBadge();
                if (!res.data.items.length) {
                    list.innerHTML = '<div style="padding:20px;text-align:center;color:var(--text-muted)">لا تنبيهات جديدة</div>';
                    return;
                }
                list.innerHTML = res.data.items.map(function (n) {
                    var bgColor = n.is_read ? 'transparent' : 'rgba(245,158,11,0.08)';
                    var icon = n.type === 'cancelled' ? '❌' : n.type === 'delayed_delivery' ? '⏱️' : '⚠️';
                    return '<div style="padding:12px 18px;border-bottom:1px solid var(--border);background:' + bgColor + '">' +
                        '<div style="font-size:13px;font-weight:600">' + icon + ' ' + n.message + '</div>' +
                        '<div style="font-size:11px;color:var(--text-muted);margin-top:3px">' + new Date(n.created_at).toLocaleString("ar-EG") + '</div>' +
                        '</div>';
                }).join('');
            } catch (e) { console.error(e); }
        }

        function updateNotifBadge() {
            var badge = document.getElementById('notif-count-badge');
            if (_notifCount > 0) { badge.style.display = 'block'; badge.textContent = _notifCount; }
            else badge.style.display = 'none';
        }

        async function markAllRead() {
            await axios.post('/admin/notifications/read-all');
            _notifCount = 0;
            updateNotifBadge();
            loadNotifications();
        }

        // Real-time: listen on admin-notifications channel
        if (typeof window.Echo !== 'undefined') {
            window.Echo.channel('admin-notifications').listen('AdminNotificationCreated', function (e) {
                _notifCount++;
                updateNotifBadge();
                if (_notifOpen) loadNotifications();
                var ToastInstance = window.Swal ? Swal.mixin({ toast: true, position: 'top-start', showConfirmButton: false, timer: 5000 }) : null;
                if (ToastInstance) ToastInstance.fire({ icon: 'warning', title: e.message });
            });
        }

        // Initial badge count
        axios.get('/admin/notifications/count').then(function (r) { _notifCount = r.data.count; updateNotifBadge(); }).catch(e => console.error(e));
    </script>

    {{-- ── Shared Excel Export Utility ── --}}
    <script>
        window.exportToExcel = function (data, columns, filename, sheetName) {
            // columns = [{header: 'اسم العمود', key: 'مفتاح البيانات', width: 20}]
            const wb = XLSX.utils.book_new();
            const wsData = [columns.map(c => c.header)];
            data.forEach(row => {
                wsData.push(columns.map(c => {
                    const val = c.key.split('.').reduce((o, k) => o?.[k], row);
                    return val ?? '—';
                }));
            });
            const ws = XLSX.utils.aoa_to_sheet(wsData);
            columns.forEach((col, i) => {
                if (!ws['!cols']) ws['!cols'] = [];
                ws['!cols'][i] = { wch: col.width || 20 };
            });
            XLSX.utils.book_append_sheet(wb, ws, sheetName || 'Sheet1');
            XLSX.writeFile(wb, filename + '.xlsx');
        };
    </script>

    @stack('scripts')
</body>

</html>