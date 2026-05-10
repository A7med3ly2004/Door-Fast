{{-- Admin Reports SPA partial --}}
<div class="section-header">
    <h2>التقارير</h2>
    <div style="display:flex;gap:10px;align-items:center;">
        <a id="export-pdf-btn" href="{{ route('admin.reports.export-pdf') }}" target="_blank" class="btn btn-danger"
            data-no-spa>تصدير PDF</a>
        <button class="btn btn-success btn-sm" id="export-excel-btn" onclick="exportReportExcel()"
            style="background:#217346;color:#fff;" data-no-spa>تصدير Excel</button>
    </div>
</div>
<div class="card" style="margin-bottom:20px">
    <div class="filter-bar" style="align-items: flex-end;">
        <div>
            <label class="form-label" style="font-size: 12px; margin-bottom: 2px;">تاريخ من</label>
            <input type="date" id="filter-from" class="form-control">
        </div>
        <div>
            <label class="form-label" style="font-size: 12px; margin-bottom: 2px;">تاريخ إلى</label>
            <input type="date" id="filter-to" class="form-control">
        </div>
        <div>
            <label class="form-label" style="font-size: 12px; margin-bottom: 2px;">المندوب</label>
            <div class="relative group" style="min-width:160px; z-index: 50;">
                <div class="form-control" style="cursor:pointer; display:flex; justify-content:space-between; align-items:center;">
                    <span id="label-filter-delivery">كل المناديب</span>
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </div>
                <input type="hidden" id="filter-delivery" value="">
                <div class="absolute top-full right-0 w-full opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all bg-white/80 backdrop-blur shadow-lg rounded-md mt-1 overflow-hidden" style="border:1px solid var(--border); background-color: rgba(255, 255, 255, 0.9); max-height:200px; overflow-y:auto;">
                    <div class="px-3 py-2 cursor-pointer hover:bg-green-50 hover:text-green-700 text-sm transition-colors text-gray-800" onclick="selectDropdown('filter-delivery', '', 'كل المناديب')">كل المناديب</div>
                    @foreach($deliveries as $d)
                        <div class="px-3 py-2 cursor-pointer hover:bg-green-50 hover:text-green-700 text-sm transition-colors text-gray-800" onclick="selectDropdown('filter-delivery', '{{ $d->id }}', '{{ $d->name }}')">{{ $d->name }}</div>
                    @endforeach
                </div>
            </div>
        </div>
        <div>
            <label class="form-label" style="font-size: 12px; margin-bottom: 2px;">كول سنتر</label>
            <div class="relative group" style="min-width:160px; z-index: 40;">
                <div class="form-control" style="cursor:pointer; display:flex; justify-content:space-between; align-items:center;">
                    <span id="label-filter-callcenter">كل الكول سنتر</span>
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </div>
                <input type="hidden" id="filter-callcenter" value="">
                <div class="absolute top-full right-0 w-full opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all bg-white/80 backdrop-blur shadow-lg rounded-md mt-1 overflow-hidden" style="border:1px solid var(--border); background-color: rgba(255, 255, 255, 0.9); max-height:200px; overflow-y:auto;">
                    <div class="px-3 py-2 cursor-pointer hover:bg-green-50 hover:text-green-700 text-sm transition-colors text-gray-800" onclick="selectDropdown('filter-callcenter', '', 'كل الكول سنتر')">كل الكول سنتر</div>
                    @foreach($callcenters as $cc)
                        <div class="px-3 py-2 cursor-pointer hover:bg-green-50 hover:text-green-700 text-sm transition-colors text-gray-800" onclick="selectDropdown('filter-callcenter', '{{ $cc->id }}', '{{ $cc->name }}')">{{ $cc->name }}</div>
                    @endforeach
                </div>
            </div>
        </div>
        <div>
            <label class="form-label" style="font-size: 12px; margin-bottom: 2px;">مدير النظام</label>
            <div class="relative group" style="min-width:160px; z-index: 30;">
                <div class="form-control" style="cursor:pointer; display:flex; justify-content:space-between; align-items:center;">
                    <span id="label-filter-admin">كل المديرين</span>
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </div>
                <input type="hidden" id="filter-admin" value="">
                <div class="absolute top-full right-0 w-full opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all bg-white/80 backdrop-blur shadow-lg rounded-md mt-1 overflow-hidden" style="border:1px solid var(--border); background-color: rgba(255, 255, 255, 0.9); max-height:200px; overflow-y:auto;">
                    <div class="px-3 py-2 cursor-pointer hover:bg-green-50 hover:text-green-700 text-sm transition-colors text-gray-800" onclick="selectDropdown('filter-admin', '', 'كل المديرين')">كل المديرين</div>
                    @foreach($admins as $adm)
                        <div class="px-3 py-2 cursor-pointer hover:bg-green-50 hover:text-green-700 text-sm transition-colors text-gray-800" onclick="selectDropdown('filter-admin', '{{ $adm->id }}', '{{ $adm->name }}')">{{ $adm->name }}</div>
                    @endforeach
                </div>
            </div>
        </div>
        <div style="display: flex; gap: 5px; margin-bottom: 2px;">
            <button class="btn btn-primary" onclick="loadReport()">عرض</button>
            <button class="btn btn-secondary" onclick="resetReport()">إعادة</button>
        </div>
    </div>
</div>
<div class="kpi-grid" style="margin-bottom:20px; grid-template-columns: repeat(3, 1fr);">
    <div class="kpi-card cyan">
        <div class="kpi-label">إجمالي الطلبات</div>
        <div class="kpi-value" id="r-total">—</div>
    </div>
    <div class="kpi-card green">
        <div class="kpi-label">تم التوصيلة</div>
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
    <div class="kpi-card blue">
        <div class="kpi-label">إجمالي التوصيل</div>
        <div class="kpi-value" id="r-delivery-fees">—</div>
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
                        <th style="text-align: center;">تم التوصيلة</th>
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
                    <th style="text-align: center;">تم انشاؤه</th>
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
        return { from: document.getElementById('filter-from').value, to: document.getElementById('filter-to').value, delivery_id: document.getElementById('filter-delivery').value, callcenter_id: document.getElementById('filter-callcenter').value, admin_id: document.getElementById('filter-admin').value };
    }
    function resetReport() {
        ['filter-from', 'filter-to', 'filter-delivery', 'filter-callcenter', 'filter-admin'].forEach(id => { 
            const el = document.getElementById(id); 
            if (el) el.value = ''; 
            const lbl = document.getElementById('label-' + id);
            if (lbl) {
                if (id === 'filter-delivery') lbl.innerText = 'كل المناديب';
                else if (id === 'filter-admin') lbl.innerText = 'كل المديرين';
                else lbl.innerText = 'كل الكول سنتر';
            }
        });
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
            document.getElementById('r-revenue').textContent = parseFloat(data.kpis.revenue).toLocaleString('en-US', { minimumFractionDigits: 2 });
            document.getElementById('r-delivery-fees').textContent = parseFloat(data.kpis.delivery_fees).toLocaleString('en-US', { minimumFractionDigits: 2 });
            var ctx = document.getElementById('reportChart').getContext('2d');
            if (reportChart) reportChart.destroy();
            reportChart = new Chart(ctx, {
                type: 'bar',
                data: { labels: data.chart.map(d => d.label), datasets: [{ label: 'الطلبات', data: data.chart.map(d => d.count), backgroundColor: '#f59e0b', borderRadius: 4 }, { label: 'الإيراد', data: data.chart.map(d => d.revenue), backgroundColor: '#3b82f6', borderRadius: 4, yAxisID: 'y2' }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { color: '#94a3b8' } } }, scales: { x: { grid: { color: '#334155' }, ticks: { color: '#94a3b8' } }, y: { grid: { color: '#334155' }, ticks: { color: '#94a3b8' }, beginAtZero: true }, y2: { position: 'left', grid: { display: false }, ticks: { color: '#3b82f6' }, beginAtZero: true } } }
            });
            document.getElementById('delivery-breakdown').innerHTML = data.delivery_breakdown.length ? data.delivery_breakdown.map(d => `<tr><td style="text-align: right;">${d.name}</td><td style="text-align: center;">${d.total}</td><td style="text-align: center;"><span class="badge badge-green">${d.completed}</span></td><td style="text-align: center;">${parseFloat(d.revenue).toFixed(2)} ج</td></tr>`).join('') : '<tr><td colspan="5" style="text-align:center;color:var(--text-muted)">لا بيانات</td></tr>';
            document.getElementById('cc-breakdown').innerHTML = data.cc_breakdown.length ? data.cc_breakdown.map(cc => `<tr><td style="text-align: right;">${cc.name}</td><td style="text-align: center;">${cc.total}</td><td style="text-align: center;"><span class="badge badge-red">${cc.cancelled}</span></td><td style="text-align: center;">${parseFloat(cc.revenue).toFixed(2)} ج</td></tr>`).join('') : '<tr><td colspan="4" style="text-align:center;color:var(--text-muted)">لا بيانات</td></tr>';
            document.getElementById('report-orders').innerHTML = data.orders.length ? data.orders.map(o => `<tr><td style="color:var(--yellow); text-align: center;">${o.order_number}</td><td style="font-size:12px; text-align: center;">${formatDate(o.created_at)}</td><td style="text-align: center;">${o.client}</td><td style="text-align: center;">${o.creator_name}${o.creator_type==='admin' ? ' <span class="badge badge-blue" style="font-size:9px; padding:1px 4px;">أدمن</span>' : ''}</td><td style="text-align: center;">${o.delivery}</td><td style="text-align: center;">${parseFloat(o.delivery_fee).toFixed(2)} ج</td><td style="text-align: center;">${parseFloat(o.discount).toFixed(2)} ج</td><td style="text-align: center;"><strong>${parseFloat(o.total).toFixed(2)} ج</strong></td><td style="text-align: center;">${statusBadge(o.status)}</td><td style="text-align: center;"><button class="btn btn-sm btn-info" onclick="viewOrder(${o.id})">عرض</button></td></tr>`).join('') : '<tr><td colspan="10" style="text-align:center;color:var(--text-muted)">لا طلبات</td></tr>';
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

    window.exportReportExcel = async function () {
        try {
            const filters = {
                from: document.getElementById('filter-from').value,
                to: document.getElementById('filter-to').value,
                delivery_id: document.getElementById('filter-delivery').value,
                callcenter_id: document.getElementById('filter-callcenter').value,
                page: 1,
                per_page: 9999,
            };
            const { data } = await axios.get('{{ route("admin.reports.data") }}', { params: filters });

            const columns = [
                { header: 'رقم الطلب', key: 'order_number', width: 18 },
                { header: 'التاريخ', key: 'created_at', width: 20 },
                { header: 'العميل', key: 'client', width: 22 },
                { header: 'تم انشاؤه', key: 'creator_name', width: 18 },
                { header: 'المندوب', key: 'delivery', width: 18 },
                { header: 'رسوم التوصيل', key: 'delivery_fee', width: 16 },
                { header: 'الخصم', key: 'discount', width: 12 },
                { header: 'الإجمالي', key: 'total', width: 14 },
                { header: 'الحالة', key: 'status', width: 14 },
            ];

            const statusMap = { pending: 'قيد الانتظار', received: 'مسلم للمندوب', delivered: 'تم التوصيل', cancelled: 'ملغي' };
            const rows = data.orders.map(o => ({
                ...o,
                created_at: o.created_at ? new Date(o.created_at).toLocaleDateString('ar-EG') : '—',
                status: statusMap[o.status] || o.status,
            }));

            exportToExcel(rows, columns, 'report-' + new Date().toISOString().slice(0, 10), 'التقارير');
            if (typeof showSuccess === 'function') showSuccess('تم التصدير');
        } catch (e) {
            if (typeof showError === 'function') showError('حدث خطأ');
            console.error(e);
        }
    };
</script>

@include('admin.orders.partials.view_modal')