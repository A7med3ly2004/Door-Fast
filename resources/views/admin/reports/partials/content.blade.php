{{-- Admin Reports SPA partial --}}
<div class="section-header">
    <h2>التقارير</h2>
    <a id="export-pdf-btn" href="{{ route('admin.reports.export-pdf') }}" target="_blank" class="btn btn-danger"
        data-no-spa>📄 تصدير PDF</a>
</div>
<div class="card" style="margin-bottom:20px">
    <div class="filter-bar">
        <input type="date" id="filter-from" class="form-control">
        <input type="date" id="filter-to" class="form-control">
        <select id="filter-delivery" class="form-select">
            <option value="">كل المناديب</option>
            @foreach($deliveries as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
        </select>
        <select id="filter-callcenter" class="form-select">
            <option value="">كل الكول سنتر</option>
            @foreach($callcenters as $cc)<option value="{{ $cc->id }}">{{ $cc->name }}</option>@endforeach
        </select>
        <button class="btn btn-primary" onclick="loadReport()">عرض</button>
        <button class="btn btn-secondary" onclick="resetReport()">إعادة</button>
    </div>
</div>
<div class="kpi-grid" style="margin-bottom:20px">
    <div class="kpi-card cyan">
        <div class="kpi-label">إجمالي الطلبات</div>
        <div class="kpi-value" id="r-total">—</div>
    </div>
    <div class="kpi-card green">
        <div class="kpi-label">مُوصَّلة</div>
        <div class="kpi-value" id="r-delivered">—</div>
    </div>
    <div class="kpi-card red">
        <div class="kpi-label">ملغاة</div>
        <div class="kpi-value" id="r-cancelled">—</div>
    </div>
    <div class="kpi-card yellow">
        <div class="kpi-label">معلقة</div>
        <div class="kpi-value" id="r-pending">—</div>
    </div>
    <div class="kpi-card blue">
        <div class="kpi-label">الإيرادات</div>
        <div class="kpi-value" id="r-revenue">—</div>
        <div class="kpi-sub">ج.م</div>
    </div>
</div>
<div class="card" style="margin-bottom:20px">
    <div class="card-title">الطلبات اليومية</div>
    <div class="chart-container" style="height:220px"><canvas id="reportChart"></canvas></div>
</div>
<div class="grid-2" style="gap:20px;margin-bottom:20px">
    <div class="card">
        <div class="card-title">أداء المناديب</div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th style="text-align: right;">المندوب</th>
                        <th style="text-align: center;">الطلبات</th>
                        <th style="text-align: center;">مُوصَّلة</th>
                        <th style="text-align: center;">الإيراد</th>
                    </tr>
                </thead>
                <tbody id="delivery-breakdown"></tbody>
            </table>
        </div>
    </div>
    <div class="card">
        <div class="card-title">أداء الكول سنتر</div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th style="text-align: right;">الموظف</th>
                        <th style="text-align: center;">أنشأ</th>
                        <th style="text-align: center;">ملغاة</th>
                        <th style="text-align: center;">الإيراد</th>
                    </tr>
                </thead>
                <tbody id="cc-breakdown"></tbody>
            </table>
        </div>
    </div>
</div>
<div class="card" style="padding:0">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border)"><strong>تفاصيل الطلبات</strong></div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="text-align: center;">رقم الطلب</th>
                    <th style="text-align: center;">التاريخ</th>
                    <th style="text-align: center;">العميل</th>
                    <th style="text-align: center;">كول سنتر</th>
                    <th style="text-align: center;">المندوب</th>
                    <th style="text-align: center;">توصيل</th>
                    <th style="text-align: center;">خصم</th>
                    <th style="text-align: center;">الإجمالي</th>
                    <th style="text-align: center;">الحالة</th>
                    <th style="text-align: center;">الإجراءات</th>
                </tr>
            </thead>
            <tbody id="report-orders"></tbody>
            <tfoot id="report-totals" style="background:var(--bg);font-weight:700"></tfoot>
        </table>
    </div>
    <div id="report-pagination" style="padding:16px"></div>
</div>
<script>
    var reportChart = null;
    function getFilters() {
        return { from: document.getElementById('filter-from').value, to: document.getElementById('filter-to').value, delivery_id: document.getElementById('filter-delivery').value, callcenter_id: document.getElementById('filter-callcenter').value };
    }
    function resetReport() {
        ['filter-from', 'filter-to', 'filter-delivery', 'filter-callcenter'].forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
        loadReport();
    }
    async function loadReport(page = 1) {
        var filters = getFilters();
        var params = new URLSearchParams(Object.fromEntries(Object.entries(filters).filter(([, v]) => v)));
        var pdfBtn = document.getElementById('export-pdf-btn');
        if (pdfBtn) pdfBtn.href = '{{ route("admin.reports.export-pdf") }}' + (params.toString() ? '?' + params.toString() : '');
        try {
            const { data } = await axios.get('{{ route("admin.reports.data") }}', { params: { ...filters, page } });
            document.getElementById('r-total').textContent = data.kpis.total;
            document.getElementById('r-delivered').textContent = data.kpis.delivered;
            document.getElementById('r-cancelled').textContent = data.kpis.cancelled;
            document.getElementById('r-pending').textContent = data.kpis.pending;
            document.getElementById('r-revenue').textContent = parseFloat(data.kpis.revenue).toLocaleString('ar-EG', { minimumFractionDigits: 2 });
            var ctx = document.getElementById('reportChart').getContext('2d');
            if (reportChart) reportChart.destroy();
            reportChart = new Chart(ctx, {
                type: 'bar',
                data: { labels: data.chart.map(d => d.label), datasets: [{ label: 'الطلبات', data: data.chart.map(d => d.count), backgroundColor: '#f59e0b', borderRadius: 4 }, { label: 'الإيراد', data: data.chart.map(d => d.revenue), backgroundColor: '#3b82f6', borderRadius: 4, yAxisID: 'y2' }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { color: '#94a3b8' } } }, scales: { x: { grid: { color: '#334155' }, ticks: { color: '#94a3b8' } }, y: { grid: { color: '#334155' }, ticks: { color: '#94a3b8' }, beginAtZero: true }, y2: { position: 'left', grid: { display: false }, ticks: { color: '#3b82f6' }, beginAtZero: true } } }
            });
            document.getElementById('delivery-breakdown').innerHTML = data.delivery_breakdown.length ? data.delivery_breakdown.map(d => `<tr><td style="text-align: right;">${d.name}</td><td style="text-align: center;">${d.total}</td><td style="text-align: center;"><span class="badge badge-green">${d.completed}</span></td><td style="text-align: center;">${parseFloat(d.revenue).toFixed(2)} ج</td></tr>`).join('') : '<tr><td colspan="5" style="text-align:center;color:var(--text-muted)">لا بيانات</td></tr>';
            document.getElementById('cc-breakdown').innerHTML = data.cc_breakdown.length ? data.cc_breakdown.map(cc => `<tr><td style="text-align: right;">${cc.name}</td><td style="text-align: center;">${cc.total}</td><td style="text-align: center;"><span class="badge badge-red">${cc.cancelled}</span></td><td style="text-align: center;">${parseFloat(cc.revenue).toFixed(2)} ج</td></tr>`).join('') : '<tr><td colspan="4" style="text-align:center;color:var(--text-muted)">لا بيانات</td></tr>';
            document.getElementById('report-orders').innerHTML = data.orders.length ? data.orders.map(o => `<tr><td style="color:var(--yellow); text-align: center;">${o.order_number}</td><td style="font-size:12px; text-align: center;">${formatDate(o.created_at)}</td><td style="text-align: center;">${o.client}</td><td style="text-align: center;">${o.callcenter}</td><td style="text-align: center;">${o.delivery}</td><td style="text-align: center;">${parseFloat(o.delivery_fee).toFixed(2)} ج</td><td style="text-align: center;">${parseFloat(o.discount).toFixed(2)} ج</td><td style="text-align: center;"><strong>${parseFloat(o.total).toFixed(2)} ج</strong></td><td style="text-align: center;">${statusBadge(o.status)}</td><td style="text-align: center;"><button class="btn btn-sm btn-info" onclick="viewOrder(${o.id})">عرض</button></td></tr>`).join('') : '<tr><td colspan="10" style="text-align:center;color:var(--text-muted)">لا طلبات</td></tr>';
            var t = data.totals;
            document.getElementById('report-totals').innerHTML = `<tr><td colspan="5" style="padding:12px 16px">الإجمالي (${t.count} طلب)</td><td style="padding:12px 16px">${parseFloat(t.delivery_fee).toFixed(2)} ج</td><td style="padding:12px 16px">${parseFloat(t.discount).toFixed(2)} ج</td><td style="padding:12px 16px;color:var(--yellow)">${parseFloat(t.total).toFixed(2)} ج</td><td></td></tr>`;
            if (t.pages > 1) {
                var html = '<div class="pagination">';
                for (let i = 1; i <= t.pages; i++) html += `<a class="${i === t.page ? 'active' : ''}" onclick="loadReport(${i})">${i}</a>`;
                document.getElementById('report-pagination').innerHTML = html + '</div>';
            } else { document.getElementById('report-pagination').innerHTML = ''; }
        } catch (e) { console.error(e); showError('حدث خطأ'); }
    }
    loadReport();
</script>

@include('admin.orders.partials.view_modal')