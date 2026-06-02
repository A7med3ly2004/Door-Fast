{{-- Reserve Delivery Dashboard SPA partial --}}
<style>
    .grid-kpi {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 30px;
    }

    .kpi-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        border: 1px solid var(--border-color);
    }

    .kpi-title {
        color: var(--text-muted);
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 10px;
    }

    .kpi-value {
        font-size: 28px;
        font-weight: 800;
        color: var(--text-dark);
    }

    .kpi-value.money {
        color: var(--success);
    }

    .kpi-value.orders {
        color: var(--primary);
    }

    /* MOBILE: responsive dashboard KPI grid */
    @media (max-width: 768px) {

        /* MOBILE: 2 columns instead of 4 on mobile */
        .grid-kpi {
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 16px;
        }

        /* MOBILE: smaller KPI card styling */
        .kpi-card {
            padding: 14px;
        }

        .kpi-title {
            font-size: 12px;
            margin-bottom: 6px;
        }

        .kpi-value {
            font-size: 20px;
        }
    }
</style>

<div class="grid-kpi">
    <div class="kpi-card">
        <div class="kpi-title">وقت بدء الشفت</div>
        <div class="kpi-value" id="kpi-started-at">--:--</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-title">مدة العمل</div>
        <div class="kpi-value" id="kpi-duration">00:00:00</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-title">عدد الطلبات الكاملة</div>
        <div class="kpi-value money" id="kpi-delivered-count">0</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-title">عدد الطلبات المعلقة</div>
        <div class="kpi-value orders" id="kpi-received-count">0</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-title">التحصيل اليومي</div>
        <div class="kpi-value money" id="kpi-total-collected">0 ج</div>
    </div>

    <div class="kpi-card">
        <div class="kpi-title">إجمالي الخصومات</div>
        <div class="kpi-value" style="color:var(--secondary)" id="kpi-total-discount">0 ج</div>
    </div>
    <div class="kpi-card" style="border-right:4px solid #a855f7;">
        <div class="kpi-title">الشريحة المحققة</div>
        <div class="kpi-value" id="kpi-tier-number" style="color:#a855f7">—</div>
    </div>
    <div class="kpi-card" style="border-right:4px solid #a855f7;">
        <div class="kpi-title">إجمالي الأرباح</div>
        <div class="kpi-value" id="kpi-total-profits" style="color:#a855f7">0 ج</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-title">عدد الطلبات الملغية</div>
        <div class="kpi-value" style="color:var(--secondary)" id="kpi-cancelled-count">0</div>
    </div>
</div>

<script>
    var shiftStartedTimestamp = null;
    var durationInterval = null;

    function fetchDashboardData() {
        const startedEl = document.getElementById('kpi-started-at');
        if (!startedEl) return; // Not on dashboard page

        axios.get('{{ route("reserve.dashboard.data") }}').then(res => {
            var data = res.data;
            startedEl.innerText = data.started_at || '--:--';
            shiftStartedTimestamp = data.started_timestamp;
            window.previousWorkedSeconds = data.previous_worked_seconds || 0;

            const ids = {
                'kpi-delivered-count': data.delivered_count,
                'kpi-received-count': data.received_count,
                'kpi-cancelled-count': data.cancelled_count,
                'kpi-total-collected': data.total_collected + ' ج',
                'kpi-total-fee': data.total_delivery_fee + ' ج',
                'kpi-total-discount': data.total_discount + ' ج',
                'kpi-tier-number': data.tier_number > 0 ? ('الشريحة ' + data.tier_number) : '— لا يوجد',
                'kpi-total-profits': data.total_profits + ' ج'
            };

            for (const [id, value] of Object.entries(ids)) {
                const el = document.getElementById(id);
                if (el) el.innerText = value;
            }

            startDurationTimer();
        }).catch(err => console.log('Dashboard fetch error:', err));
    }

    function startDurationTimer() {
        if (durationInterval) clearInterval(durationInterval);

        var el = document.getElementById('kpi-duration');
        if (!el) return;

        if (!shiftStartedTimestamp) {
            el.innerText = '00:00:00';
            return;
        }

        durationInterval = setInterval(() => {
            var diff = Math.floor(Date.now() / 1000) - shiftStartedTimestamp;
            if (diff >= 0) {
                var h = Math.floor(diff / 3600).toString().padStart(2, '0');
                var m = Math.floor((diff % 3600) / 60).toString().padStart(2, '0');
                var s = (diff % 60).toString().padStart(2, '0');
                el.innerText = `${h}:${m}:${s}`;
            }
        }, 1000);
        if (typeof addPolling === 'function') addPolling(durationInterval);
    }

    function onShiftStarted() { fetchDashboardData(); }

    setTimeout(() => { if (isShiftActive) fetchDashboardData(); }, 500);

    if (typeof addPolling === 'function') {
        addPolling(setInterval(fetchDashboardData, 30000));
    } else {
        setInterval(fetchDashboardData, 30000);
    }
</script>