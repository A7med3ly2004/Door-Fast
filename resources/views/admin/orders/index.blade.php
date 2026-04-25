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
            <h3>تفاصيل الطلب — <span id="modal-order-num"></span></h3>
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
                        <button class="btn btn-sm btn-info" onclick="viewOrder(${o.id})">عـرض</button>
                        ${o.status !== 'cancelled' && o.status !== 'delivered' ? `<button class="btn btn-sm btn-danger" onclick="cancelOrder(${o.id})">إلغاء</button>` : ''}
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
    document.getElementById('modal-order-body').innerHTML = '<div style="display:flex;flex-direction:column;align-items:center;padding:40px;color:var(--text-muted);"><div class="spin" style="margin-bottom:16px;"></div><div>جاري تحميل التفاصيل...</div></div>';
    try {
        const { data } = await axios.get(`/admin/orders/${id}`);
        const o = data.order;
        document.getElementById('modal-order-num').textContent = o.order_number;
        const itemsTotal = o.items.reduce((sum, item) => sum + parseFloat(item.total), 0);
        
        let html = `<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:16px; margin-bottom: 20px;">`;
        html += `<div style="background:var(--bg); border-radius:12px; padding:16px; border:1px solid var(--border); box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
            <div style="font-size:14px; font-weight:700; color:var(--text-muted); margin-bottom:12px; display:flex; align-items:center; gap:8px;">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                بيانات العميل والتوصيل
            </div>
            <div style="display:flex; flex-direction:column; gap:10px;">
                <div style="display:flex; justify-content:space-between; align-items:center; padding-bottom:8px; border-bottom:1px solid var(--border);">
                    <span style="color:var(--text-muted); font-size:13px;">العميل</span>
                    <span style="font-weight:600;">${o.client?.name ?? '—'} <span style="color:var(--text-muted); font-size:12px;">(${o.client?.code ?? ''})</span></span>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center; padding-bottom:8px; border-bottom:1px solid var(--border);">
                    <span style="color:var(--text-muted); font-size:13px;">الهاتف</span>
                    <span style="font-weight:600; direction:ltr;">${o.client?.phone ?? '—'}</span>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center; padding-bottom:8px; border-bottom:1px solid var(--border);">
                    <span style="color:var(--text-muted); font-size:13px;">العنوان</span>
                    <span style="font-weight:600; text-align:left;">${o.client_address ?? '—'}</span>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <span style="color:var(--text-muted); font-size:13px;">المندوب</span>
                    <span style="font-weight:600; color:var(--yellow);">${o.delivery?.name ?? '—'}</span>
                </div>
            </div>
        </div>`;
        html += `<div style="background:var(--bg); border-radius:12px; padding:16px; border:1px solid var(--border); box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
            <div style="font-size:14px; font-weight:700; color:var(--text-muted); margin-bottom:12px; display:flex; align-items:center; justify-content:space-between;">
                <div style="display:flex; align-items:center; gap:8px;">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    الملخص المالي
                </div>
                <div>${statusBadge(o.status)}</div>
            </div>
            <div style="display:flex; flex-direction:column; gap:10px;">
                <div style="display:flex; justify-content:space-between; align-items:center; padding-bottom:8px; border-bottom:1px solid var(--border);">
                    <span style="color:var(--text-muted); font-size:13px;">كول سنتر</span>
                    <span style="font-weight:600;">${o.callcenter?.name ?? '—'}</span>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center; padding-bottom:8px; border-bottom:1px solid var(--border);">
                    <span style="color:var(--text-muted); font-size:13px;">رسوم التوصيل</span>
                    <span style="font-weight:600;">${parseFloat(o.delivery_fee).toFixed(2)} ج</span>
                </div>
                ${parseFloat(o.discount) > 0 ? `<div style="display:flex; justify-content:space-between; align-items:center; padding-bottom:8px; border-bottom:1px solid var(--border);">
                    <span style="color:var(--text-muted); font-size:13px;">الخصم</span>
                    <span style="font-weight:600; color:var(--red);">${parseFloat(o.discount).toFixed(2)} ${o.discount_type==='percent'?'%':'ج'}</span>
                </div>` : ''}
                <div style="display:flex; justify-content:space-between; align-items:center; margin-top:4px;">
                    <span style="font-size:14px; font-weight:700;">الإجمالي النهائي</span>
                    <strong style="color:var(--yellow); font-size:18px;">${parseFloat(o.total).toFixed(2)} ج</strong>
                </div>
            </div>
        </div></div>`;
        if (o.notes) {
            html += `<div style="display:flex; align-items:flex-start; gap:12px; background:var(--bg); border:1px solid var(--border); border-radius:10px; padding:12px; margin-bottom:20px;">
                <div style="color:var(--text-muted); margin-top:2px;">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                </div>
                <div>
                    <div style="font-size:12px; font-weight:700; color:var(--text-muted); margin-bottom:4px;">ملاحظات الطلب</div>
                    <div style="font-size:14px; line-height:1.5;">${o.notes}</div>
                </div>
            </div>`;
        }
        html += `<div style="background:var(--bg); border-radius:12px; border:1px solid var(--border); overflow:hidden; margin-bottom:20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
            <div style="padding:12px 16px; background:rgba(255,255,255,0.02); border-bottom:1px solid var(--border); font-size:14px; font-weight:700; display:flex; align-items:center; gap:8px;">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                الأصناف (${o.items.length})
            </div>
            <div class="table-wrap" style="margin:0; border:none; border-radius:0;">
                <table style="margin:0; width:100%; border-collapse:collapse;">
                    <thead style="background:transparent;">
                        <tr style="border-bottom:1px solid var(--border);">
                            <th style="padding:10px 16px; text-align:right;">الصنف</th>
                            <th style="padding:10px 16px; text-align:right;">المتجر</th>
                            <th style="padding:10px 16px; text-align:center;">الكمية</th>
                            <th style="padding:10px 16px; text-align:center;">السعر</th>
                            <th style="padding:10px 16px; text-align:left;">الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${o.items.map(i => `<tr style="border-bottom:1px solid var(--border);">
                            <td style="padding:12px 16px; font-weight:600;">${i.item_name}</td>
                            <td style="padding:12px 16px; color:var(--text-muted); font-size:13px;">${i.shop}</td>
                            <td style="padding:12px 16px; text-align:center;">
                                <span style="background:rgba(255,255,255,0.05); padding:2px 8px; border-radius:12px; font-size:12px; border:1px solid var(--border);">${i.quantity}</span>
                            </td>
                            <td style="padding:12px 16px; text-align:center;">${parseFloat(i.unit_price).toFixed(2)} ج</td>
                            <td style="padding:12px 16px; text-align:left; font-weight:700; color:var(--yellow);">${parseFloat(i.total).toFixed(2)} ج</td>
                        </tr>`).join('')}
                    </tbody>
                </table>
            </div>
        </div>`;
        if (o.logs && o.logs.length) {
            html += `<div style="background:var(--bg); border-radius:12px; padding:16px; border:1px solid var(--border); box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
                <div style="font-size:14px; font-weight:700; color:var(--text-muted); margin-bottom:16px; display:flex; align-items:center; gap:8px;">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    سجل النشاط
                </div>
                <div style="display:flex; flex-direction:column; gap:16px; position:relative;">
                    <div style="position:absolute; right:15px; top:10px; bottom:10px; width:2px; background:var(--border); z-index:1;"></div>
                    ${o.logs.map((l, index) => `<div style="display:flex; align-items:center; gap:16px; position:relative; z-index:2;">
                        <div style="width:32px; height:32px; border-radius:50%; background:${index === 0 ? 'var(--yellow)' : 'var(--bg)'}; border:2px solid ${index === 0 ? 'var(--yellow)' : 'var(--border)'}; display:flex; align-items:center; justify-content:center; color:${index === 0 ? '#000' : 'var(--text-muted)'};">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${index === 0 ? 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z' : 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'}"></path></svg>
                        </div>
                        <div style="flex:1;">
                            <div style="font-size:13px; font-weight:700; color:${index === 0 ? 'var(--text)' : 'var(--text-muted)'};">${l.action} <span style="font-weight:400; color:var(--text-muted); margin-right:4px;">— ${l.user}</span></div>
                            <div style="font-size:12px; color:var(--text-muted); margin-top:2px; direction:ltr; text-align:right;">${formatDate(l.created_at)}</div>
                        </div>
                    </div>`).join('')}
                </div>
            </div>`;
        }
        document.getElementById('modal-order-body').innerHTML = html;
    } catch(e) {
        document.getElementById('modal-order-body').innerHTML = `<div style="padding:40px; text-align:center;">
            <div style="display:inline-flex; align-items:center; justify-content:center; width:64px; height:64px; border-radius:50%; background:rgba(255,0,0, 0.1); color:var(--red); margin-bottom:16px;">
                <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
            <h3 style="margin-bottom:8px;">عذراً، حدث خطأ</h3>
            <p style="color:var(--text-muted); font-size:14px;">لم نتمكن من جلب بيانات الطلب. يرجى المحاولة مرة أخرى.</p>
        </div>`;
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
