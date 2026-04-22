@extends('layouts.admin')

@section('page-title', 'الطلبات')

@section('content')
<div class="section-header">
    <h2> إدارة الطلبات</h2>
    <a href="{{ route('admin.orders.export-pdf') }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}"
       id="export-pdf-btn" class="btn btn-danger" target="_blank">
        تصدير PDF
    </a>
</div>

{{-- Filters --}}
<div class="card" style="margin-bottom:20px">
    <div class="filter-bar">
        <input type="text" id="filter-search" class="form-control" placeholder="بحث بالطلب / العميل / الهاتف" style="min-width:260px">
        <select id="filter-status" class="form-select">
            <option value="">كل الحالات</option>
            <option value="pending">باقي</option>
            <option value="received">مُسلَّم للمندوب</option>
            <option value="delivered">مُوصَّل</option>
            <option value="cancelled">ملغي</option>
        </select>
        <select id="filter-callcenter" class="form-select">
            <option value="">كل الكول سنتر</option>
            @foreach($callcenters as $cc)
                <option value="{{ $cc->id }}">{{ $cc->name }}</option>
            @endforeach
        </select>
        <select id="filter-delivery" class="form-select">
            <option value="">كل المناديب</option>
            @foreach($deliveries as $d)
                <option value="{{ $d->id }}">{{ $d->name }}</option>
            @endforeach
        </select>
        <input type="date" id="filter-from" class="form-control" placeholder="من">
        <input type="date" id="filter-to" class="form-control" placeholder="إلى">
        <button class="btn btn-primary" onclick="loadOrders(1)">بحث</button>
        <button class="btn btn-secondary" onclick="resetFilters()">إعادة</button>
    </div>
</div>

{{-- Table --}}
<div class="card" style="padding:0;position:relative">
    <div class="loading-overlay" id="table-loading"><div class="spin" style="width:30px;height:30px;border-width:3px"></div></div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="text-align: center;">رقم الطلب</th>
                    <th style="text-align: center;">التاريخ</th>
                    <th style="text-align: center;">العميل</th>
                    <th style="text-align: center;">كول سنتر</th>
                    <th style="text-align: center;">المندوب</th>
                    <th style="text-align: center;">عدد الأصناف</th>
                    <th style="text-align: center;">توصيل</th>
                    <th style="text-align: center;">خصم</th>
                    <th style="text-align: center;">الإجمالي</th>
                    <th style="text-align: center;">الحالة</th>
                    <th style="text-align: center;">إجراءات</th>
                </tr>
            </thead>
            <tbody id="orders-body">
                <tr><td colspan="11" style="text-align:center;color:var(--text-muted);padding:40px">جاري التحميل...</td></tr>
            </tbody>
        </table>
    </div>
    <div id="pagination-wrap" style="padding:16px; background: var(--bg);"></div>
</div>

{{-- View Order Modal --}}
<div class="modal-overlay" id="modal-view-order">
    <div class="modal modal-lg">
        <div class="modal-header">
            <h3>📦 تفاصيل الطلب — <span id="modal-order-num"></span></h3>
            <button class="btn-close" onclick="closeModal('modal-view-order')">✕</button>
        </div>
        <div class="modal-body" id="modal-order-body">
            <div style="text-align:center;padding:40px;color:var(--text-muted)">جاري التحميل...</div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
var currentPage = 1;
var currentFilters = {};

function getFilters() {
    return {
        search:       document.getElementById('filter-search').value,
        status:       document.getElementById('filter-status').value,
        callcenter_id:document.getElementById('filter-callcenter').value,
        delivery_id:  document.getElementById('filter-delivery').value,
        from:         document.getElementById('filter-from').value,
        to:           document.getElementById('filter-to').value,
    };
}

function resetFilters() {
    document.getElementById('filter-search').value = '';
    document.getElementById('filter-status').value = '';
    document.getElementById('filter-callcenter').value = '';
    document.getElementById('filter-delivery').value = '';
    document.getElementById('filter-from').value = '';
    document.getElementById('filter-to').value = '';
    loadOrders(1);
}

async function loadOrders(page = 1) {
    currentPage = page;
    currentFilters = getFilters();
    document.getElementById('table-loading').classList.add('show');

    // Update export PDF link
    const params = new URLSearchParams({...currentFilters, page});
    Object.keys(currentFilters).forEach(k => { if (!currentFilters[k]) params.delete(k); });
    const exportBtn = document.getElementById('export-pdf-btn');
    const baseUrl = '{{ route("admin.orders.export-pdf") }}';
    exportBtn.href = baseUrl + (params.toString() ? '?' + params.toString() : '');

    try {
        const { data } = await axios.get('{{ route("admin.orders.index") }}', {
            params: { ...currentFilters, page },
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        });

        const body = document.getElementById('orders-body');
        if (!data.data.length) {
            body.innerHTML = '<tr><td colspan="11" style="text-align:center;color:var(--text-muted);padding:40px">لا توجد طلبات</td></tr>';
            document.getElementById('pagination-wrap').innerHTML = '';
            return;
        }

        body.innerHTML = data.data.map(o => {
            const itemsSummary = o.items ? o.items.map(i => `${i.item_name}×${i.quantity}`).join('، ').substring(0, 60) + '...' : '—';
            return `<tr>
                <td><strong style="color:var(--yellow) text-align: center;">${o.order_number}</strong></td>
                <td style="font-size:12px;color:var(--text-muted) text-align: center;">${formatDate(o.created_at)}</td>
                <td style="text-align: center;">${o.client?.name ?? '—'}</td>
                <td style="text-align: center;">${o.callcenter?.name ?? '—'}</td>
                <td style="text-align: center;">
                    ${o.delivery?.name ?? '—'}
                    ${o.is_delivery_chosen ? '<div class="kpi-sub" style="font-size:11px; margin-top:4px; color:var(--text-muted);">تم اختيار المندوب</div>' : ''}
                </td>
                <td style="text-align: center;">${o.items_count}</td>
                <td style="text-align: center;">${parseFloat(o.delivery_fee||0).toFixed(2)} ج</td>
                <td style="text-align: center;">${parseFloat(o.discount||0).toFixed(2)} ج</td>
                <td style="text-align: center;"><strong>${parseFloat(o.total||0).toFixed(2)} ج</strong></td>
                <td style="text-align: center;">${statusBadge(o.status)}</td>
                <td style="text-align: center;">
                    <div style="display:flex;gap:6px;justify-content: center;">
                        <button class="btn btn-sm btn-info" onclick="viewOrder(${o.id})">👁 عرض</button>
                        ${o.status !== 'cancelled' && o.status !== 'delivered' ? `<button class="btn btn-sm btn-danger" onclick="cancelOrder(${o.id})">✕ إلغاء</button>` : ''}
                    </div>
                </td>
            </tr>`;
        }).join('');

        renderPagination(data.last_page, data.current_page);
    } catch(e) {
        console.error(e);
        document.getElementById('orders-body').innerHTML = '<tr><td colspan="11" style="text-align:center;color:var(--red)">حدث خطأ</td></tr>';
    } finally {
        document.getElementById('table-loading').classList.remove('show');
    }
}

function renderPagination(lastPage, current) {
    if (lastPage <= 1) { document.getElementById('pagination-wrap').innerHTML = ''; return; }
    let html = '<div class="pagination">';
    html += `<a class="${current === 1 ? 'disabled' : ''}" onclick="loadOrders(${current-1})">‹</a>`;
    for (let i = 1; i <= lastPage; i++) {
        if (i === 1 || i === lastPage || Math.abs(i - current) <= 2) {
            html += `<a class="${i === current ? 'active' : ''}" onclick="loadOrders(${i})">${i}</a>`;
        } else if (Math.abs(i - current) === 3) {
            html += '<span>…</span>';
        }
    }
    html += `<a class="${current === lastPage ? 'disabled' : ''}" onclick="loadOrders(${current+1})">›</a>`;
    html += '</div>';
    document.getElementById('pagination-wrap').innerHTML = html;
}

async function viewOrder(id) {
    openModal('modal-view-order');
    document.getElementById('modal-order-body').innerHTML = '<div style="text-align:center;padding:40px;color:var(--text-muted)">جاري التحميل...</div>';
    try {
        const { data } = await axios.get(`/admin/orders/${id}`);
        const o = data.order;
        document.getElementById('modal-order-num').textContent = o.order_number;
        document.getElementById('modal-order-body').innerHTML = `
            <div class="form-row" style="margin-bottom:16px">
                <div>
                    <div class="info-row"><span class="info-label">العميل</span><span>${o.client?.name ?? '—'}</span></div>
                    <div class="info-row"><span class="info-label">الهاتف</span><span>${o.client?.phone ?? '—'}</span></div>
                    <div class="info-row"><span class="info-label">العنوان</span><span>${o.client_address ?? '—'}</span></div>
                    <div class="info-row"><span class="info-label">الكول سنتر</span><span>${o.callcenter?.name ?? '—'}</span></div>
                    <div class="info-row"><span class="info-label">المندوب</span><span>${o.delivery?.name ?? '—'}</span></div>
                </div>
                <div>
                    <div class="info-row"><span class="info-label">الحالة</span><span>${statusBadge(o.status)}</span></div>
                    <div class="info-row"><span class="info-label">رسوم التوصيل</span><span>${parseFloat(o.delivery_fee).toFixed(2)} ج</span></div>
                    <div class="info-row"><span class="info-label">الخصم</span><span>${parseFloat(o.discount).toFixed(2)} ${o.discount_type === 'percent' ? '%' : 'ج'}</span></div>
                    <div class="info-row"><span class="info-label">الإجمالي</span><strong>${parseFloat(o.total).toFixed(2)} ج</strong></div>
                    <div class="info-row"><span class="info-label">التاريخ</span><span style="font-size:12px">${formatDate(o.created_at)}</span></div>
                </div>
            </div>
            ${o.notes ? `<div style="background:var(--bg);padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:16px;color:var(--text-muted)">📝 ${o.notes}</div>` : ''}
            <div class="card-title" style="margin-bottom:10px">الأصناف</div>
            <div class="table-wrap" style="margin-bottom:16px">
                <table>
                    <thead><tr><th>الصنف</th><th>المتجر</th><th>الكمية</th><th>سعر الوحدة</th><th>الإجمالي</th></tr></thead>
                    <tbody>${o.items.map(i => `<tr>
                        <td>${i.item_name}</td><td>${i.shop}</td>
                        <td>${i.quantity}</td>
                        <td>${parseFloat(i.unit_price).toFixed(2)} ج</td>
                        <td>${parseFloat(i.total).toFixed(2)} ج</td>
                    </tr>`).join('')}</tbody>
                </table>
            </div>
            ${o.logs.length ? `<div class="card-title" style="margin-bottom:10px">سجل التاريخ</div>
            <div>${o.logs.map(l => `<div style="padding:6px 0;border-bottom:1px solid var(--border);font-size:12px">
                <span style="color:var(--yellow)">${l.action}</span> — ${l.user}
                <span style="float:left;color:var(--text-muted)">${formatDate(l.created_at)}</span>
            </div>`).join('')}</div>` : ''}
        `;
    } catch(e) {
        document.getElementById('modal-order-body').innerHTML = '<div style="text-align:center;color:var(--red)">حدث خطأ</div>';
    }
}

async function cancelOrder(id) {
    const ok = await confirmAction('إلغاء الطلب', 'هل تريد إلغاء هذا الطلب؟ لا يمكن التراجع.', 'نعم إلغاء');
    if (!ok) return;
    try {
        const { data } = await axios.patch(`/admin/orders/${id}/cancel`);
        showSuccess(data.message);
        loadOrders(currentPage);
    } catch(e) {
        showError(e.response?.data?.message ?? 'حدث خطأ');
    }
}

loadOrders(1);
</script>
@endpush
