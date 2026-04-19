{{-- Callcenter Orders Index SPA partial --}}
<div class="section-header"><h2>📋 قائمة الطلبات</h2><a href="{{ route('callcenter.orders.create') }}" class="btn btn-primary">➕ طلب جديد</a></div>
<div class="card" style="margin-bottom:16px"><div class="filter-bar"><input type="text" id="f-search" class="form-control" placeholder="رقم الطلب / العميل / الهاتف" style="min-width:200px"><select id="f-status" class="form-select"><option value="">كل الحالات</option><option value="pending">باقي</option><option value="received">مُسلَّم</option><option value="delivered">مُوصَّل</option><option value="cancelled">ملغي</option></select><button class="btn btn-primary" onclick="loadList(1)">🔍 بحث</button><button class="btn btn-secondary" onclick="resetFilters()">↺ إعادة</button></div></div>
<div class="card" style="padding:0;position:relative">
    <div class="loading-overlay" id="tbl-loading"><div class="spin"></div></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>رقم الطلب</th><th>التاريخ</th><th>العميل</th><th>الهاتف</th><th>المندوب</th><th>المتاجر</th><th>قيمة التوصيل</th><th>الإجمالي</th><th>الحالة</th><th>إجراءات</th></tr></thead>
            <tbody id="orders-body"><tr><td colspan="10" style="text-align:center;padding:40px;color:var(--text-muted)">جاري التحميل...</td></tr></tbody>
        </table>
    </div>
    <div id="pg-wrap" style="padding:14px"></div>
</div>

<div class="modal-overlay" id="modal-view"><div class="modal modal-lg"><div class="modal-header"><h3>📦 تفاصيل الطلب — <span id="view-num"></span></h3><button class="btn-close" onclick="closeModal('modal-view')">✕</button></div><div class="modal-body" id="view-body"></div></div></div>
<div class="modal-overlay" id="modal-cancel"><div class="modal"><div class="modal-header"><h3>إلغاء الطلب</h3><button class="btn-close" onclick="closeModal('modal-cancel')">✕</button></div><div class="modal-body"><input type="hidden" id="cancel-id"><div class="form-group"><label class="form-label">سبب الإلغاء (اختياري)</label><textarea class="form-control" id="cancel-reason" rows="3" placeholder="اكتب سبب الإلغاء..."></textarea></div><div class="modal-footer"><button class="btn btn-secondary" onclick="closeModal('modal-cancel')">تراجع</button><button class="btn btn-danger" onclick="doCancel()">إلغاء الطلب</button></div></div></div></div>
<div class="modal-overlay" id="modal-edit"><div class="modal modal-lg" style="max-width:800px"><div class="modal-header"><h3>✏️ تعديل الطلب — <span id="edit-num"></span></h3><button class="btn-close" onclick="closeModal('modal-edit')">✕</button></div><div class="modal-body" id="edit-body"><input type="hidden" id="edit-id"><div class="grid-2" style="margin-bottom:12px"><div class="form-group"><label class="form-label">المندوب (تلقائي إن تُرك فارغاً)</label><select class="form-select" id="edit-delivery"></select></div><div class="form-group"><label class="form-label">عنوان العميل *</label><input type="text" class="form-control" id="edit-address"></div></div><div style="background:rgba(255,255,255,0.02);border-radius:8px;padding:12px;margin-bottom:12px;border:1px dashed var(--border)"><div style="font-size:12px;font-weight:700;margin-bottom:8px;color:var(--text-muted)">↗ إرسال إلى عميل آخر (اختياري)</div><div class="grid-2"><div class="form-group"><label class="form-label">هاتف المستلم</label><input type="text" class="form-control" id="edit-send-to-phone" placeholder="01xxxxxxxxx"></div><div class="form-group"><label class="form-label">عنوان المستلم</label><input type="text" class="form-control" id="edit-send-to-address" placeholder="العنوان"></div></div></div><div class="form-group"><label class="form-label">ملاحظات</label><textarea class="form-control" id="edit-notes" rows="2" placeholder="ملاحظات اختيارية..."></textarea></div><div style="font-weight:700;margin-top:16px;margin-bottom:8px">📦 الأصناف</div><div class="table-wrap" style="margin-bottom:12px;overflow:visible"><table class="items-table" style="width:100%;border-collapse:collapse"><thead><tr style="border-bottom:1px solid var(--border)"><th style="padding:4px;text-align:right">الصنف</th><th style="padding:4px;text-align:right;width:130px">المتجر</th><th style="padding:4px;text-align:right;width:65px">الكمية</th><th style="padding:4px;text-align:right;width:80px">السعر</th><th style="padding:4px;text-align:right;width:80px">الإجمالي</th><th style="padding:4px;width:30px"></th></tr></thead><tbody id="edit-items"></tbody></table></div><button class="btn btn-secondary btn-sm" onclick="addEditRow()">＋ إضافة صنف</button><div class="grid-2" style="margin-top:16px"><div class="form-group"><label class="form-label">رسوم التوصيل</label><input type="number" class="form-control" id="edit-fee" min="0" step="0.5" oninput="calcEditTotals()"></div><div class="form-group"><label class="form-label">الخصم</label><div style="display:flex;gap:5px"><input type="number" class="form-control" id="edit-disc" min="0" step="0.5" oninput="calcEditTotals()"><select class="form-select" id="edit-disc-type" style="width:70px" onchange="calcEditTotals()"><option value="amount">ج</option><option value="percent">%</option></select></div></div></div><div style="background:var(--bg);padding:12px;margin-top:16px;border-radius:8px"><div style="display:flex;justify-content:space-between;margin-bottom:4px"><span style="font-size:13px">إجمالي الأصناف:</span> <strong id="edit-items-total" style="font-size:14px">0 ج</strong></div><div style="display:flex;justify-content:space-between;font-size:18px;color:var(--yellow);font-weight:800;border-top:1px solid var(--border);padding-top:6px"><span>الإجمالي النهائي:</span> <span id="edit-grand-total">0 ج</span></div></div></div><div class="modal-footer"><button class="btn btn-secondary" onclick="closeModal('modal-edit')">تراجع</button><button class="btn btn-primary" id="btn-save-edit" onclick="saveEdit()">حفظ التعديلات ✔</button></div></div></div>

<script>
var SHOPS = @json($shops);
var activeDeliveries = [];
var currentPage = 1;

async function loadActiveDeliveries() { try { const { data } = await axios.get('{{ route("callcenter.delivery.active") }}'); activeDeliveries = data; } catch(e) {} }
function buildDeliveryOptions() { return activeDeliveries.map(d => `<option value="${d.id}">${d.name} (${d.orders_today}/${d.max_orders})</option>`).join(''); }
function getFilters() { return { search: document.getElementById('f-search').value, status: document.getElementById('f-status').value }; }
function resetFilters() { document.getElementById('f-search').value=''; document.getElementById('f-status').value=''; loadList(1); }

async function loadList(page = 1) {
    currentPage = page; document.getElementById('tbl-loading').classList.add('show');
    try {
        const filters = getFilters();
        const globalSearchNav = document.getElementById('nav-global-search');
        if (globalSearchNav) {
            if (filters.search && filters.search.trim() !== '') {
                globalSearchNav.style.display = 'flex';
                globalSearchNav.href = `{{ route('callcenter.orders.global-search') }}?q=${encodeURIComponent(filters.search)}`;
            } else {
                globalSearchNav.style.display = 'none';
            }
        }
        
        const { data } = await axios.get('{{ route("callcenter.orders.list-data") }}', { params: { ...filters, page } });
        var body = document.getElementById('orders-body');
        if (!data.data.length) { body.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:40px;color:var(--text-muted)">لا طلبات</td></tr>'; document.getElementById('pg-wrap').innerHTML = ''; return; }
        var now = Date.now();
        body.innerHTML = data.data.map(o => {
            var sendAt = o.sent_to_delivery_at ? new Date(o.sent_to_delivery_at) : null;
            var minsLeft = sendAt ? Math.ceil((sendAt - now) / 60000) : null;
            var timeCol = '—';
            if (o.status === 'pending' && sendAt) timeCol = minsLeft > 0 ? `<span style="color:var(--yellow);font-weight:700">يُرسل بعد ${minsLeft} د</span>` : `<span class="badge badge-blue">تم الإرسال</span>`;
            var editBtn = o.can_edit ? `<button class="btn btn-sm btn-secondary" onclick="editOrder(${o.id})">✏️ تعديل</button>` : `<button class="btn btn-sm btn-secondary" style="opacity:0.4" disabled>✏️ تعديل</button>`;
            var sendBtn = o.can_send_early ? `<button class="btn btn-sm btn-info" onclick="sendEarly(${o.id})">⚡ مبكر</button>` : '';
            var cancelBtn = o.status === 'pending' ? `<button class="btn btn-sm btn-danger" onclick="openCancel(${o.id})">✕ إلغاء</button>` : '';
            return `<tr><td><strong style="color:var(--yellow)">${o.order_number}</strong></td><td style="font-size:11px;color:var(--text-muted)">${formatDate(o.created_at)}</td><td>${o.client_name}</td><td>${o.client_phone}</td><td>${o.delivery_name}</td><td>${o.shops_count}</td><td>${parseFloat(o.delivery_fee).toFixed(2)} ج</td><td>${parseFloat(o.total).toFixed(2)} ج</td><td>${statusBadge(o.status)}</td><td><div style="display:flex;gap:4px;flex-wrap:wrap"><button class="btn btn-sm btn-info" onclick="viewOrder(${o.id})">👁 عرض</button>${editBtn}${sendBtn}${cancelBtn}</div></td></tr>`;
        }).join('');
        document.getElementById('pg-wrap').innerHTML = renderPagination(data.last_page, data.current_page, 'loadList');
    } catch(e) { console.error(e); } finally { document.getElementById('tbl-loading').classList.remove('show'); }
}

async function viewOrder(id) {
    openModal('modal-view'); document.getElementById('view-body').innerHTML = '<div style="text-align:center;padding:40px;color:var(--text-muted)"><div class="spin"></div></div>';
    try {
        const { data } = await axios.get(`/callcenter/orders/${id}`); const o = data.order;
        document.getElementById('view-num').textContent = o.order_number;
        document.getElementById('view-body').innerHTML = `<div class="grid-2" style="margin-bottom:16px"><div><div class="info-row"><span class="info-label">العميل</span><span>${o.client?.name ?? '—'} (${o.client?.code ?? ''})</span></div><div class="info-row"><span class="info-label">الهاتف</span><span>${o.client?.phone ?? '—'}</span></div><div class="info-row"><span class="info-label">العنوان</span><span>${o.client_address}</span></div><div class="info-row"><span class="info-label">المندوب</span><span>${o.delivery?.name ?? '—'}</span></div></div><div><div class="info-row"><span class="info-label">الحالة</span><span>${statusBadge(o.status)}</span></div><div class="info-row"><span class="info-label">رسوم توصيل</span><span>${parseFloat(o.delivery_fee).toFixed(2)} ج</span></div><div class="info-row"><span class="info-label">الخصم</span><span>${parseFloat(o.discount).toFixed(2)} ${o.discount_type==='percent'?'%':'ج'}</span></div><div class="info-row"><span class="info-label">الإجمالي</span><strong style="color:var(--yellow)">${parseFloat(o.total).toFixed(2)} ج</strong></div></div></div>${o.send_to_phone ? `<div style="padding:8px 12px;background:var(--bg);border-radius:8px;font-size:13px;margin-bottom:12px">↗ إرسال إلى: ${o.send_to_phone} — ${o.send_to_address}</div>` : ''}${o.notes ? `<div style="padding:8px 12px;background:var(--bg);border-radius:8px;font-size:13px;margin-bottom:12px;color:var(--text-muted)">📝 ${o.notes}</div>` : ''}<div style="font-weight:700;margin-bottom:8px">الأصناف</div><div class="table-wrap" style="margin-bottom:14px"><table><thead><tr><th>الصنف</th><th>المتجر</th><th>الكمية</th><th>السعر</th><th>الإجمالي</th></tr></thead><tbody>${o.items.map(i=>`<tr><td>${i.item_name}</td><td>${i.shop}</td><td>${i.quantity}</td><td>${parseFloat(i.unit_price).toFixed(2)}</td><td>${parseFloat(i.total).toFixed(2)} ج</td></tr>`).join('')}</tbody></table></div><div style="font-weight:700;margin-bottom:8px">التسلسل الزمني</div><div>${[['تاريخ الإنشاء', o.created_at],['إرسال للدلفري', o.sent_to_delivery_at],['قبول المندوب', o.accepted_at],['تم التوصيل', o.delivered_at]].map(([label,val])=>val ? `<div class="info-row"><span class="info-label">${label}</span><span>${formatDate(val)}</span></div>` : '').join('')}</div>`;
    } catch(e) { document.getElementById('view-body').innerHTML = '<div style="color:var(--red);text-align:center">حدث خطأ</div>'; }
}

function openCancel(id) { document.getElementById('cancel-id').value = id; document.getElementById('cancel-reason').value = ''; openModal('modal-cancel'); }
async function doCancel() {
    var id = document.getElementById('cancel-id').value; const reason = document.getElementById('cancel-reason').value;
    try { const { data } = await axios.patch(`/callcenter/orders/${id}/cancel`, { reason }); showSuccess(data.message); closeModal('modal-cancel'); loadList(currentPage); }
    catch(e) { showError(e.response?.data?.message ?? 'حدث خطأ'); }
}
async function sendEarly(id) {
    var ok = await confirmAction('إرسال مبكر', 'هل تريد إرسال الطلب للدلفري الآن؟', 'نعم أرسل'); if (!ok) return;
    try { const { data } = await axios.patch(`/callcenter/orders/${id}/send-early`); showSuccess(data.message); loadList(currentPage); } catch(e) { showError(e.response?.data?.message ?? 'حدث خطأ'); }
}

async function editOrder(id) {
    document.getElementById('edit-delivery').innerHTML = '<option value="">— تلقائي —</option>' + buildDeliveryOptions(); openModal('modal-edit'); document.getElementById('edit-body').style.opacity = '0.5';
    try {
        const { data } = await axios.get(`/callcenter/orders/${id}`); const o = data.order;
        document.getElementById('edit-id').value = o.id; document.getElementById('edit-num').textContent = o.order_number; document.getElementById('edit-address').value = o.client_address || ''; document.getElementById('edit-send-to-phone').value = o.send_to_phone || ''; document.getElementById('edit-send-to-address').value = o.send_to_address || ''; document.getElementById('edit-notes').value = o.notes || ''; document.getElementById('edit-fee').value = o.delivery_fee || 0; document.getElementById('edit-disc').value = o.discount || 0; document.getElementById('edit-disc-type').value = o.discount_type || 'amount';
        if (o.delivery) document.getElementById('edit-delivery').value = o.delivery.id; else document.getElementById('edit-delivery').value = '';
        var tbody = document.getElementById('edit-items'); tbody.innerHTML = '';
        if (o.items && o.items.length) o.items.forEach(i => addEditRow(i.item_name, i.shop_id, i.quantity, i.unit_price)); else addEditRow();
        calcEditTotals(); document.getElementById('edit-body').style.opacity = '1';
    } catch(e) { showError('تعذر جلب بيانات الطلب للتعديل'); closeModal('modal-edit'); }
}

function addEditRow(name = '', shopId = '', qty = 1, price = 0) {
    var tbody = document.getElementById('edit-items'); const tr = document.createElement('tr');
    var shopOptions = SHOPS.map(s => `<option value="${s.id}" ${s.id == shopId ? 'selected' : ''}>${s.name}</option>`).join('');
    tr.innerHTML = `<td style="padding:2px"><input type="text" class="form-control" style="font-size:12px;padding:4px 6px" placeholder="الصنف" value="${name}" oninput="calcEditTotals()"></td><td style="padding:2px"><select class="form-select" style="font-size:12px;padding:4px 6px"><option value="">— متجر —</option>${shopOptions}</select></td><td style="padding:2px"><input type="number" class="form-control" style="font-size:12px;padding:4px 6px" min="0.01" step="1" value="${qty}" oninput="calcEditTotals()"></td><td style="padding:2px"><input type="number" class="form-control" style="font-size:12px;padding:4px 6px" min="0" step="0.5" value="${price}" oninput="calcEditTotals()"></td><td class="edit-row-total" style="padding:2px;font-size:12px;font-weight:700;color:var(--yellow);text-align:center">0 ج</td><td style="padding:2px;text-align:left"><button class="btn btn-sm" style="background:none;color:var(--text-muted);border:none" onclick="delEditRow(this)">✕</button></td>`;
    tbody.appendChild(tr); calcEditTotals();
}
function delEditRow(btn) { btn.closest('tr').remove(); calcEditTotals(); }
function calcEditTotals() {
    var tbody = document.getElementById('edit-items'); let itemsTotal = 0;
    tbody.querySelectorAll('tr').forEach(tr => {
        var inputs = tr.querySelectorAll('input'); const qty = parseFloat(inputs[1].value)||0; const prc = parseFloat(inputs[2].value)||0; const total = qty*prc;
        tr.querySelector('.edit-row-total').textContent = total.toFixed(2) + ' ج'; itemsTotal += total;
    });
    var fee = parseFloat(document.getElementById('edit-fee').value)||0; const disc = parseFloat(document.getElementById('edit-disc').value)||0; const discType = document.getElementById('edit-disc-type').value; let discAmt = disc; if (discType === 'percent') discAmt = itemsTotal * (disc / 100);
    document.getElementById('edit-items-total').textContent = itemsTotal.toFixed(2) + ' ج'; document.getElementById('edit-grand-total').textContent = (itemsTotal+fee-discAmt).toFixed(2) + ' ج';
}

async function saveEdit() {
    var id = document.getElementById('edit-id').value; const items = [];
    document.getElementById('edit-items').querySelectorAll('tr').forEach(tr => {
        var inputs = tr.querySelectorAll('input'); const sel = tr.querySelector('select'); const name = inputs[0].value.trim();
        if (name) items.push({ item_name: name, shop_id: sel.value||null, quantity: parseFloat(inputs[1].value)||1, unit_price: parseFloat(inputs[2].value)||0 });
    });
    if (!items.length) { showError('يجب إضافة صنف واحد على الأقل'); return; }
    var address = document.getElementById('edit-address').value.trim(); if (!address) { showError('يحب إدخال عنوان العميل'); return; }
    var payload = { delivery_id: document.getElementById('edit-delivery').value||null, client_address: address, send_to_phone: document.getElementById('edit-send-to-phone').value||null, send_to_address: document.getElementById('edit-send-to-address').value||null, notes: document.getElementById('edit-notes').value, delivery_fee: document.getElementById('edit-fee').value||0, discount: document.getElementById('edit-disc').value||0, discount_type: document.getElementById('edit-disc-type').value, items };
    var btn = document.getElementById('btn-save-edit'); btn.disabled = true; btn.textContent = 'جاري الحفظ...';
    try { const { data } = await axios.put(`/callcenter/orders/${id}`, payload); showSuccess(data.message); closeModal('modal-edit'); loadList(currentPage); }
    catch(e) { const errors = e.response?.data?.errors; if(errors) showError(Object.values(errors).flat().join(' | ')); else showError(e.response?.data?.message ?? 'حدث خطأ أثناء الحفظ'); }
    finally { btn.disabled = false; btn.textContent = 'حفظ التعديلات ✔'; }
}

loadActiveDeliveries();
loadList(1);
if (typeof addPolling === 'function') addPolling(setInterval(() => loadList(currentPage), 20000));
else setInterval(() => loadList(currentPage), 20000);
</script>
