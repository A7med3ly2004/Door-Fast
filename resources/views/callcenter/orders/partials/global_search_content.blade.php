<div class="section-header"><h2>🌍 بحث الطلبات الشامل (جميع الكول سنتر)</h2></div>
<div class="card" style="margin-bottom:16px">
    <div class="filter-bar">
        <input type="text" id="f-g-search" class="form-control" placeholder="كود الطلب، كود العميل، رقم العميل" style="min-width:300px" value="{{ request('q') }}">
        <button class="btn btn-primary" onclick="loadGlobalList()">🔍 بحث إضافي</button>
    </div>
</div>
<div class="card" style="padding:0;position:relative">
    <div class="loading-overlay" id="tbl-loading-g"><div class="spin"></div></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>رقم الطلب</th><th>التاريخ</th><th>الكول سنتر</th><th>العميل</th><th>الهاتف</th><th>المندوب</th><th>الإجمالي</th><th>الحالة</th><th>إجراءات</th></tr></thead>
            <tbody id="global-orders-body"><tr><td colspan="9" style="text-align:center;padding:40px;color:var(--text-muted)">أدخل كود للبحث...</td></tr></tbody>
        </table>
    </div>
</div>

<div class="modal-overlay" id="modal-view"><div class="modal modal-lg"><div class="modal-header"><h3>📦 تفاصيل الطلب — <span id="view-num"></span></h3><button class="btn-close" onclick="closeModal('modal-view')">✕</button></div><div class="modal-body" id="view-body"></div></div></div>

<script>
async function loadGlobalList() {
    var search = document.getElementById('f-g-search').value.trim();
    if (!search) return;
    
    document.getElementById('tbl-loading-g').classList.add('show');
    try {
        const { data } = await axios.get('{{ route("callcenter.orders.global-search") }}', { params: { search } });
        var body = document.getElementById('global-orders-body');
        if (!data.length) { body.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:40px;color:var(--text-muted)">لا توجد نتائج</td></tr>'; return; }
        
        body.innerHTML = data.map(o => {
            return `<tr><td><strong style="color:var(--yellow)">${o.order_number}</strong></td><td style="font-size:11px;color:var(--text-muted)">${formatDate(o.created_at)}</td><td>${o.callcenter_name}</td><td>${o.client_name}</td><td>${o.client_phone}</td><td>${o.delivery_name}</td><td>${parseFloat(o.total).toFixed(2)} ج</td><td>${statusBadge(o.status)}</td><td><button class="btn btn-sm btn-info" onclick="viewOrder(${o.id})">👁 عرض</button></td></tr>`;
        }).join('');
    } catch(e) { console.error(e); } finally { document.getElementById('tbl-loading-g').classList.remove('show'); }
}

async function viewOrder(id) {
    openModal('modal-view'); document.getElementById('view-body').innerHTML = '<div style="text-align:center;padding:40px;color:var(--text-muted)"><div class="spin"></div></div>';
    try {
        const { data } = await axios.get(`/callcenter/orders/global-search/${id}`); const o = data.order;
        document.getElementById('view-num').textContent = o.order_number;
        document.getElementById('view-body').innerHTML = `<div class="grid-2" style="margin-bottom:16px"><div><div class="info-row"><span class="info-label">العميل</span><span>${o.client?.name ?? '—'} (${o.client?.code ?? ''})</span></div><div class="info-row"><span class="info-label">الهاتف</span><span>${o.client?.phone ?? '—'}</span></div><div class="info-row"><span class="info-label">العنوان</span><span>${o.client_address}</span></div><div class="info-row"><span class="info-label">المندوب</span><span>${o.delivery?.name ?? '—'}</span></div></div><div><div class="info-row"><span class="info-label">الحالة</span><span>${statusBadge(o.status)}</span></div><div class="info-row"><span class="info-label">رسوم توصيل</span><span>${parseFloat(o.delivery_fee).toFixed(2)} ج</span></div><div class="info-row"><span class="info-label">الخصم</span><span>${parseFloat(o.discount).toFixed(2)} ${o.discount_type==='percent'?'%':'ج'}</span></div><div class="info-row"><span class="info-label">الإجمالي</span><strong style="color:var(--yellow)">${parseFloat(o.total).toFixed(2)} ج</strong></div></div></div>${o.send_to_phone ? `<div style="padding:8px 12px;background:var(--bg);border-radius:8px;font-size:13px;margin-bottom:12px">↗ إرسال إلى: ${o.send_to_phone} — ${o.send_to_address}</div>` : ''}${o.notes ? `<div style="padding:8px 12px;background:var(--bg);border-radius:8px;font-size:13px;margin-bottom:12px;color:var(--text-muted)">📝 ${o.notes}</div>` : ''}<div style="font-weight:700;margin-bottom:8px">الأصناف</div><div class="table-wrap" style="margin-bottom:14px"><table><thead><tr><th>الصنف</th><th>المتجر</th><th>الكمية</th><th>السعر</th><th>الإجمالي</th></tr></thead><tbody>${o.items.map(i=>`<tr><td>${i.item_name}</td><td>${i.shop}</td><td>${i.quantity}</td><td>${parseFloat(i.unit_price).toFixed(2)}</td><td>${parseFloat(i.total).toFixed(2)} ج</td></tr>`).join('')}</tbody></table></div><div style="font-weight:700;margin-bottom:8px">التسلسل الزمني</div><div>${[['تاريخ الإنشاء', o.created_at],['إرسال للدلفري', o.sent_to_delivery_at],['قبول المندوب', o.accepted_at],['تم التوصيل', o.delivered_at]].map(([label,val])=>val ? `<div class="info-row"><span class="info-label">${label}</span><span>${formatDate(val)}</span></div>` : '').join('')}</div>`;
    } catch(e) { document.getElementById('view-body').innerHTML = '<div style="color:var(--red);text-align:center">حدث خطأ</div>'; }
}

if (document.getElementById('f-g-search').value.trim() !== '') {
    loadGlobalList();
}
</script>
