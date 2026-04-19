{{-- Reserve Delivery Dashboard SPA partial --}}
<style>
.kpi-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:12px; margin-bottom:20px; }
.kpi-card { background:white; border-radius:12px; padding:16px; box-shadow:0 2px 4px rgba(0,0,0,0.05); border:1px solid var(--border-color); }
.kpi-title { color:var(--text-muted); font-size:13px; font-weight:600; margin-bottom:8px; }
.kpi-value { font-size:24px; font-weight:800; color:var(--text-dark); }
.kpi-value.money { color:var(--success); }
.kpi-value.orders { color:var(--primary); }
.progress-container { background:white; border-radius:12px; padding:16px; box-shadow:0 2px 4px rgba(0,0,0,0.05); border:1px solid var(--border-color); }
.progress-header { display:flex; justify-content:space-between; margin-bottom:10px; font-size:14px; }
.progress-bar-wrap { height:16px; background-color:#f3f4f6; border-radius:8px; overflow:hidden; }
.progress-bar { height:100%; background-color:var(--primary); width:0%; transition:width 0.5s ease; }
.progress-bar.full { background-color:var(--secondary); }
</style>

<div style="background-color:#fef3c7;color:var(--primary);padding:15px;border-radius:12px;font-weight:700;font-size:14px;margin-bottom:20px;text-align:center;border:1px solid #fde68a">
    أنت دلفري احتياطي — تصلك الطلبات بعد <span id="kpi-delay-min">{{ \App\Models\Setting::where('key','reserve_delay_minutes')->value('value') ?? 5 }}</span> دقائق من عرضها على الدلفري الأصلي
</div>

<div class="kpi-grid">
    <div class="kpi-card"><div class="kpi-title">وقت الانتظار</div><div class="kpi-value" style="color:var(--primary)" id="kpi-wait-time">{{ \App\Models\Setting::where('key','reserve_delay_minutes')->value('value') ?? 5 }} دقيقة</div></div>
    <div class="kpi-card"><div class="kpi-title">وقت بدء الشفت</div><div class="kpi-value" id="kpi-started-at">--:--</div></div>
    <div class="kpi-card"><div class="kpi-title">الطلبات الموصلة</div><div class="kpi-value orders" id="kpi-delivered-count">0</div></div>
    <div class="kpi-card"><div class="kpi-title">مستلمة (الآن)</div><div class="kpi-value orders" id="kpi-received-count">0</div></div>
    <div class="kpi-card"><div class="kpi-title">التحصيل الكلي</div><div class="kpi-value money" id="kpi-total-collected">0 ج</div></div>
</div>

<script>
var durationInterval;
function fetchDashboardData() {
    if (!isShiftActive) return;
    axios.get('{{ route("reserve.dashboard.data") }}').then(res => {
        var data = res.data;
        var elStartedAt = document.getElementById('kpi-started-at');
        if (elStartedAt) elStartedAt.innerText = data.started_at || '--:--';
        
        var elDelivered = document.getElementById('kpi-delivered-count');
        if (elDelivered) elDelivered.innerText = data.delivered_count;
        
        var elReceived = document.getElementById('kpi-received-count');
        if (elReceived) elReceived.innerText = data.received_count;
        
        var elTotal = document.getElementById('kpi-total-collected');
        if (elTotal) elTotal.innerText = data.total_collected + ' ج';
        
    });
}
function onShiftStarted() { 
    fetchDashboardData(); 
    if (durationInterval) clearInterval(durationInterval);
    durationInterval = setInterval(() => {
        var el = document.getElementById('kpi-duration');
        if (!el) { clearInterval(durationInterval); return; }
        var diff = Math.floor(Date.now() / 1000) - shiftStartedTimestamp;
        if (diff >= 0) {
            var h = Math.floor(diff/3600).toString().padStart(2,'0');
            var m = Math.floor((diff%3600)/60).toString().padStart(2,'0');
            var s = (diff%60).toString().padStart(2,'0');
            el.innerText = `${h}:${m}:${s}`;
        }
    }, 1000);
    if (typeof addPolling === 'function') addPolling(durationInterval);
}
setTimeout(() => { if (isShiftActive) fetchDashboardData(); }, 500);
if (typeof addPolling === 'function') {
    addPolling(setInterval(fetchDashboardData, 30000));
} else {
    setInterval(fetchDashboardData, 30000);
}
</script>
