<style>
    #activity-feed {
        scrollbar-width: none; /* Firefox */
        -ms-overflow-style: none; /* IE and Edge */
    }
    #activity-feed::-webkit-scrollbar {
        display: none; /* Chrome, Safari, and Opera */
    }
</style>

{{-- KPI Cards --}}
<div class="kpi-grid" id="kpi-grid">
    <div class="kpi-card blue" id="kpi-orders">
        <div class="kpi-label">إجمالي الطلبات اليوم</div>
        <div class="kpi-value" id="v-orders">—</div>
    </div>
    <div class="kpi-card green" id="kpi-completed">
        <div class="kpi-label">تم التوصيلة اليوم</div>
        <div class="kpi-value" id="v-completed">—</div>
    </div>
    <div class="kpi-card yellow">
        <div class="kpi-label">معلقة اليوم</div>
        <div class="kpi-value" id="v-pending">—</div>
    </div>
    <div class="kpi-card red">
        <div class="kpi-label">ملغاة اليوم</div>
        <div class="kpi-value" id="v-cancelled">—</div>
    </div>
    <div class="kpi-card green">
        <div class="kpi-label">إيرادات اليوم</div>
        <div class="kpi-value" id="v-daily">—</div>
        <div class="kpi-sub">ج.م</div>
    </div>
    <div class="kpi-card green">
        <div class="kpi-label">إيرادات الشهر</div>
        <div class="kpi-value" id="v-monthly">—</div>
        <div class="kpi-sub">ج.م</div>
    </div>
    <div class="kpi-card blue">
        <div class="kpi-label">إجمالي العملاء</div>
        <div class="kpi-value" id="v-clients">—</div>
    </div>
</div>

<div class="grid-2" style="gap:20px; margin-bottom:20px">
    <div class="card">
        <div class="card-title">الطلبات - آخر 7 أيام</div>
        <div class="chart-container" style="height:220px">
            <canvas id="ordersChart"></canvas>
        </div>
    </div>
    <div class="card">
        <div class="card-title">آخر الأنشطة <span style="font-size:11px;color:var(--text-muted);font-weight:400">(يتجدد
                تلقائياً)</span></div>
        <div id="activity-feed" style="max-height:220px;overflow-y:auto">
            <div class="text-muted" style="text-align:center;padding:20px">جاري التحميل...</div>
        </div>
    </div>
</div>

<div class="card" style="margin-bottom:20px">
    <div class="card-title">آخر 5 طلبات <span style="font-size:11px;color:var(--text-muted);font-weight:400">(يتجدد كل
            30 ثانية)</span></div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="text-align:center">رقم الطلب</th>
                    <th style="text-align:center">العميل</th>
                    <th style="text-align:center">كول سنتر</th>
                    <th style="text-align:center">المندوب</th>
                    <th style="text-align:center">الإجمالي</th>
                    <th style="text-align:center">الحالة</th>
                    <th style="text-align:center">التاريخ</th>
                </tr>
            </thead>
            <tbody id="recent-orders-body">
                <tr>
                    <td colspan="7" style="text-align:center;color:var(--text-muted)">جاري التحميل...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="grid-2" style="gap:20px">
    <div class="card">
        <div class="card-title">أداء المناديب اليوم</div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>المندوب</th>
                        <th>تم التوصيلة</th>
                        <th>الإيراد</th>
                    </tr>
                </thead>
                <tbody id="delivery-perf-body">
                    <tr>
                        <td colspan="3" style="text-align:center;color:var(--text-muted)">لا بيانات</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card">
        <div class="card-title">أداء الكول سنتر اليوم</div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>الموظف</th>
                        <th>أنشأ</th>
                        <th>ملغاة</th>
                        <th>الإيراد</th>
                    </tr>
                </thead>
                <tbody id="cc-perf-body">
                    <tr>
                        <td colspan="4" style="text-align:center;color:var(--text-muted)">لا بيانات</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    var ordersChart = null;

    async function loadStats() {
        if (!document.getElementById('v-orders')) return;
        try {
            const { data } = await axios.get('{{ route("admin.dashboard.stats") }}');
            if (!document.getElementById('v-orders')) return; // double check after await
            var k = data.kpis;
            document.getElementById('v-orders').textContent = k.orders_today;
            document.getElementById('v-completed').textContent = k.completed_today;
            document.getElementById('v-pending').textContent = k.pending_today;
            document.getElementById('v-cancelled').textContent = k.cancelled_today;
            document.getElementById('v-daily').textContent = parseFloat(k.daily_revenue).toLocaleString('en-US', { minimumFractionDigits: 2 });
            document.getElementById('v-monthly').textContent = parseFloat(k.monthly_revenue).toLocaleString('en-US', { minimumFractionDigits: 2 });
            document.getElementById('v-clients').textContent = k.total_clients;

            var labels = data.chart.map(d => d.label);
            var counts = data.chart.map(d => d.count);
            var ctx = document.getElementById('ordersChart').getContext('2d');
            if (ordersChart) ordersChart.destroy();
            ordersChart = new Chart(ctx, {
                type: 'bar',
                data: { labels, datasets: [{ label: 'عدد الطلبات', data: counts, backgroundColor: '#f59e0b', borderRadius: 6 }] },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { color: '#334155' }, ticks: { color: '#94a3b8' } },
                        y: { grid: { color: '#334155' }, ticks: { color: '#94a3b8', stepSize: 1 }, beginAtZero: true }
                    }
                }
            });

            var dpBody = document.getElementById('delivery-perf-body');
            dpBody.innerHTML = data.delivery_perf.length === 0
                ? '<tr><td colspan="3" style="text-align:center;color:var(--text-muted)">لا بيانات اليوم</td></tr>'
                : data.delivery_perf.map(d => `<tr><td>${d.name}</td><td><span class="badge badge-green">${d.completed}</span></td><td>${parseFloat(d.revenue).toLocaleString('en-US', { minimumFractionDigits: 2 })} ج</td></tr>`).join('');

            var ccBody = document.getElementById('cc-perf-body');
            ccBody.innerHTML = data.cc_perf.length === 0
                ? '<tr><td colspan="4" style="text-align:center;color:var(--text-muted)">لا بيانات اليوم</td></tr>'
                : data.cc_perf.map(cc => `<tr><td>${cc.name}</td><td>${cc.created}</td><td><span class="badge badge-red">${cc.cancelled}</span></td><td>${parseFloat(cc.revenue).toLocaleString('en-US', { minimumFractionDigits: 2 })} ج</td></tr>`).join('');
        } catch (e) { console.error('stats error', e); }
    }

    async function loadRecentOrders() {
        if (!document.getElementById('recent-orders-body')) return;
        try {
            const { data } = await axios.get('{{ route("admin.dashboard.recent-orders") }}');
            var body = document.getElementById('recent-orders-body');
            if (!body) return;
            body.innerHTML = !data.orders.length
                ? '<tr><td colspan="7" style="text-align:center;color:var(--text-muted)">لا طلبات</td></tr>'
                : data.orders.map(o => `<tr>
                <td style="text-align:center"><strong>${o.order_number}</strong></td>
                <td style="text-align:center">${o.client}</td><td style="text-align:center">${o.callcenter}</td><td style="text-align:center">${o.delivery}</td>
                <td style="text-align:center">${parseFloat(o.total).toLocaleString('en-US', { minimumFractionDigits: 2 })} ج</td>
                <td style="text-align:center">${statusBadge(o.status)}</td>
                <td style="text-align:center;color:var(--text-muted);font-size:12px">${formatDate(o.created_at)}</td>
            </tr>`).join('');
        } catch (e) { console.error('orders error', e); }
    }

    async function loadActivity() {
        if (!document.getElementById('activity-feed')) return;
        try {
            const { data } = await axios.get('{{ route("admin.dashboard.activity") }}');
            var feed = document.getElementById('activity-feed');
            if (!feed) return;
            feed.innerHTML = !data.logs.length
                ? '<div style="text-align:center;color:var(--text-muted);padding:16px">لا أنشطة</div>'
                : data.logs.map(l => `<div style="padding:8px 0;border-bottom:1px solid var(--border);font-size:12px">
                <div style="display:flex;justify-content:space-between;margin-bottom:2px">
                    <span style="font-weight:700;color:var(--yellow)">${l.order_number}</span>
                    <span style="color:var(--text-muted)">${formatDate(l.created_at)}</span>
                </div>
                <div>${l.action} — <span style="color:var(--text-muted)">${l.user}</span></div>
                ${l.notes ? `<div style="color:var(--text-muted)">${l.notes}</div>` : ''}
            </div>`).join('');
        } catch (e) { console.error('activity error', e); }
    }

    loadStats();
    loadRecentOrders();
    loadActivity();

    // Register polling so SPA navigation can clear it
    if (typeof addPolling === 'function') {
        addPolling(setInterval(() => { loadStats(); loadRecentOrders(); }, 30000));
        addPolling(setInterval(loadActivity, 20000));
    } else {
        setInterval(() => { loadStats(); loadRecentOrders(); }, 30000);
        setInterval(loadActivity, 20000);
    }
</script>