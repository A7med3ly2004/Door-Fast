{{-- Admin Report-Hops SPA partial --}}
<div class="section-header">
    <h2>🏪 تقارير المتاجر</h2>
</div>
<div class="card" style="margin-bottom:20px">
    <div class="filter-bar">
        <input type="date" id="filter-from" class="form-control">
        <input type="date" id="filter-to" class="form-control">
        <button class="btn btn-primary" onclick="loadGlobal()">🔍 عرض</button>
    </div>
</div>
<div class="kpi-grid" style="margin-bottom:20px">
    <div class="kpi-card">
        <div class="kpi-label">إجمالي المتاجر</div>
        <div class="kpi-value" id="g-shops">—</div>
    </div>
    <div class="kpi-card blue">
        <div class="kpi-label">إجمالي الطلبات</div>
        <div class="kpi-value" id="g-orders">—</div>
    </div>
    <div class="kpi-card green">
        <div class="kpi-label">إجمالي المشتريات</div>
        <div class="kpi-value" id="g-purchases">—</div>
        <div class="kpi-sub">ج.م</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">أفضل متجر</div>
        <div class="kpi-value" id="g-top" style="font-size:14px">—</div>
    </div>
</div>
<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px" id="shop-tabs">
    @foreach($shops as $shop)
        <button class="btn btn-secondary shop-tab" onclick="selectShop({{ $shop->id }}, this)"
            id="tab-{{ $shop->id }}">{{ $shop->name }}</button>
    @endforeach
</div>
<div id="no-shop-msg" style="text-align:center;padding:60px;color:var(--text-muted)">
    <div style="font-size:48px;margin-bottom:16px">🏪</div>
    <div>اختر متجراً من الأعلى لعرض تقريره</div>
</div>
<div id="shop-detail-panel" style="display:none">
    <div class="section-header">
        <div>
            <h2 id="detail-shop-name">—</h2>
            <div style="color:var(--text-muted);font-size:13px" id="detail-shop-info"></div>
        </div>
        <a id="shop-pdf-btn" href="#" target="_blank" class="btn btn-danger" data-no-spa>📄 تصدير PDF</a>
    </div>
    <div class="kpi-grid" style="margin-bottom:20px">
        <div class="kpi-card">
            <div class="kpi-label">الطلبات</div>
            <div class="kpi-value" id="sk-orders">—</div>
        </div>
        <div class="kpi-card green">
            <div class="kpi-label">مُوصَّلة</div>
            <div class="kpi-value" id="sk-completed">—</div>
        </div>
        <div class="kpi-card red">
            <div class="kpi-label">ملغاة</div>
            <div class="kpi-value" id="sk-cancelled">—</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">معلقة</div>
            <div class="kpi-value" id="sk-pending">—</div>
        </div>
        <div class="kpi-card blue">
            <div class="kpi-label">الإيراد</div>
            <div class="kpi-value" id="sk-revenue">—</div>
            <div class="kpi-sub">ج.م</div>
        </div>
    </div>
    <div class="card" style="margin-bottom:20px; padding:16px;">
        <div style="display:flex; align-items:center; gap:20px; flex-wrap:wrap;">
            <div class="form-group" style="margin-bottom:0; flex:1; min-width:200px;">
                <label class="form-label">نسبة الخصم (%)</label>
                <input type="number" id="shop-discount-percent" class="form-control" value="0" min="0" max="100"
                    oninput="updateFinalAmount()">
            </div>
            <div class="kpi-card orange"
                style="flex:1; margin-bottom:0; min-width:200px; border-right: 4px solid #f97316;">
                <div class="kpi-label">قيمة الخصم</div>
                <div class="kpi-value" id="sk-discount">0.00</div>
                <div class="kpi-sub">ج.م (المبلغ المخصوم)</div>
            </div>
            <div class="kpi-card purple"
                style="flex:1; margin-bottom:0; min-width:200px; border-right: 4px solid #a855f7;">
                <div class="kpi-label">المبلغ النهائي</div>
                <div class="kpi-value" id="sk-final">—</div>
                <div class="kpi-sub">ج.م (بعد الخصم)</div>
            </div>
        </div>
    </div>
    <div class="grid-2" style="gap:20px;margin-bottom:20px">
        <div class="card">
            <div class="card-title">📈 الطلبات اليومية</div>
            <div class="chart-container" style="height:200px"><canvas id="shopChart"></canvas></div>
        </div>
        <div class="card">
            <div class="card-title">👥 أفضل العملاء</div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>العميل</th>
                            <th>الطلبات</th>
                            <th>الإنفاق</th>
                        </tr>
                    </thead>
                    <tbody id="top-clients"></tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="card" style="margin-bottom:20px">
        <div class="card-title">📦 أكثر الأصناف طلباً</div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>الصنف</th>
                        <th>الكمية</th>
                        <th>القيمة</th>
                        <th>سعر الوحدة</th>
                        <th>النسبة</th>
                    </tr>
                </thead>
                <tbody id="top-items"></tbody>
                <tfoot id="items-summary-row"></tfoot>
            </table>
        </div>
    </div>
    <div class="card" style="padding:0">
        <div style="padding:16px 20px;border-bottom:1px solid var(--border)"><strong>📋 الطلبات</strong></div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>رقم الطلب</th>
                        <th>التاريخ</th>
                        <th>العميل</th>
                        <th>المندوب</th>
                        <th>الكول سينتر</th>
                        <th>عدد الأصناف</th>
                        <th>الإجمالي</th>
                        <th>الحالة</th>
                    </tr>
                </thead>
                <tbody id="shop-orders"></tbody>
            </table>
        </div>
    </div>
</div>
<script>
    var shopChart = null;
    var currentShopId = null;
    function getFilters() { return { from: document.getElementById('filter-from').value, to: document.getElementById('filter-to').value }; }
    async function loadGlobal() {
        try {
            const { data } = await axios.get('{{ route("admin.report-hops.data") }}', { params: getFilters() });
            var g = data.global;
            document.getElementById('g-shops').textContent = g.total_shops;
            document.getElementById('g-orders').textContent = g.total_orders;
            document.getElementById('g-purchases').textContent = parseFloat(g.total_purchases).toLocaleString('ar-EG', { minimumFractionDigits: 2 });
            document.getElementById('g-top').textContent = g.top_shop;
        } catch (e) { console.error(e); }
        if (currentShopId) loadShopData(currentShopId);
    }
    var currentShopRevenue = 0;
    function updateFinalAmount() {
        var discountPercent = parseFloat(document.getElementById('shop-discount-percent').value) || 0;
        var discountAmount = currentShopRevenue * (discountPercent / 100);
        var finalAmount = currentShopRevenue - discountAmount;
        document.getElementById('sk-discount').textContent = discountAmount.toLocaleString('ar-EG', { minimumFractionDigits: 2 });
        document.getElementById('sk-final').textContent = finalAmount.toLocaleString('ar-EG', { minimumFractionDigits: 2 });
    }
    async function selectShop(id, btn) {
        document.querySelectorAll('.shop-tab').forEach(b => { b.classList.remove('btn-primary'); b.classList.add('btn-secondary'); });
        btn.classList.remove('btn-secondary'); btn.classList.add('btn-primary');
        currentShopId = id; loadShopData(id);
    }
    async function loadShopData(id) {
        document.getElementById('no-shop-msg').style.display = 'none';
        document.getElementById('shop-detail-panel').style.display = 'block';
        document.getElementById('shop-orders').innerHTML = '<tr><td colspan="6" style="text-align:center;color:var(--text-muted)">جاري التحميل...</td></tr>';
        var params = new URLSearchParams(Object.fromEntries(Object.entries(getFilters()).filter(([, v]) => v)));
        document.getElementById('shop-pdf-btn').href = `/admin/report-hops/${id}/pdf` + (params.toString() ? '?' + params.toString() : '');
        try {
            const { data } = await axios.get('{{ route("admin.report-hops.data") }}', { params: { ...getFilters(), shop_id: id } });
            var s = data.shop; const k = data.shop_kpis;
            document.getElementById('detail-shop-name').textContent = s.name;
            document.getElementById('detail-shop-info').textContent = `${s.phone ?? ''} | ${s.address ?? ''}`;
            document.getElementById('sk-orders').textContent = k.orders;
            document.getElementById('sk-completed').textContent = k.completed;
            document.getElementById('sk-cancelled').textContent = k.cancelled;
            document.getElementById('sk-pending').textContent = k.pending;
            currentShopRevenue = parseFloat(k.revenue) || 0;
            document.getElementById('sk-revenue').textContent = currentShopRevenue.toLocaleString('ar-EG', { minimumFractionDigits: 2 });
            updateFinalAmount();
            var ctx = document.getElementById('shopChart').getContext('2d');
            if (shopChart) shopChart.destroy();
            shopChart = new Chart(ctx, { type: 'bar', data: { labels: data.chart.map(d => d.label), datasets: [{ label: 'الطلبات', data: data.chart.map(d => d.count), backgroundColor: '#f59e0b', borderRadius: 4 }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { color: '#334155' }, ticks: { color: '#94a3b8' } }, y: { grid: { color: '#334155' }, ticks: { color: '#94a3b8' }, beginAtZero: true } } } });
            document.getElementById('top-clients').innerHTML = data.top_clients.length ? data.top_clients.map(c => `<tr><td>${c.name}</td><td>${c.orders}</td><td>${parseFloat(c.spend).toFixed(2)} ج</td></tr>`).join('') : '<tr><td colspan="3" style="text-align:center;color:var(--text-muted)">لا بيانات</td></tr>';
            document.getElementById('top-items').innerHTML = data.top_items.length ? data.top_items.map(i => `<tr><td>${i.name}</td><td>${i.qty}</td><td>${parseFloat(i.value).toFixed(2)} ج</td><td>${parseFloat(i.avg_price).toFixed(2)} ج</td><td><div style="display:flex;align-items:center;gap:6px"><div style="flex:1;height:6px;background:var(--border);border-radius:3px"><div style="width:${i.percentage}%;height:100%;background:var(--yellow);border-radius:3px"></div></div><span style="font-size:11px">${i.percentage}%</span></div></td></tr>`).join('') : '<tr><td colspan="5" style="text-align:center;color:var(--text-muted)">لا بيانات</td></tr>';
            var sm = data.items_summary;
            document.getElementById('items-summary-row').innerHTML = `<tr style="background:var(--bg);font-weight:700"><td>الإجمالي</td><td>${sm.total_qty}</td><td>${parseFloat(sm.total_value).toFixed(2)} ج</td><td>${parseFloat(sm.avg_price).toFixed(2)} ج</td><td></td></tr>`;
            document.getElementById('shop-orders').innerHTML = data.orders.length ? data.orders.map(o => `<tr><td style="color:var(--yellow)">${o.order_number}</td><td style="font-size:12px">${formatDate(o.created_at)}</td><td>${o.client}</td><td>${o.delivery}</td><td>${o.callcenter}</td><td style="text-align:center">${o.items_count}</td><td>${parseFloat(o.total).toFixed(2)} ج</td><td>${statusBadge(o.status)}</td></tr>`).join('') : '<tr><td colspan="8" style="text-align:center;color:var(--text-muted)">لا طلبات</td></tr>';
        } catch (e) { console.error(e); showError('حدث خطأ'); }
    }
    loadGlobal();
</script>