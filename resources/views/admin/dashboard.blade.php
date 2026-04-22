@extends('layouts.admin')

@section('page-title', 'لوحة التحكم')

@section('content')
    {{-- KPI Cards --}}
    <div class="kpi-grid" id="kpi-grid">
        <div class="kpi-card" id="kpi-orders">
            <div class="kpi-label" style="border-right: 4px solid #0891b2;">إجمالي الطلبات اليوم</div>
            <div class="kpi-value" id="v-orders">—</div>
        </div>
        <div class="kpi-card" id="kpi-completed">
            <div class="kpi-label" style="border-right: 4px solid #0891b2;">مُوصَّلة اليوم</div>
            <div class="kpi-value" id="v-completed">—</div>
        </div>
        <div class="kpi-card" style="--kpi-color:var(--yellow);">
            <div class="kpi-label">معلقة اليوم</div>
            <div class="kpi-value" id="v-pending">—</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">ملغاة اليوم</div>
            <div class="kpi-value" id="v-cancelled">—</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">إيرادات اليوم</div>
            <div class="kpi-value" id="v-daily">—</div>
            <div class="kpi-sub">ج.م</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">إيرادات الشهر</div>
            <div class="kpi-value" id="v-monthly">—</div>
            <div class="kpi-sub">ج.م</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">إجمالي العملاء</div>
            <div class="kpi-value" id="v-clients">—</div>
        </div>
    </div>

    <div class="grid-2" style="gap:20px; margin-bottom:20px">
        {{-- Orders Chart --}}
        <div class="card">
            <div class="card-title">📊 الطلبات - آخر 7 أيام</div>
            <div class="chart-container" style="height:220px">
                <canvas id="ordersChart"></canvas>
            </div>
        </div>

        {{-- Activity Feed --}}
        <div class="card">
            <div class="card-title">🕐 آخر الأنشطة <span
                    style="font-size:11px;color:var(--text-muted);font-weight:400">(يتجدد تلقائياً)</span></div>
            <div id="activity-feed" style="max-height:220px;overflow-y:auto">
                <div class="text-muted" style="text-align:center;padding:20px">جاري التحميل...</div>
            </div>
        </div>
    </div>

    {{-- Recent Orders --}}
    <div class="card" style="margin-bottom:20px">
        <div class="card-title">🧾 آخر 5 طلبات <span style="font-size:11px;color:var(--text-muted);font-weight:400">(يتجدد
                كل 30 ثانية)</span></div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>رقم الطلب</th>
                        <th>العميل</th>
                        <th>كول سنتر</th>
                        <th>المندوب</th>
                        <th>الإجمالي</th>
                        <th>الحالة</th>
                        <th>التاريخ</th>
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
        {{-- Delivery Performance --}}
        <div class="card">
            <div class="card-title">🚴 أداء المناديب اليوم</div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>المندوب</th>
                            <th>مُوصَّلة</th>
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

        {{-- Callcenter Performance --}}
        <div class="card">
            <div class="card-title">📞 أداء الكول سنتر اليوم</div>
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
@endsection

@push('scripts')
    <script>
        var ordersChart = null;

        async function loadStats() {
            try {
                const { data } = await axios.get('{{ route("admin.dashboard.stats") }}');
                const k = data.kpis;
                document.getElementById('v-orders').textContent = k.orders_today;
                document.getElementById('v-completed').textContent = k.completed_today;
                document.getElementById('v-pending').textContent = k.pending_today;
                document.getElementById('v-cancelled').textContent = k.cancelled_today;
                document.getElementById('v-daily').textContent = parseFloat(k.daily_revenue).toLocaleString('ar-EG', { minimumFractionDigits: 2 });
                document.getElementById('v-monthly').textContent = parseFloat(k.monthly_revenue).toLocaleString('ar-EG', { minimumFractionDigits: 2 });
                document.getElementById('v-clients').textContent = k.total_clients;

                // Chart
                const labels = data.chart.map(d => d.label);
                const counts = data.chart.map(d => d.count);
                const ctx = document.getElementById('ordersChart').getContext('2d');
                if (ordersChart) ordersChart.destroy();
                ordersChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: [{
                            label: 'عدد الطلبات',
                            data: counts,
                            backgroundColor: '#f59e0b',
                            borderRadius: 6,
                        }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            x: { grid: { color: '#334155' }, ticks: { color: '#94a3b8' } },
                            y: { grid: { color: '#334155' }, ticks: { color: '#94a3b8', stepSize: 1 }, beginAtZero: true }
                        }
                    }
                });

                // Delivery perf
                const dpBody = document.getElementById('delivery-perf-body');
                if (data.delivery_perf.length === 0) {
                    dpBody.innerHTML = '<tr><td colspan="3" style="text-align:center;color:var(--text-muted)">لا بيانات اليوم</td></tr>';
                } else {
                    dpBody.innerHTML = data.delivery_perf.map(d => `
                        <tr>
                            <td>${d.name}</td>
                            <td><span class="badge badge-green">${d.completed}</span></td>
                            <td>${parseFloat(d.revenue).toLocaleString('ar-EG', { minimumFractionDigits: 2 })} ج</td>
                        </tr>`).join('');
                }

                // CC perf
                const ccBody = document.getElementById('cc-perf-body');
                if (data.cc_perf.length === 0) {
                    ccBody.innerHTML = '<tr><td colspan="4" style="text-align:center;color:var(--text-muted)">لا بيانات اليوم</td></tr>';
                } else {
                    ccBody.innerHTML = data.cc_perf.map(cc => `
                        <tr>
                            <td>${cc.name}</td>
                            <td>${cc.created}</td>
                            <td><span class="badge badge-red">${cc.cancelled}</span></td>
                            <td>${parseFloat(cc.revenue).toLocaleString('ar-EG', { minimumFractionDigits: 2 })} ج</td>
                        </tr>`).join('');
                }
            } catch (e) { console.error('stats error', e); }
        }

        async function loadRecentOrders() {
            try {
                const { data } = await axios.get('{{ route("admin.dashboard.recent-orders") }}');
                const body = document.getElementById('recent-orders-body');
                if (!data.orders.length) {
                    body.innerHTML = '<tr><td colspan="7" style="text-align:center;color:var(--text-muted)">لا طلبات</td></tr>';
                    return;
                }
                body.innerHTML = data.orders.map(o => `
                    <tr>
                        <td><strong>${o.order_number}</strong></td>
                        <td>${o.client}</td>
                        <td>${o.callcenter}</td>
                        <td>${o.delivery}</td>
                        <td>${parseFloat(o.total).toLocaleString('ar-EG', { minimumFractionDigits: 2 })} ج</td>
                        <td>${statusBadge(o.status)}</td>
                        <td style="color:var(--text-muted);font-size:12px">${formatDate(o.created_at)}</td>
                    </tr>`).join('');
            } catch (e) { console.error('orders error', e); }
        }

        async function loadActivity() {
            try {
                const { data } = await axios.get('{{ route("admin.dashboard.activity") }}');
                const feed = document.getElementById('activity-feed');
                if (!data.logs.length) {
                    feed.innerHTML = '<div style="text-align:center;color:var(--text-muted);padding:16px">لا أنشطة</div>';
                    return;
                }
                feed.innerHTML = data.logs.map(l => `
                    <div style="padding:8px 0;border-bottom:1px solid var(--border);font-size:12px">
                        <div style="display:flex;justify-content:space-between;margin-bottom:2px">
                            <span style="font-weight:700;color:var(--yellow)">${l.order_number}</span>
                            <span style="color:var(--text-muted)">${formatDate(l.created_at)}</span>
                        </div>
                        <div>${l.action} — <span style="color:var(--text-muted)">${l.user}</span></div>
                        ${l.notes ? `<div style="color:var(--text-muted)">${l.notes}</div>` : ''}
                    </div>`).join('');
            } catch (e) { console.error('activity error', e); }
        }

        // Initial load
        loadStats();
        loadRecentOrders();
        loadActivity();

        // Polling
        setInterval(() => { loadStats(); loadRecentOrders(); }, 30000);
        setInterval(loadActivity, 20000);
    </script>
@endpush