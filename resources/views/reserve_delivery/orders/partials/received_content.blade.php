{{-- Reserve Delivery Received Orders SPA partial --}}
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
.items-list { background:#f9fafb; padding:15px; border-radius:8px; margin-bottom:20px; font-size:14px; border:1px solid var(--border-color); }
.items-list strong { display:block; margin-bottom:10px; font-size:15px; color:var(--text-dark); }
.money-total { font-size:26px; font-weight:800; color:var(--success); text-align:center; padding:15px; background:#ecfdf5; border-radius:8px; border:1px dashed var(--success); margin-bottom:20px; }
.modal-footer { padding:20px; border-top:1px solid var(--border-color); display:flex; gap:10px; background:white; position:sticky; bottom:0; border-radius:0 0 16px 16px; z-index:10; }
.btn-deliver { flex:2; padding:14px; background-color:var(--success); color:white; border:none; border-radius:8px; font-size:18px; font-weight:700; cursor:pointer; justify-content:center; display:flex; align-items:center; gap:8px; transition:0.3s; }
.btn-deliver:hover { background-color:#059669; }
.btn-cancel { flex:1; padding:12px; background-color:white; color:var(--secondary); border:1px solid var(--secondary); border-radius:8px; font-size:16px; font-weight:700; cursor:pointer; transition:0.3s; }
.btn-cancel:hover { background-color:#fee2e2; }
</style>

<div class="orders-grid" id="received-reserve-orders-grid"></div>
<div id="received-reserve-empty-state" style="display:none;text-align:center;padding:50px;color:var(--text-muted)">
    <h3 style="font-size:24px;color:var(--text-dark)">لا يوجد طلبات مستلمة حالياً</h3>
    <p>قم بقبول طلبات من صفحة "طلبات جديدة"</p>
</div>

<!-- Modal Container -->
<div class="modal-overlay" id="reserve-order-modal">
    <div class="modal-content" id="reserve-modal-content"></div>
</div>

<script>
var myNameR = "{{ auth()->user()->name }}";
var myPhoneR = "{{ auth()->user()->phone }}";
var cachedReserveOrders = [];

function fetchReceivedOrders() {
    if (!isShiftActive) return;
    axios.get('{{ route("reserve.orders.received-data") }}').then(res => {
        cachedReserveOrders = res.data.orders;
        renderReceivedOrders();
    });
}

function renderReceivedOrders() {
    var grid = document.getElementById('received-reserve-orders-grid'); 
    var empty = document.getElementById('received-reserve-empty-state');
    if (!grid || !empty) return;
    grid.innerHTML = '';
    
    if (!cachedReserveOrders || !cachedReserveOrders.length) { 
        empty.style.display = 'block'; 
        return; 
    }
    empty.style.display = 'none';
    
    cachedReserveOrders.forEach(order => {
        var clientName = order.client?.name ?? 'غير محدد';
        var clientPhone = order.client?.phone ?? '';
        var minutesAgo = order.accepted_at ? Math.floor((new Date() - new Date(order.accepted_at)) / 60000) : 0;
        
        var deliveryAddress = order.client_address || 'لم يتم تحديده';
        if (order.send_to_phone) {
            deliveryAddress = `
                <div style="margin-bottom:6px;"><span style="color:var(--text-muted);font-size:12px">عنوان المالك:</span><br>${order.client_address || ''}</div>
                <div style="color:var(--secondary);font-weight:bold;border-top:1px dashed var(--border-color);padding-top:6px;font-size:14px;">
                    التوصيل لـ: ${order.send_to_phone} <br> ${order.send_to_address || ''}
                </div>`;
        }
        
        var card = document.createElement('div');
        card.className = 'order-card';
        card.innerHTML = `
            <div class="order-header">
                <div class="order-number">#${order.order_number}</div>
                <div class="time-badge">منذ: ${minutesAgo} دقيقة</div>
            </div>
            <div class="info-group">
                <div class="info-label">العميل</div>
                <div class="info-value">${clientName}</div>
                <div class="info-value" style="margin-top:5px; font-size:14px;"><a href="tel:${clientPhone}" class="phone-link" onclick="event.stopPropagation()">📞 ${clientPhone}</a></div>
            </div>
            <div class="info-group">
                <div class="info-label">عنوان التوصيل</div>
                <div class="info-value" style="line-height:1.5; font-size:15px;">${deliveryAddress}</div>
            </div>
            <div class="info-group">
                <div class="info-label">الإجمالي النهائي</div>
                <div class="info-value" style="color:var(--success); font-size:20px;">${order.total} ج</div>
            </div>
            <button class="btn-view" onclick="openReserveModal(${order.id})">📋 عرض الطلب بالكامل</button>
        `;
        grid.appendChild(card);
    });
}

function openReserveModal(orderId) {
    const order = cachedReserveOrders.find(o => o.id === orderId);
    if (!order) return;
    
    var clientName = order.client?.name ?? 'غير محدد';
    var clientPhone = order.client?.phone ?? '';
    var phoneHtml = `<a href="tel:${clientPhone}" class="phone-link">📞 ${clientPhone}</a>`;
    if (order.client?.phone_secondary) {
        phoneHtml += ` | <a href="tel:${order.client.phone_secondary}" class="phone-link">📞 ${order.client.phone_secondary}</a>`;
    }
    
    var deliveryAddressHtml = order.client_address || 'غير محدد';
    if (order.send_to_phone) {
        deliveryAddressHtml = `
            <div style="margin-bottom:8px;"><span style="color:var(--text-muted);font-size:12px">العنوان الأساسي:</span><br>${order.client_address || ''}</div>
            <div style="background:#fee2e2; border-radius:6px; padding:10px; border:1px solid #fca5a5;">
                <div style="color:var(--secondary);font-weight:bold;margin-bottom:5px">وجهة التوصيل لعميل أخر: ${order.send_to_phone}</div>
                <div style="font-size:15px">${order.send_to_address || ''}</div>
            </div>`;
    }
    
    var itemsHtml = order.items?.length 
        ? order.items.map(i => `<div style="margin-bottom:6px;">- ${i.item_name} (×${i.quantity}) &mdash; <small style="color:var(--primary)">${i.shop?.name ?? ''}</small></div>`).join('') 
        : 'لا توجد أصناف';

    const modalContent = document.getElementById('reserve-modal-content');
    modalContent.innerHTML = `
        <div class="modal-header">
            <h3>تفاصيل الطلب #${order.order_number}</h3>
            <button class="btn-close-modal" onclick="closeReserveModal()">✕</button>
        </div>
        <div class="modal-body">
            <div class="info-group"><div class="info-label">بيانات العميل</div><div class="info-value">${clientName}</div><div class="info-value" style="margin-top:5px">${phoneHtml}</div></div>
            <div class="info-group"><div class="info-label">وجهة التوصيل</div><div class="info-value" style="line-height:1.5">${deliveryAddressHtml}</div></div>
            <div class="info-group"><div class="info-label">ملاحظات الطلب</div><div class="info-value" style="color:var(--secondary);font-size:14px">${order.notes || '- لا توجد -'}</div></div>
            <div class="items-list"><strong>المنتجات:</strong>${itemsHtml}</div>
            <div class="money-total">
                المطلوب تحصيله: ${order.total} ج
                <div style="font-size:13px;color:var(--text-muted);font-weight:600;margin-top:8px;">( يشمل توصيل: ${order.delivery_fee} ج | خصم: ${order.discount} ج )</div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel" onclick="cancelReserveOrder(${order.id})">إلغاء الطلب</button>
            <button class="btn-deliver" onclick="markReserveDelivered(${order.id}, '${order.order_number}')">✔ تم التوصيل بنجاح</button>
        </div>
    `;
    
    document.getElementById('reserve-order-modal').classList.add('open');
}

function closeReserveModal() {
    document.getElementById('reserve-order-modal').classList.remove('open');
}

document.getElementById('reserve-order-modal').addEventListener('click', function(e) {
    if (e.target === this) closeReserveModal();
});

function markReserveDelivered(id, orderNumber) {
    Swal.fire({ title: 'تأكيد التوصيل', text: 'هل تم تحصيل المبلغ وتوصيل الطلب بنجاح؟', icon: 'question', showCancelButton: true, confirmButtonText: 'نعم', cancelButtonText: 'تراجع', confirmButtonColor: '#10b981' }).then(result => {
        if (!result.isConfirmed) return;
        closeReserveModal();
        axios.post('/reserve/orders/'+id+'/deliver').then(res => {
            if (res.data.success) { Swal.fire({ title: 'تم التوصيل ✅', icon: 'success', confirmButtonText: 'حسناً' }).then(() => fetchReceivedOrders()); }
            else { Swal.fire('خطأ', res.data.message, 'error'); }
        });
    });
}

function cancelReserveOrder(id) {
    Swal.fire({ title: 'إلغاء الطلب', input: 'text', inputLabel: 'سبب الإلغاء:', inputPlaceholder: 'مثال: العميل لا يرد', showCancelButton: true, confirmButtonText: 'تأكيد', cancelButtonText: 'تراجع', confirmButtonColor: '#dc2626', preConfirm: r => { if (!r) Swal.showValidationMessage('يجب كتابة سبب'); return r; } }).then(result => {
        if (!result.isConfirmed) return;
        closeReserveModal();
        axios.post('/reserve/orders/'+id+'/cancel', { reason: result.value }).then(res => {
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
