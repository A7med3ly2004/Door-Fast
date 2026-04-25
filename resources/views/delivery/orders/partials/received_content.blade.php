{{-- Delivery Received Orders SPA partial --}}
<style>
.orders-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(350px,1fr)); gap:20px; }
.order-card { background:white; border-radius:12px; padding:20px; border:1px solid var(--border-color); box-shadow:0 4px 6px rgba(0,0,0,0.05); display:flex; flex-direction:column; }
.order-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-bottom:1px solid var(--border-color); padding-bottom:10px; }
.order-number { font-size:24px; font-weight:800; color:var(--primary); }
.time-badge { background-color:#fee2e2; color:var(--secondary); padding:4px 10px; border-radius:15px; font-size:12px; font-weight:700; }
.info-group { margin-bottom:15px; }
.info-label { font-size:13px; color:var(--text-muted); font-weight:600; margin-bottom:4px; }
.info-value { font-size:16px; color:var(--text-dark); font-weight:700; }
.phone-link { color:#2563eb; text-decoration:none; display:inline-flex; align-items:center; gap:5px; direction:ltr; }
.btn-view { width:100%; padding:14px; background-color:var(--primary); color:white; border:none; border-radius:8px; font-size:16px; font-weight:700; cursor:pointer; transition:0.3s; margin-top:auto; }
.btn-view:hover { background-color:#d97706; transform:translateY(-2px); }

/* New Card Layout Styles */
.two-party-info { display:flex; flex-direction:column; gap:8px; background:#f8fafc; border-radius:10px; padding:12px; margin-bottom:15px; border:1px solid #e2e8f0; }
.party-label { font-size:12px; color:var(--text-muted); margin-bottom:4px; font-weight:600; }
.party { display:flex; flex-direction:column; gap:4px; font-size:14px; }
.party.sender { color:#475569; }
.party.receiver { color:var(--text-dark); background:#ecfdf5; padding:12px; border-radius:8px; border:1px dashed #34d399; margin-top:4px; }
.party a { color:#2563eb; text-decoration:none; font-weight:600; direction:ltr; display:inline-block; }
.party-divider { display:flex; justify-content:center; color:#94a3b8; }
.arrow-icon { width:20px; height:20px; }

.single-party-info { display:flex; flex-direction:column; gap:10px; margin-bottom:15px; padding:5px 0; }
.party-row { display:flex; align-items:flex-start; gap:8px; font-size:14.5px; color:var(--text-dark); line-height:1.4; }
.party-row .icon { flex-shrink:0; width:22px; text-align:center; font-size:16px; }

.total-row { display:flex; justify-content:space-between; align-items:center; background:#f0f9ff; padding:12px 15px; border-radius:10px; margin-bottom:15px; border:1px solid #bae6fd; }
.total-label { font-weight:700; color:#0369a1; font-size:15px; }
.total-amount { font-size:22px; font-weight:800; color:#0284c7; }

/* Modal Styles */
.modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:1000; display:none; align-items:center; justify-content:center; padding:20px; }
.modal-overlay.open { display:flex; }
.modal-content { background:white; width:100%; max-width:600px; max-height:90vh; overflow-y:auto; border-radius:16px; position:relative; display:flex; flex-direction:column; box-shadow:0 10px 25px rgba(0,0,0,0.2); animation:modalIn 0.3s ease; }
@keyframes modalIn { from{ opacity:0; transform:translateY(-20px); } to{ opacity:1; transform:translateY(0); } }
.modal-header { padding:20px; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center; position:sticky; top:0; background:white; z-index:10; border-radius:16px 16px 0 0; }
.modal-header h3 { font-size:20px; font-weight:800; color:var(--text-dark); margin:0; }
.btn-close-modal { background:none; border:none; font-size:24px; cursor:pointer; color:var(--text-muted); transition:0.2s; }
.btn-close-modal:hover { color:var(--secondary); }
.modal-body { padding:20px; flex:1; }
.items-list-container { border:1px solid #e2e8f0; border-radius:12px; margin-bottom:20px; overflow:hidden; }
.items-list-header { background:#f1f5f9; padding:12px 15px; font-weight:700; color:#334155; font-size:15px; border-bottom:1px solid #e2e8f0; display:flex; align-items:center; gap:8px; }
.items-list-body { background:#ffffff; padding:10px 15px; display:flex; flex-direction:column; gap:10px; }
.item-row { display:flex; align-items:center; justify-content:space-between; padding-bottom:10px; border-bottom:1px dashed #e2e8f0; }
.item-row:last-child { border-bottom:none; padding-bottom:0; }
.item-main { display:flex; align-items:center; gap:12px; flex:1; }
.item-qty { background:#e0f2fe; color:#0369a1; font-weight:800; font-size:14px; padding:4px 8px; border-radius:6px; min-width:35px; text-align:center; flex-shrink:0; }
.item-details { flex:1; }
.item-name { font-size:15px; font-weight:700; color:#1e293b; margin-bottom:4px; line-height:1.3; }
.item-shop { font-size:13px; color:#64748b; display:flex; align-items:center; gap:4px; }
.item-pricing { text-align:left; flex-shrink:0; margin-right:10px; }
.item-total { font-weight:800; font-size:15px; color:var(--primary); }
.item-unit { font-size:12px; color:var(--text-muted); margin-top:2px; }
.money-total { font-size:26px; font-weight:800; color:var(--success); text-align:center; padding:15px; background:#ecfdf5; border-radius:8px; border:1px dashed var(--success); margin-bottom:20px; }
.modal-footer { padding:20px; border-top:1px solid var(--border-color); display:flex; gap:10px; background:white; position:sticky; bottom:0; border-radius:0 0 16px 16px; z-index:10; }
.btn-deliver { flex:2; padding:14px; background-color:var(--success); color:white; border:none; border-radius:8px; font-size:18px; font-weight:700; cursor:pointer; justify-content:center; display:flex; align-items:center; gap:8px; transition:0.3s; }
.btn-deliver:hover { background-color:#059669; }
.btn-cancel { flex:1; padding:12px; background-color:white; color:var(--secondary); border:1px solid var(--secondary); border-radius:8px; font-size:16px; font-weight:700; cursor:pointer; transition:0.3s; }
.btn-cancel:hover { background-color:#fee2e2; }

/* MOBILE: responsive received orders */
@media (max-width: 768px) {
    /* MOBILE: single column grid */
    .orders-grid { grid-template-columns: 1fr; gap: 12px; }
    .order-card { padding: 16px; }
    .order-number { font-size: 20px; }
    .info-label { font-size: 12px; }
    .info-value { font-size: 14px; }
    .btn-view { padding: 12px; font-size: 15px; }
    /* MOBILE: full-screen modal on phones */
    .modal-overlay { padding: 0; }
    .modal-content { max-width: 100%; max-height: 100vh; border-radius: 0; height: 100vh; }
    .modal-header { border-radius: 0; padding: 14px 16px; }
    .modal-header h3 { font-size: 16px; }
    .modal-body { padding: 16px; }
    .items-list { padding: 12px; font-size: 13px; }
    .money-total { font-size: 20px; padding: 12px; }
    /* MOBILE: stack modal footer buttons vertically */
    .modal-footer { flex-direction: column-reverse; padding: 14px 16px; border-radius: 0; }
    .modal-footer .btn-deliver,
    .modal-footer .btn-cancel { flex: unset; width: 100%; }
    .btn-deliver { font-size: 16px; padding: 14px; }
    .btn-cancel { font-size: 14px; padding: 12px; }
}
</style>

<div class="orders-grid" id="received-orders-grid"></div>
<div id="received-empty-state" style="display:none;text-align:center;padding:50px;color:var(--text-muted)">
    <h3 style="font-size:24px;color:var(--text-dark)">لا يوجد طلبات مستلمة حالياً</h3>
    <p>قم بقبول طلبات من صفحة "طلبات جديدة"</p>
</div>

<!-- Modal Container -->
<div class="modal-overlay" id="order-details-modal">
    <div class="modal-content" id="order-modal-content"></div>
</div>

<script>
var myName = "{{ auth()->user()->name }}";
var myPhone = "{{ auth()->user()->phone }}";
var cachedOrders = [];

function fetchReceivedOrders() {
    if (!isShiftActive) return;
    axios.get('{{ route("delivery.orders.received-data") }}').then(res => {
        cachedOrders = res.data.orders;
        renderReceivedOrders();
    });
}

function renderReceivedOrders() {
    var grid = document.getElementById('received-orders-grid'); 
    var empty = document.getElementById('received-empty-state');
    if (!grid || !empty) return;
    grid.innerHTML = '';
    
    if (!cachedOrders || !cachedOrders.length) { 
        empty.style.display = 'block'; 
        return; 
    }
    empty.style.display = 'none';
    
    cachedOrders.forEach(order => {
        var clientName = order.client?.name ?? 'غير محدد';
        var clientPhone = order.client?.phone ?? '';
        var minutesAgo = order.accepted_at ? Math.floor((new Date() - new Date(order.accepted_at)) / 60000) : 0;
        
        var clientInfoHtml = '';
        if (order.send_to_phone) {
            clientInfoHtml = `
                <div class="two-party-info">
                    <div class="party sender">
                        <div class="party-label">العميل المالك</div>
                        <div class="party-row" style="gap:5px; margin-bottom:2px"><span class="icon" style="font-size:14px">👤</span> <strong>${clientName}</strong></div>
                        <div class="party-row" style="gap:5px; margin-bottom:2px"><span class="icon" style="font-size:14px">📞</span> <a href="tel:${clientPhone}" onclick="event.stopPropagation()">${clientPhone}</a></div>
                        <div class="party-row" style="gap:5px; color:var(--text-muted); font-size:13px"><span class="icon" style="font-size:14px">📍</span> <span>${order.client_address || 'بدون عنوان'}</span></div>
                    </div>
                    <div class="party-divider">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="arrow-icon"><path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                    </div>
                    <div class="party receiver">
                        <div class="party-label" style="color:#059669">العميل المستلم (وجهة التوصيل)</div>
                        <div class="party-row" style="gap:5px; margin-bottom:2px"><span class="icon" style="font-size:14px">📞</span> <a href="tel:${order.send_to_phone}" onclick="event.stopPropagation()">${order.send_to_phone}</a></div>
                        <div class="party-row" style="gap:5px"><span class="icon" style="font-size:14px">📍</span> <strong>${order.send_to_address || 'بدون عنوان'}</strong></div>
                    </div>
                </div>
            `;
        } else {
            clientInfoHtml = `
                <div class="single-party-info">
                    <div class="party-row"><span class="icon">👤</span> <strong>${clientName}</strong></div>
                    <div class="party-row"><span class="icon">📞</span> <a href="tel:${clientPhone}" class="phone-link" onclick="event.stopPropagation()">${clientPhone}</a></div>
                    <div class="party-row"><span class="icon">📍</span> <span>${order.client_address || 'لم يتم تحديده'}</span></div>
                </div>
            `;
        }
        
        var card = document.createElement('div');
        card.className = 'order-card';
        card.innerHTML = `
            <div class="order-header">
                <div class="order-number">#${order.order_number}</div>
                <div class="time-badge">منذ: ${minutesAgo} دقيقة</div>
            </div>
            ${clientInfoHtml}
            <div class="total-row">
                <div class="total-label">الإجمالي المطلوب</div>
                <div class="total-amount">${order.total} ج</div>
            </div>
            <button class="btn-view" onclick="openOrderModal(${order.id})">📋 عرض تفاصيل الطلب</button>
        `;
        grid.appendChild(card);
    });
}

function openOrderModal(orderId) {
    const order = cachedOrders.find(o => o.id === orderId);
    if (!order) return;
    
    var clientName = order.client?.name ?? 'غير محدد';
    var clientPhone = order.client?.phone ?? '';
    var phoneHtml = `<a href="tel:${clientPhone}" class="phone-link">📞 ${clientPhone}</a>`;
    if (order.client?.phone_secondary) {
        phoneHtml += ` | <a href="tel:${order.client.phone_secondary}" class="phone-link">📞 ${order.client.phone_secondary}</a>`;
    }
    
    var clientSectionHtml = '';
    if (order.send_to_phone) {
        clientSectionHtml = `
            <div class="two-party-info" style="margin-bottom:20px; font-size:15px">
                <div class="party sender">
                    <div class="party-label" style="font-size:13px">العميل المالك (المرسل)</div>
                    <div class="party-row" style="gap:8px; margin-bottom:5px"><span class="icon">👤</span> <strong>${clientName}</strong></div>
                    <div class="party-row" style="gap:8px; margin-bottom:5px"><span class="icon">📞</span> ${phoneHtml}</div>
                    <div class="party-row" style="gap:8px; color:var(--text-muted)"><span class="icon">📍</span> <span>${order.client_address || 'بدون عنوان'}</span></div>
                </div>
                <div class="party-divider" style="margin:10px 0">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="arrow-icon" style="width:24px;height:24px"><path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                </div>
                <div class="party receiver" style="padding:15px">
                    <div class="party-label" style="color:#059669; font-size:13px">العميل المستلم (وجهة التوصيل النهائية)</div>
                    <div class="party-row" style="gap:8px; margin-bottom:5px"><span class="icon">📞</span> <a href="tel:${order.send_to_phone}" style="font-size:16px">${order.send_to_phone}</a></div>
                    <div class="party-row" style="gap:8px"><span class="icon">📍</span> <strong style="font-size:16px">${order.send_to_address || 'بدون عنوان'}</strong></div>
                </div>
            </div>
        `;
    } else {
        clientSectionHtml = `
            <div class="single-party-info" style="background:#f8fafc; padding:15px; border-radius:10px; border:1px solid #e2e8f0; margin-bottom:20px">
                <div class="party-row" style="margin-bottom:8px"><span class="icon">👤</span> <strong style="font-size:16px">${clientName}</strong></div>
                <div class="party-row" style="margin-bottom:8px"><span class="icon">📞</span> ${phoneHtml}</div>
                <div class="party-row"><span class="icon">📍</span> <span style="font-size:15px">${order.client_address || 'لم يتم تحديده'}</span></div>
            </div>
        `;
    }
    
    var itemsHtml = '';
    if (order.items && order.items.length > 0) {
        var rows = order.items.map(i => {
            var unitPrice = i.unit_price ? parseFloat(i.unit_price) : 0;
            var totalPrice = i.total ? parseFloat(i.total) : (unitPrice * i.quantity);
            return `
            <div class="item-row">
                <div class="item-main">
                    <div class="item-qty">${i.quantity}×</div>
                    <div class="item-details">
                        <div class="item-name">${i.item_name}</div>
                        <div class="item-shop">🏪 ${i.shop?.name ?? 'بدون متجر'}</div>
                    </div>
                </div>
                <div class="item-pricing">
                    <div class="item-total">${totalPrice} ج</div>
                    <div class="item-unit">للوحدة: ${unitPrice} ج</div>
                </div>
            </div>
        `}).join('');
        itemsHtml = `
            <div class="items-list-container">
                <div class="items-list-header">🛒 قائمة المنتجات (${order.items.length})</div>
                <div class="items-list-body">${rows}</div>
            </div>
        `;
    } else {
        itemsHtml = `
            <div class="items-list-container">
                <div class="items-list-header">🛒 قائمة المنتجات (0)</div>
                <div class="items-list-body"><div style="text-align:center; padding:10px; color:var(--text-muted);">لا توجد أصناف</div></div>
            </div>
        `;
    }

    const modalContent = document.getElementById('order-modal-content');
    modalContent.innerHTML = `
        <div class="modal-header">
            <h3>تفاصيل الطلب #${order.order_number}</h3>
            <button class="btn-close-modal" onclick="closeOrderModal()">✕</button>
        </div>
        <div class="modal-body">
            ${clientSectionHtml}
            <div class="info-group"><div class="info-label">ملاحظات الطلب</div><div class="info-value" style="color:var(--secondary);font-size:15px;background:#fffbeb;padding:12px;border-radius:8px;border:1px dashed #fcd34d">${order.notes || '- لا توجد ملاحظات -'}</div></div>
            ${itemsHtml}
            <div class="money-total">
                المطلوب تحصيله: ${order.total} ج
                <div style="font-size:13px;color:var(--text-muted);font-weight:600;margin-top:8px;">( يشمل توصيل: ${order.delivery_fee} ج | خصم: ${order.discount} ج )</div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel" onclick="cancelOrder(${order.id})">إلغاء الطلب</button>
            <button class="btn-deliver" onclick="markDelivered(${order.id}, '${order.order_number}')">✔ تم التوصيل بنجاح</button>
        </div>
    `;
    
    document.getElementById('order-details-modal').classList.add('open');
}

function closeOrderModal() {
    document.getElementById('order-details-modal').classList.remove('open');
}

document.getElementById('order-details-modal').addEventListener('click', function(e) {
    if (e.target === this) closeOrderModal();
});

function markDelivered(id, orderNumber) {
    Swal.fire({ title: 'تأكيد التوصيل', text: 'هل تم تحصيل المبلغ وتوصيل الطلب بنجاح؟', icon: 'question', showCancelButton: true, confirmButtonText: 'نعم، تم بنجاح', cancelButtonText: 'تراجع', confirmButtonColor: '#10b981' }).then(result => {
        if (!result.isConfirmed) return;
        closeOrderModal();
        axios.post('/delivery/orders/'+id+'/deliver').then(res => {
            if (res.data.success) {
                Swal.fire({ title: 'تم التوصيل بنجاح ✅', icon: 'success', confirmButtonText: 'حسناً' }).then(() => fetchReceivedOrders());
            } else { Swal.fire('خطأ', res.data.message, 'error'); }
        });
    });
}

function cancelOrder(id) {
    Swal.fire({ title: 'إلغاء الطلب', input: 'text', inputLabel: 'يرجى كتابة سبب الإلغاء:', inputPlaceholder: 'مثال: العميل لا يرد', showCancelButton: true, confirmButtonText: 'تأكيد الإلغاء', cancelButtonText: 'تراجع', confirmButtonColor: '#dc2626', preConfirm: (r) => { if (!r) Swal.showValidationMessage('يجب كتابة سبب الإلغاء'); return r; } }).then(result => {
        if (!result.isConfirmed) return;
        closeOrderModal();
        axios.post('/delivery/orders/'+id+'/cancel', { reason: result.value }).then(res => {
            if (res.data.success) { Swal.fire('تم الإلغاء', '', 'success'); fetchReceivedOrders(); }
            else { Swal.fire('خطأ', res.data.message, 'error'); }
        });
    });
}

function onShiftStarted() { fetchReceivedOrders(); }
setTimeout(() => { if (isShiftActive) fetchReceivedOrders(); }, 500);
if (typeof addPolling === 'function') addPolling(setInterval(fetchReceivedOrders, 20000));
else setInterval(fetchReceivedOrders, 20000);
</script>
