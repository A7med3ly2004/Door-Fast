{{-- Callcenter Stats SPA partial --}}
<div class="section-header"><h2>📊 إحصائياتي اليوم</h2><span id="last-updated" style="font-size:12px;color:var(--text-muted)"></span></div>
<div class="kpi-grid" id="kpi-grid" style="margin-bottom:24px">
    <div class="kpi-card"><div class="kpi-label">طلباتي اليوم</div><div class="kpi-value" id="k-orders">—</div></div>
    <div class="kpi-card green"><div class="kpi-label">تم التوصيل</div><div class="kpi-value" id="k-delivered">—</div></div>
    <div class="kpi-card red"><div class="kpi-label">ملغي</div><div class="kpi-value" id="k-cancelled">—</div></div>
    <div class="kpi-card blue"><div class="kpi-label">إيراداتي اليوم</div><div class="kpi-value" id="k-revenue">—</div><div class="kpi-sub">جنيه</div></div>
    <div class="kpi-card" style="border:1px solid var(--yellow)"><div class="kpi-label" style="color:var(--yellow)">خدمة التوصيل</div><div class="kpi-value" id="k-fees">—</div><div class="kpi-sub">جنيه</div></div>
    <div class="kpi-card"><div class="kpi-label">الخصومات</div><div class="kpi-value" id="k-discount">—</div><div class="kpi-sub">جنيه</div></div>
</div>
<div class="card" style="margin-bottom:20px"><div class="card-title">📈 طلباتي آخر 7 أيام</div><div class="chart-container" style="height:220px"><canvas id="chartBar"></canvas></div></div>
<div class="card"><div class="card-title">🚴 أداء المناديب (من طلباتي)</div>
    <div class="table-wrap"><table>
        <thead><tr><th>المندوب</th><th>إجمالي الطلبات</th><th>مُوصَّل</th><th>ملغي</th><th>الإيراد</th></tr></thead>
        <tbody id="delivery-body"><tr><td colspan="5" style="text-align:center;padding:30px;color:var(--text-muted)">جاري التحميل...</td></tr></tbody>
    </table></div>
</div>
<script>
var chart;
async function loadStats() {
    if (!document.getElementById('kpi-grid')) return;
    try {
        const { data } = await axios.get('{{ route("callcenter.stats.data") }}');
        const { kpis, chart: chartData, deliveries } = data;
        document.getElementById('k-orders').textContent = kpis.ordersToday;
        document.getElementById('k-delivered').textContent = kpis.deliveredToday;
        document.getElementById('k-cancelled').textContent = kpis.cancelledToday;
        document.getElementById('k-revenue').textContent = parseFloat(kpis.revenueToday).toFixed(2);
        document.getElementById('k-fees').textContent = parseFloat(kpis.feesToday).toFixed(2);
        document.getElementById('k-discount').textContent = parseFloat(kpis.discountToday).toFixed(2);
        document.getElementById('last-updated').textContent = 'آخر تحديث: ' + new Date().toLocaleTimeString('ar-EG');
        var labels = chartData.map(d => d.label); const counts = chartData.map(d => d.count);
        if (chart) { chart.data.labels = labels; chart.data.datasets[0].data = counts; chart.update(); }
        else {
            chart = new Chart(document.getElementById('chartBar'), { type: 'bar', data: { labels, datasets: [{ label: 'الطلبات', data: counts, backgroundColor: 'rgba(245,158,11,0.7)', borderColor: '#f59e0b', borderWidth: 2, borderRadius: 6 }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { ticks: { color: '#94a3b8' }, grid: { color: '#334155' } }, y: { ticks: { color: '#94a3b8', precision: 0 }, grid: { color: '#334155' }, beginAtZero: true } } } });
        }
        var tbody = document.getElementById('delivery-body');
        if (!deliveries.length) tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:24px;color:var(--text-muted)">لا بيانات اليوم</td></tr>';
        else tbody.innerHTML = deliveries.map(d => `<tr><td><strong>${d.name}</strong></td><td>${d.total}</td><td><span class="badge badge-green">${d.delivered}</span></td><td><span class="badge badge-red">${d.cancelled}</span></td><td><strong style="color:var(--yellow)">${parseFloat(d.revenue).toFixed(2)} ج</strong></td></tr>`).join('');
    } catch(e) { console.error(e); }
}
loadStats();
if (typeof addPolling === 'function') addPolling(setInterval(loadStats, 30000));
else setInterval(loadStats, 30000);
</script>
