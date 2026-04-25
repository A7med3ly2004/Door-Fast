{{-- Delivery Dashboard SPA partial --}}
<style>
.grid-kpi { display:grid; grid-template-columns:repeat(4,1fr); gap:20px; margin-bottom:30px; }
.kpi-card { background:white; border-radius:12px; padding:20px; box-shadow:0 2px 4px rgba(0,0,0,0.05); border:1px solid var(--border-color); }
.kpi-title { color:var(--text-muted); font-size:14px; font-weight:600; margin-bottom:10px; }
.kpi-value { font-size:28px; font-weight:800; color:var(--text-dark); }
.kpi-value.money { color:var(--success); }
.kpi-value.orders { color:var(--primary); }
.progress-container { background:white; border-radius:12px; padding:20px; box-shadow:0 2px 4px rgba(0,0,0,0.05); border:1px solid var(--border-color); }
.progress-header { display:flex; justify-content:space-between; margin-bottom:15px; }
.progress-bar-wrap { height:20px; background-color:#f3f4f6; border-radius:10px; overflow:hidden; }
.progress-bar { height:100%; background-color:var(--primary); width:0%; transition:width 0.5s ease; }
.progress-bar.full { background-color:var(--secondary); }

/* MOBILE: responsive dashboard KPI grid */
@media (max-width: 768px) {
    /* MOBILE: 2 columns instead of 4 on mobile */
    .grid-kpi { grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 16px; }
    /* MOBILE: smaller KPI card styling */
    .kpi-card { padding: 14px; }
    .kpi-title { font-size: 12px; margin-bottom: 6px; }
    .kpi-value { font-size: 20px; }
    .progress-container { padding: 14px; }
}
</style>

<div class="grid-kpi">
    <div class="kpi-card"><div class="kpi-title">وقت بدء الشفت</div><div class="kpi-value" id="kpi-started-at">--:--</div></div>
    <div class="kpi-card"><div class="kpi-title">مدة العمل</div><div class="kpi-value" id="kpi-duration">00:00:00</div></div>
    <div class="kpi-card"><div class="kpi-title">عدد الطلبات الكاملة</div><div class="kpi-value orders" id="kpi-delivered-count">0</div></div>
    <div class="kpi-card"><div class="kpi-title">عدد الطلبات المعلقة</div><div class="kpi-value orders" id="kpi-received-count">0</div></div>
    <div class="kpi-card"><div class="kpi-title">التحصيل اليومي</div><div class="kpi-value money" id="kpi-total-collected">0 ج</div></div>
    <div class="kpi-card"><div class="kpi-title">إجمالي خدمة التوصيل</div><div class="kpi-value money" id="kpi-total-fee">0 ج</div></div>
    <div class="kpi-card"><div class="kpi-title">إجمالي الخصومات</div><div class="kpi-value" id="kpi-total-discount">0 ج</div></div>
    <div class="kpi-card"><div class="kpi-title">عدد الطلبات الملغية</div><div class="kpi-value" style="color:var(--secondary)" id="kpi-cancelled-count">0</div></div>
</div>


<script>
var shiftStartedTimestamp = null;
var durationInterval = null;

function fetchDashboardData() {
    const startedEl = document.getElementById('kpi-started-at');
    if (!startedEl) return; // Not on dashboard page

    axios.get('{{ route("delivery.dashboard.data") }}').then(res => {
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
            'kpi-total-discount': data.total_discount + ' ج'
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

    var baseDiff = window.previousWorkedSeconds || 0;

    if (!shiftStartedTimestamp) {
        if (baseDiff > 0) {
            var h = Math.floor(baseDiff/3600).toString().padStart(2,'0');
            var m = Math.floor((baseDiff%3600)/60).toString().padStart(2,'0');
            var s = (baseDiff%60).toString().padStart(2,'0');
            el.innerText = `${h}:${m}:${s}`;
        } else {
            el.innerText = '00:00:00';
        }
        return;
    }

    durationInterval = setInterval(() => {
        var diff = baseDiff + (Math.floor(Date.now() / 1000) - shiftStartedTimestamp);
        if (diff >= 0) {
            var h = Math.floor(diff/3600).toString().padStart(2,'0');
            var m = Math.floor((diff%3600)/60).toString().padStart(2,'0');
            var s = (diff%60).toString().padStart(2,'0');
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
