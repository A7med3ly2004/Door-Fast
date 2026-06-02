{{-- Callcenter Orders Index SPA partial --}}
<div class="section-header">
    <h2>قائمة الطلبات</h2><a href="{{ route('callcenter.orders.create') }}" class="btn btn-primary">طلب جديد</a>
</div>
<div class="card" style="margin-bottom:16px">
    <div class="filter-bar"><input type="text" id="f-search" class="form-control"
            placeholder="رقم الطلب / العميل / الهاتف" style="min-width:200px"><select id="f-status" class="form-select">
            <option value="">كل الحالات</option>
            <option value="pending">قيد الانتظار</option>
            <option value="received">مسلم للمندوب</option>
            <option value="delivered">تم التوصيل</option>
            <option value="cancelled">ملغي</option>
        </select><button class="btn btn-primary" onclick="loadList(1)">بحث</button><button class="btn btn-secondary"
            onclick="resetFilters()">إعادة</button></div>
</div>
<div class="card" style="padding:0;position:relative">
    <div class="loading-overlay" id="tbl-loading">
        <div class="spin"></div>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="text-align:center;">رقم الطلب</th>
                    <th style="text-align:center;">التاريخ</th>
                    <th style="text-align:center;">العميل</th>
                    <th style="text-align:center;">الهاتف</th>
                    <th style="text-align:center;">المندوب</th>
                    <th style="text-align:center;">المتاجر</th>
                    <th style="text-align:center;">قيمة التوصيل</th>
                    <th style="text-align:center;">الإجمالي</th>
                    <th style="text-align:center;">الحالة</th>
                    <th style="text-align:center;">إجراءات</th>
                </tr>
            </thead>
            <tbody id="orders-body">
                <tr>
                    <td colspan="10" style="text-align:center;padding:40px;color:var(--text-muted)">جاري التحميل...</td>
                </tr>
            </tbody>
        </table>
    </div>
    <div id="pg-wrap" style="padding:14px"></div>
</div>

<div class="modal-overlay" id="modal-view">
    <div class="modal modal-lg">
        <div class="modal-header">
            <div style="display:flex;align-items:center;gap:12px;">
                <h3>تفاصيل الطلب — <span id="view-num"></span></h3><a id="modal-pdf-btn" href="#" target="_blank"
                    class="btn btn-sm btn-secondary" onclick="if(this.href==='#'){event.preventDefault();}"
                    style="background-color: #c92f2f;">إنشاء PDF</a>
            </div><button class="btn-close" onclick="closeModal('modal-view')">✕</button>
        </div>
        <div class="modal-body" id="view-body"></div>
    </div>
</div>
<div class="modal-overlay" id="modal-cancel">
    <div class="modal">
        <div class="modal-header">
            <h3>إلغاء الطلب</h3><button class="btn-close" onclick="closeModal('modal-cancel')">✕</button>
        </div>
        <div class="modal-body"><input type="hidden" id="cancel-id">
            <div class="form-group"><label class="form-label">سبب الإلغاء</label><textarea class="form-control"
                    id="cancel-reason" rows="3" placeholder="اكتب سبب الإلغاء..."></textarea></div>
            <div class="modal-footer"><button class="btn btn-secondary"
                    onclick="closeModal('modal-cancel')">تراجع</button><button class="btn btn-danger"
                    onclick="doCancel()">إلغاء الطلب</button></div>
        </div>
    </div>
</div>
<div class="modal-overlay" id="modal-edit">
    <div class="modal modal-lg" style="max-width:800px">
        <div class="modal-header">
            <h3>تعديل الطلب — <span id="edit-num"></span></h3><button class="btn-close"
                onclick="closeEditModal()">✕</button>
        </div>
        <div class="modal-body" id="edit-body"><input type="hidden" id="edit-id">
            <div class="grid-2" style="margin-bottom:12px">
                <div class="form-group"><label class="form-label">المندوب (تلقائي إن تُرك فارغاً)</label><select
                        class="form-select" id="edit-delivery"></select></div>
                <div class="form-group">
                    <label class="form-label">عنوان العميل *</label>
                    <select class="form-select" id="edit-address-select"
                        onchange="toggleNewAddress('edit-address-select', 'edit-address-txt')"></select>
                    <input type="text" class="form-control" id="edit-address-txt" style="display:none; margin-top:8px;"
                        placeholder="اكتب العنوان الجديد...">
                </div>
            </div>
            <div class="grid-2" style="margin-bottom:12px">
                <div class="form-group">
                    <label class="form-label">هاتف العميل</label>
                    <input type="text" class="form-control" id="edit-client-phone" readonly dir="ltr"
                        style="text-align:right">
                </div>
                <div class="form-group" id="edit-client-phone2-group" style="display:none;">
                    <label class="form-label">هاتف 2</label>
                    <input type="text" class="form-control" id="edit-client-phone2" readonly dir="ltr"
                        style="text-align:right">
                </div>
            </div>
            <div id="edit-send-to-section"
                style="background:rgba(255,255,255,0.02);border-radius:8px;padding:12px;margin-bottom:12px;border:1px dashed var(--border);display:none;">
                <div style="font-size:12px;font-weight:700;margin-bottom:8px;color:var(--text-muted)">↗ إرسال إلى عميل
                    آخر (اختياري)</div>
                <div class="grid-2">
                    <div class="form-group"><label class="form-label">هاتف المستلم</label><input type="text"
                            class="form-control" id="edit-send-to-phone" dir="ltr" style="text-align:right"></div>
                    <div class="form-group"><label class="form-label">هاتف 2 (العميل المستلم)</label><input type="text"
                            class="form-control" id="edit-send-to-phone2" dir="ltr" style="text-align:right"
                            placeholder="اختياري"></div>
                </div>
                <div class="grid-2" style="margin-top:12px">
                    <div class="form-group"><label class="form-label">اسم العميل المستلم</label><input type="text"
                            class="form-control" id="edit-send-to-name"></div>
                    <div class="form-group">
                        <label class="form-label">عنوان المستلم</label>
                        <select class="form-select" id="edit-send-to-address-select"
                            onchange="toggleNewAddress('edit-send-to-address-select', 'edit-send-to-address-txt')"></select>
                        <input type="text" class="form-control" id="edit-send-to-address-txt"
                            style="display:none; margin-top:8px;" placeholder="اكتب العنوان الجديد...">
                    </div>
                </div>
            </div>
            <div class="form-group"><label class="form-label">ملاحظات</label><textarea class="form-control"
                    id="edit-notes" rows="2" placeholder="ملاحظات اختيارية..."></textarea></div>
            <div style="font-weight:700;margin-top:16px;margin-bottom:8px">الأصناف</div>
            <div class="table-wrap" style="margin-bottom:12px;overflow:visible">
                <table class="items-table" style="width:100%;border-collapse:collapse">
                    <thead>
                        <tr style="border-bottom:1px solid var(--border)">
                            <th style="padding:4px;text-align:right">الصنف</th>
                            <th style="padding:4px;text-align:right;width:130px">المتجر</th>
                            <th style="padding:4px;text-align:right;width:65px">الكمية</th>
                            <th style="padding:4px;text-align:right;width:80px">السعر</th>
                            <th style="padding:4px;text-align:right;width:80px">الإجمالي</th>
                            <th style="padding:4px;width:30px"></th>
                        </tr>
                    </thead>
                    <tbody id="edit-items"></tbody>
                </table>
            </div><button class="btn btn-secondary btn-sm" onclick="addEditRow()">＋ إضافة صنف</button>
            <div class="grid-2" style="margin-top:16px">
                <div class="form-group"><label class="form-label">رسوم التوصيل</label><input type="number"
                        class="form-control" id="edit-fee" min="0" step="0.5" oninput="calcEditTotals()"></div>
                <div class="form-group"><label class="form-label">الخصم</label>
                    <div style="display:flex;gap:5px"><input type="number" class="form-control" id="edit-disc" min="0"
                            step="0.5" oninput="calcEditTotals()"><select class="form-select" id="edit-disc-type"
                            style="width:70px" onchange="calcEditTotals()">
                            <option value="amount">ج</option>
                            <option value="percent">%</option>
                        </select></div>
                </div>
            </div>
            <div style="background:var(--bg);padding:12px;margin-top:16px;border-radius:8px">
                <div style="display:flex;justify-content:space-between;margin-bottom:4px"><span
                        style="font-size:13px">إجمالي الأصناف:</span> <strong id="edit-items-total"
                        style="font-size:14px">0 ج</strong></div>
                <div
                    style="display:flex;justify-content:space-between;font-size:18px;color:var(--yellow);font-weight:800;border-top:1px solid var(--border);padding-top:6px">
                    <span>الإجمالي النهائي:</span> <span id="edit-grand-total">0 ج</span>
                </div>
            </div>
        </div>
        <div class="modal-footer"><button class="btn btn-secondary" onclick="closeEditModal()">تراجع</button><button
                class="btn btn-primary" id="btn-save-edit" onclick="saveEdit()">حفظ التعديلات ✔</button></div>
    </div>
</div>

<script>
    var SHOPS = @json($shops);
    var activeDeliveries = [];
    var currentPage = 1;

    async function loadActiveDeliveries() { try { const { data } = await axios.get('{{ route("callcenter.delivery.active") }}'); activeDeliveries = data; } catch (e) { } }
    function buildDeliveryOptions() { return activeDeliveries.map(d => `<option value="${d.id}">${d.name} (${d.orders_today}/${d.max_orders})</option>`).join(''); }
    function getFilters() { return { search: document.getElementById('f-search').value, status: document.getElementById('f-status').value }; }
    function resetFilters() { document.getElementById('f-search').value = ''; document.getElementById('f-status').value = ''; loadList(1); }

    async function loadList(page = 1) {
        // إذا غادر المستخدم الصفحة وعناصر DOM لم تعد موجودة، نوقف التنفيذ
        var loadingEl = document.getElementById('tbl-loading');
        if (!loadingEl) return;
        currentPage = page; loadingEl.classList.add('show');
        try {
            const filters = getFilters();
            const globalSearchNav = document.getElementById('nav-global-search');
            if (globalSearchNav && filters.search && filters.search.trim() !== '') {
                globalSearchNav.href = `{{ route('callcenter.orders.global-search') }}?q=${encodeURIComponent(filters.search)}`;
            }


            const { data } = await axios.get('{{ route("callcenter.orders.list-data") }}', { params: { ...filters, page } });
            var body = document.getElementById('orders-body');
            if (!body) return;
            if (!data.data.length) { body.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:40px;color:var(--text-muted)">لا طلبات</td></tr>'; var pgWrap = document.getElementById('pg-wrap'); if (pgWrap) pgWrap.innerHTML = ''; return; }
            var now = Date.now();
            body.innerHTML = data.data.map(o => {
                var sendAt = o.sent_to_delivery_at ? new Date(o.sent_to_delivery_at) : null;
                var minsLeft = sendAt ? Math.ceil((sendAt - now) / 60000) : null;
                var timeCol = '—';
                if (o.status === 'pending' && sendAt) timeCol = minsLeft > 0 ? `<span style="color:var(--yellow);font-weight:700">يُرسل بعد ${minsLeft} د</span>` : `<span class="badge badge-blue">تم الإرسال</span>`;
                var editBtn = o.can_edit ? `<button class="btn btn-sm btn-secondary" onclick="editOrder(${o.id})">تعديل</button>` : `<button class="btn btn-sm btn-secondary" style="opacity:0.4" disabled>تعديل</button>`;
                var sendBtn = o.can_send_early ? `<button class="btn btn-sm btn-info" onclick="sendEarly(${o.id})">مبكر</button>` : '';
                var cancelBtn = o.status === 'pending' ? `<button class="btn btn-sm btn-danger" onclick="openCancel(${o.id})">✕ إلغاء</button>` : '';
                return `<tr><td style=" text-align: center;"><strong style="color:var(--yellow)">${o.order_number}</strong></td><td style="font-size:11px;color:var(--text-muted); text-align: center;">${formatDate(o.created_at)}</td><td style=" text-align: center;">${o.client_name}</td><td style=" text-align: center;">${o.client_phone}</td><td style=" text-align: center;">${o.delivery_name}</td><td style=" text-align: center;">${o.shops_count}</td><td style=" text-align: center;">${parseFloat(o.delivery_fee).toFixed(2)} ج</td><td style=" text-align: center;">${parseFloat(o.total).toFixed(2)} ج</td><td style=" text-align: center;">${statusBadge(o.status)}</td><td style=" text-align: center;"><div style="display:flex;gap:4px;flex-wrap:wrap; justify-content: center;"><button class="btn btn-sm btn-info" onclick="viewOrder(${o.id})">عرض</button>${editBtn}${sendBtn}${cancelBtn}</div></td></tr>`;
            }).join('');
            var pgWrap2 = document.getElementById('pg-wrap'); if (pgWrap2) pgWrap2.innerHTML = renderPagination(data.last_page, data.current_page, 'loadList');
        } catch (e) { console.error(e); } finally { var el = document.getElementById('tbl-loading'); if (el) el.classList.remove('show'); }
    }

    async function viewOrder(id) {
        openModal('modal-view');
        document.getElementById('view-body').innerHTML = '<div style="display:flex;flex-direction:column;align-items:center;padding:40px;color:var(--text-muted);"><div class="spin" style="margin-bottom:16px;"></div><div>جاري تحميل التفاصيل...</div></div>';
        try {
            const { data } = await axios.get(`/callcenter/orders/${id}`); const o = data.order;
            document.getElementById('view-num').textContent = o.order_number;
            document.getElementById('modal-pdf-btn').href = '/callcenter/orders/' + o.id + '/pdf';
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
                    <span style="font-weight:600; text-align:left;">${o.client_address}</span>
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
                    <span style="color:var(--text-muted); font-size:13px;">إجمالي الأصناف</span>
                    <span style="font-weight:600;">${itemsTotal.toFixed(2)} ج</span>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center; padding-bottom:8px; border-bottom:1px solid var(--border);">
                    <span style="color:var(--text-muted); font-size:13px;">رسوم التوصيل</span>
                    <span style="font-weight:600;">${parseFloat(o.delivery_fee).toFixed(2)} ج</span>
                </div>
                ${parseFloat(o.discount) > 0 ? `<div style="display:flex; justify-content:space-between; align-items:center; padding-bottom:8px; border-bottom:1px solid var(--border);">
                    <span style="color:var(--text-muted); font-size:13px;">الخصم</span>
                    <span style="font-weight:600; color:var(--red);">${parseFloat(o.discount).toFixed(2)} ${o.discount_type === 'percent' ? '%' : 'ج'}</span>
                </div>` : ''}
                <div style="display:flex; justify-content:space-between; align-items:center; margin-top:4px;">
                    <span style="font-size:14px; font-weight:700;">الإجمالي النهائي</span>
                    <strong style="color:var(--yellow); font-size:18px;">${parseFloat(o.total).toFixed(2)} ج</strong>
                </div>
            </div>
        </div></div>`;
            if (o.send_to_phone || o.notes) {
                html += `<div style="display:flex; flex-direction:column; gap:12px; margin-bottom:20px;">`;
                if (o.send_to_phone) {
                    let clientName = o.send_to_name || '—';
                    if (o.recipient_client && o.recipient_client.code) {
                        clientName += ` (${o.recipient_client.code})`;
                    }
                    let phones = o.send_to_phone;
                    if (o.send_to_phone2) {
                        phones += ` / ${o.send_to_phone2}`;
                    }

                    html += `<div style="background:rgba(255,255,255,0.02); border:1px dashed var(--yellow); border-radius:10px; padding:16px;">
                    <div style="font-size:14px; font-weight:700; color:var(--yellow); margin-bottom:12px; display:flex; align-items:center; gap:8px;">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path></svg>
                        إرسال إلى عميل آخر
                    </div>
                    <div style="display:flex; flex-direction:column; gap:10px;">
                        <div style="padding-bottom:8px; border-bottom:1px dashed rgba(255,255,255,0.1);">
                            <span style="color:var(--text-muted); font-size:13px;">الاسم</span>
                            <span style="font-weight:600;">${clientName}</span>
                        </div>
                        <div style="padding-bottom:8px; border-bottom:1px dashed rgba(255,255,255,0.1);">
                            <span style="color:var(--text-muted); font-size:13px;">الهاتف</span>
                            <span style="font-weight:600; direction:ltr;">${phones}</span>
                        </div>
                        <div>
                            <span style="color:var(--text-muted); font-size:13px;">العنوان</span>
                            <span style="font-weight:600; text-align:left;">${o.send_to_address || '—'}</span>
                        </div>
                    </div>
                </div>`;
                }
                if (o.notes) {
                    html += `<div style="display:flex; align-items:flex-start; gap:12px; background:var(--bg); border:1px solid var(--border); border-radius:10px; padding:12px;">
                    <div style="color:var(--text-muted); margin-top:2px;">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    </div>
                    <div>
                        <div style="font-size:12px; font-weight:700; color:var(--text-muted); margin-bottom:4px;">ملاحظات الطلب</div>
                        <div style="font-size:14px; line-height:1.5;">${o.notes}</div>
                    </div>
                </div>`;
                }
                html += `</div>`;
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
                            <td style="padding:12px 16px; text-align:center;">${parseFloat(i.unit_price).toFixed(2)}</td>
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
                        ${o.logs.map((l, index) => `<div style="display:flex; align-items:flex-start; gap:16px; position:relative; z-index:2;">
                            <div style="width:32px; height:32px; border-radius:50%; background:${index === 0 ? 'var(--yellow)' : 'var(--bg)'}; border:2px solid ${index === 0 ? 'var(--yellow)' : 'var(--border)'}; display:flex; align-items:center; justify-content:center; color:${index === 0 ? '#000' : 'var(--text-muted)'}; flex-shrink:0; margin-top:2px;">
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${index === 0 ? 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z' : 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'}"></path></svg>
                            </div>
                            <div style="flex:1;">
                                <div style="font-size:13px; font-weight:700; color:${index === 0 ? 'var(--text)' : 'var(--text-muted)'};">${l.action} <span style="font-weight:400; color:var(--text-muted); margin-right:4px;">— ${l.user}</span></div>
                                ${l.notes ? `<div style="font-size:12px; color:var(--text-muted); margin-top:5px; padding:6px 10px; line-height:1.5;">${l.notes}</div>` : ''}
                                <div style="font-size:12px; color:var(--text-muted); margin-top:4px; direction:ltr; text-align:right;">${formatDate(l.created_at)}</div>
                            </div>
                        </div>`).join('')}
                    </div>
                </div>`;
            }
            document.getElementById('view-body').innerHTML = html;
        } catch (e) {
            document.getElementById('view-body').innerHTML = `<div style="padding:40px; text-align:center;">
            <div style="display:inline-flex; align-items:center; justify-content:center; width:64px; height:64px; border-radius:50%; background:rgba(255,0,0, 0.1); color:var(--red); margin-bottom:16px;">
                <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
            <h3 style="margin-bottom:8px;">عذراً، حدث خطأ</h3>
            <p style="color:var(--text-muted); font-size:14px;">لم نتمكن من جلب بيانات الطلب. يرجى المحاولة مرة أخرى.</p>
        </div>`;
        }
    }

    function openCancel(id) { document.getElementById('cancel-id').value = id; document.getElementById('cancel-reason').value = ''; openModal('modal-cancel'); }
    async function doCancel() {
        var id = document.getElementById('cancel-id').value; const reason = document.getElementById('cancel-reason').value;
        try { const { data } = await axios.patch(`/callcenter/orders/${id}/cancel`, { reason }); showSuccess(data.message); closeModal('modal-cancel'); loadList(currentPage); }
        catch (e) { showError(e.response?.data?.message ?? 'حدث خطأ'); }
    }
    async function sendEarly(id) {
        var ok = await confirmAction('إرسال مبكر', 'هل تريد إرسال الطلب للمندوب الآن؟', 'نعم أرسل'); if (!ok) return;
        try { const { data } = await axios.patch(`/callcenter/orders/${id}/send-early`); showSuccess(data.message); loadList(currentPage); } catch (e) { showError(e.response?.data?.message ?? 'حدث خطأ'); }
    }

    // ── Edit modal — timer freeze (keepalive) ───────────────────────
    // بينما الفورم مفتوح نُرسل pause-edit كل 30 ثانية باستمرار
    // حتى يظل المؤقت متجمداً تماماً.
    var _editingOrderId = null;   // id الطلب المفتوح حالياً
    var _editKeepalive = null;   // intervalId الـ keepalive
    // كل كم ثانية نُجدد المؤقت (أقل من نصف hold_minutes)
    var EDIT_KEEPALIVE_MS = 20000; // 20 ثانية

    /** إرسال pause-edit — يُعيد ضبط sent_to_delivery_at من جديد. */
    async function callPauseEdit(id) {
        if (!id) return;
        try { await axios.patch(`/callcenter/orders/${id}/pause-edit`); } catch (_) { /* 422 = انتهى الوقت مسبقاً — نتجاهل */ }
    }

    /** تشغيل الـ keepalive: يُرسل pause-edit فوراً ثم كل EDIT_KEEPALIVE_MS. */
    function startEditKeepalive(id) {
        stopEditKeepalive();           // أوقف أي keepalive سابق
        callPauseEdit(id);             // إرسال فوري عند الفتح
        _editKeepalive = setInterval(() => callPauseEdit(id), EDIT_KEEPALIVE_MS);
    }

    /** إيقاف الـ keepalive عند الإغلاق أو الحفظ. */
    function stopEditKeepalive() {
        if (_editKeepalive) { clearInterval(_editKeepalive); _editKeepalive = null; }
    }

    /**
     * إغلاق مودال التعديل:
     * - يُوقف الـ keepalive
     * - يُعيد ضبط المؤقت مرة أخيرة (يبدأ العد من الصفر)
     * - يُغلق النافذة ويُحدّث القائمة
     */
    async function closeEditModal() {
        stopEditKeepalive();
        const id = _editingOrderId;
        _editingOrderId = null;
        closeModal('modal-edit');
        if (id) {
            // إعادة ضبط المؤقت من الصفر بعد الإغلاق
            try { await axios.patch(`/callcenter/orders/${id}/pause-edit`); } catch (_) { }
        }
        loadList(currentPage);
    }

    async function editOrder(id) {
        _editingOrderId = id;
        document.getElementById('edit-delivery').innerHTML = '<option value="">— تلقائي —</option>' + buildDeliveryOptions();
        openModal('modal-edit');
        document.getElementById('edit-body').style.opacity = '0.5';

        // ابدأ الـ keepalive فوراً — الفورم مفتوح والمؤقت يجب أن يتجمد
        startEditKeepalive(id);

        try {
            const { data } = await axios.get(`/callcenter/orders/${id}`); const o = data.order;
            document.getElementById('edit-id').value = o.id; document.getElementById('edit-num').textContent = o.order_number; document.getElementById('edit-notes').value = o.notes || ''; document.getElementById('edit-fee').value = o.delivery_fee || 0; document.getElementById('edit-disc').value = o.discount || 0; document.getElementById('edit-disc-type').value = o.discount_type || 'amount';
            if (o.delivery) document.getElementById('edit-delivery').value = o.delivery.id; else document.getElementById('edit-delivery').value = '';

            let clientAddrsHtml = '<option value="">— اختر العنوان —</option>';
            let foundPrimary = false;
            if (o.client && o.client.addresses && o.client.addresses.length) {
                o.client.addresses.forEach(a => {
                    let sel = (a.address === o.client_address) ? 'selected' : '';
                    if (sel) foundPrimary = true;
                    clientAddrsHtml += `<option value="${a.address}" ${sel}>${a.address}</option>`;
                });
            }
            if (!foundPrimary && o.client_address) {
                clientAddrsHtml += `<option value="${o.client_address}" selected>${o.client_address}</option>`;
            }
            clientAddrsHtml += `<option value="new">+ اضافة عنوان جديد</option>`;
            document.getElementById('edit-address-select').innerHTML = clientAddrsHtml;
            document.getElementById('edit-address-txt').style.display = 'none';
            document.getElementById('edit-address-txt').value = '';

            let sendToAddrsHtml = '<option value="">— اختر العنوان —</option>';
            let foundSendTo = false;
            if (o.recipient_client && o.recipient_client.addresses && o.recipient_client.addresses.length) {
                o.recipient_client.addresses.forEach(a => {
                    let sel = (a.address === o.send_to_address) ? 'selected' : '';
                    if (sel) foundSendTo = true;
                    sendToAddrsHtml += `<option value="${a.address}" ${sel}>${a.address}</option>`;
                });
            }
            if (!foundSendTo && o.send_to_address) {
                sendToAddrsHtml += `<option value="${o.send_to_address}" selected>${o.send_to_address}</option>`;
            }
            sendToAddrsHtml += `<option value="new">+ اضافة عنوان جديد</option>`;
            document.getElementById('edit-send-to-address-select').innerHTML = sendToAddrsHtml;
            document.getElementById('edit-send-to-address-txt').style.display = 'none';
            document.getElementById('edit-send-to-address-txt').value = '';

            document.getElementById('edit-client-phone').value = o.client?.phone ?? '';
            if (o.client?.phone2) {
                document.getElementById('edit-client-phone2-group').style.display = 'block';
                document.getElementById('edit-client-phone2').value = o.client.phone2;
            } else {
                document.getElementById('edit-client-phone2-group').style.display = 'none';
            }

            const hasSendTo = o.send_to_phone && o.send_to_phone.trim() !== '';
            const sendToSection = document.getElementById('edit-send-to-section');
            if (hasSendTo) {
                sendToSection.style.display = 'block';
                document.getElementById('edit-send-to-phone').value = o.send_to_phone ?? '';
                document.getElementById('edit-send-to-phone2').value = o.send_to_phone2 ?? '';
                document.getElementById('edit-send-to-name').value = o.send_to_name ?? '';
                document.getElementById('edit-send-to-address-select').value = o.send_to_address ?? '';
            } else {
                sendToSection.style.display = 'none';
                document.getElementById('edit-send-to-phone').value = '';
                document.getElementById('edit-send-to-phone2').value = '';
                document.getElementById('edit-send-to-name').value = '';
                document.getElementById('edit-send-to-address-select').value = '';
                document.getElementById('edit-send-to-address-txt').value = '';
                document.getElementById('edit-send-to-address-txt').style.display = 'none';
            }
            var tbody = document.getElementById('edit-items'); tbody.innerHTML = '';
            if (o.items && o.items.length) o.items.forEach(i => addEditRow(i.item_name, i.shop_id, i.quantity, i.unit_price)); else addEditRow();
            calcEditTotals(); document.getElementById('edit-body').style.opacity = '1';
        } catch (e) { showError('تعذر جلب بيانات الطلب للتعديل'); stopEditKeepalive(); closeModal('modal-edit'); }
    }

    function addEditRow(name = '', shopId = '', qty = 1, price = 0) {
        var tbody = document.getElementById('edit-items'); const tr = document.createElement('tr');
        var shopOptions = SHOPS.map(s => `<option value="${s.id}" ${s.id == shopId ? 'selected' : ''}>${s.name}</option>`).join('');
        tr.innerHTML = `<td style="padding:2px"><input type="text" class="form-control" style="font-size:12px;padding:4px 6px" placeholder="الصنف" value="${name}" oninput="calcEditTotals()"></td><td style="padding:2px"><select class="form-select" style="font-size:12px;padding:4px 6px"><option value="">— متجر —</option>${shopOptions}</select></td><td style="padding:2px"><input type="number" class="form-control" style="font-size:12px;padding:4px 6px" min="0.01" step="any" value="${qty}" oninput="calcEditTotals()"></td><td style="padding:2px"><input type="number" class="form-control" style="font-size:12px;padding:4px 6px" min="0" step="0.5" value="${price}" oninput="calcEditTotals()"></td><td class="edit-row-total" style="padding:2px;font-size:12px;font-weight:700;color:var(--yellow);text-align:center">0 ج</td><td style="padding:2px;text-align:left"><button class="btn btn-sm" style="background:none;color:var(--text-muted);border:none" onclick="delEditRow(this)">✕</button></td>`;
        tbody.appendChild(tr); calcEditTotals();
    }
    function toggleNewAddress(selId, txtId) {
        var sel = document.getElementById(selId);
        var txt = document.getElementById(txtId);
        if (sel.value === 'new') {
            txt.style.display = 'block';
            txt.focus();
        } else {
            txt.style.display = 'none';
        }
    }
    function delEditRow(btn) { btn.closest('tr').remove(); calcEditTotals(); }
    function calcEditTotals() {
        var tbody = document.getElementById('edit-items'); let itemsTotal = 0;
        tbody.querySelectorAll('tr').forEach(tr => {
            var inputs = tr.querySelectorAll('input'); const qty = parseFloat(inputs[1].value) || 0; const prc = parseFloat(inputs[2].value) || 0; const total = qty * prc;
            tr.querySelector('.edit-row-total').textContent = total.toFixed(2) + ' ج'; itemsTotal += total;
        });
        var fee = parseFloat(document.getElementById('edit-fee').value) || 0;
        const baseTotal = itemsTotal + fee;
        const disc = parseFloat(document.getElementById('edit-disc').value) || 0;
        const discType = document.getElementById('edit-disc-type').value;
        let discAmt = disc;
        if (discType === 'percent') discAmt = baseTotal * (disc / 100);
        document.getElementById('edit-items-total').textContent = itemsTotal.toFixed(2) + ' ج'; document.getElementById('edit-grand-total').textContent = (baseTotal - discAmt).toFixed(2) + ' ج';
    }

    async function saveEdit() {
        var id = document.getElementById('edit-id').value; const items = [];
        document.getElementById('edit-items').querySelectorAll('tr').forEach(tr => {
            var inputs = tr.querySelectorAll('input'); const sel = tr.querySelector('select'); const name = inputs[0].value.trim();
            if (name) items.push({ item_name: name, shop_id: sel.value || null, quantity: parseFloat(inputs[1].value) || 1, unit_price: parseFloat(inputs[2].value) || 0 });
        });
        if (!items.length) { showError('يجب إضافة صنف واحد على الأقل'); return; }

        let address = document.getElementById('edit-address-select').value;
        if (address === 'new') address = document.getElementById('edit-address-txt').value.trim();
        if (!address) { showError('يحب إدخال عنوان العميل'); return; }

        let stAddress = document.getElementById('edit-send-to-address-select').value;
        if (stAddress === 'new') stAddress = document.getElementById('edit-send-to-address-txt').value.trim();

        var payload = { delivery_id: document.getElementById('edit-delivery').value || null, client_address: address, send_to_phone: document.getElementById('edit-send-to-phone').value || null, send_to_phone2: document.getElementById('edit-send-to-phone2').value || null, send_to_name: document.getElementById('edit-send-to-name').value || null, send_to_address: stAddress || null, notes: document.getElementById('edit-notes').value, delivery_fee: document.getElementById('edit-fee').value || 0, discount: document.getElementById('edit-disc').value || 0, discount_type: document.getElementById('edit-disc-type').value, items };
        var btn = document.getElementById('btn-save-edit'); btn.disabled = true; btn.textContent = 'جاري الحفظ...';
        try {
            const { data } = await axios.put(`/callcenter/orders/${id}`, payload);
            showSuccess(data.message);
            stopEditKeepalive();      // إيقاف الـ keepalive بعد الحفظ الناجح
            _editingOrderId = null;   // تم الحفظ — لا حاجة لإعادة ضبط المؤقت مرة أخرى
            closeModal('modal-edit');
            loadList(currentPage);
        }
        catch (e) { const errors = e.response?.data?.errors; if (errors) showError(Object.values(errors).flat().join(' | ')); else showError(e.response?.data?.message ?? 'حدث خطأ أثناء الحفظ'); }
        finally { btn.disabled = false; btn.textContent = 'حفظ التعديلات ✔'; }
    }

    loadActiveDeliveries();
    loadList(1);
    if (typeof addPolling === 'function') addPolling(setInterval(() => loadList(currentPage), 20000));
    else setInterval(() => loadList(currentPage), 20000);
</script>