{{-- Delivery New Orders SPA partial --}}
<style>
.top-info-bar { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; background:white; padding:15px 20px; border-radius:12px; border:1px solid var(--border-color); box-shadow:0 2px 4px rgba(0,0,0,0.05); }
.orders-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:20px; }
.order-card { background:white; border-radius:12px; padding:20px; border:1px solid var(--border-color); box-shadow:0 2px 4px rgba(0,0,0,0.05); display:flex; flex-direction:column; position:relative; }
.order-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; }
.order-number { font-size:24px; font-weight:800; color:var(--text-dark); }
.hidden-zone { background-color:#f9fafb; border-radius:8px; padding:15px; margin-bottom:20px; position:relative; overflow:hidden; }
.hidden-content { filter:blur(5px); user-select:none; opacity:0.5; }
.blur-overlay { position:absolute; top:0;left:0;right:0;bottom:0; display:flex; flex-direction:column; justify-content:center; align-items:center; z-index:2; }
.btn-accept { width:100%; padding:12px; background-color:var(--success); color:white; border:none; border-radius:8px; font-size:16px; font-weight:700; cursor:pointer; transition:0.3s; }
.btn-accept:hover { background-color:#059669; }
.btn-accept:disabled { background-color:#9ca3af; cursor:not-allowed; }
.empty-state { text-align:center; padding:50px 20px; color:var(--text-muted); }

/* MOBILE: responsive new orders grid */
@media (max-width: 768px) {
    /* MOBILE: narrower card minimum for phone screens */
    .orders-grid { grid-template-columns: 1fr; gap: 12px; }
    .order-card { padding: 16px; }
    .order-number { font-size: 20px; }
    .top-info-bar { padding: 12px 14px; flex-wrap: wrap; gap: 8px; }
    .empty-state { padding: 30px 16px; }
    .empty-state h3 { font-size: 18px; }
    .btn-accept { padding: 14px; font-size: 15px; }
}
</style>

<div class="orders-grid" id="new-orders-grid"></div>
<div class="empty-state" id="new-empty-state" style="display:none;">
    <h3 style="font-size:24px;color:var(--text-muted)">لا توجد طلبات جديدة الآن</h3>
    <p>سيتم إعلامك تلقائياً عند وجود طلب جديد</p>
</div>

<script>
var currentOrders = [];
var currentOrders = [];

function fetchNewOrders() {
    if (!isShiftActive) return;
    axios.get('{{ route("delivery.orders.new-data") }}').then(res => {
        currentOrders = res.data.orders;
        updateDashboardCapacity();
        renderNewOrders();
    });
}

function updateDashboardCapacity() {
    // Capacity tracking removed as per user request
}

function renderNewOrders() {
    var grid = document.getElementById('new-orders-grid');
    var empty = document.getElementById('new-empty-state');
    if (!grid) return;
    grid.innerHTML = '';
    if (!currentOrders || !currentOrders.length) { empty.style.display = 'block'; return; }
    empty.style.display = 'none';
    currentOrders.forEach(order => {
        var itemsCount = order.items ? order.items.reduce((s,i) => s + i.quantity, 0) : 0;
        var card = document.createElement('div');
        card.className = 'order-card'; card.id = `order-${order.id}`;
        card.innerHTML = `
            <div class="order-header">
                <div class="order-number">#${order.order_number}</div>
                <div style="font-size:14px;color:var(--text-muted);font-weight:bold" class="order-timer" data-time="${order.sent_to_delivery_at || order.created_at}">00:00</div>
            </div>
            <div class="hidden-zone">
                <div class="blur-overlay"><span>🔒</span><span>تفاصيل العميل مخفية</span></div>
                <div class="hidden-content"><strong>الاسم:</strong> ${order.client?.name ?? ''}<br><strong>الهاتف:</strong> ${order.client?.phone ?? ''}<br><strong>العنوان:</strong> ${order.client_address ?? ''}</div>
            </div>
            <button class="btn-accept" onclick="acceptOrder(${order.id})">✔ قبول الطلب</button>
        `;
        grid.appendChild(card);
    });
}

function acceptOrder(id) {
    Swal.fire({ title: 'تأكيد', text: 'هل أنت متأكد من قبول هذا الطلب؟', icon: 'question', showCancelButton: true, confirmButtonText: 'نعم، أقبل', cancelButtonText: 'إلغاء' }).then(result => {
        if (!result.isConfirmed) return;
        axios.post(`/delivery/orders/${id}/accept`).then(res => {
            if (res.data.success) {
                Swal.fire({ icon: 'success', title: 'تم قبول الطلب', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
                currentOrders = currentOrders.filter(o => o.id !== id);
                renderNewOrders(); updateDashboardCapacity(); checkNewOrdersBadge();
            } else { Swal.fire('خطأ', res.data.message, 'error'); fetchNewOrders(); }
        }).catch(() => { Swal.fire('خطأ', 'حدث خطأ في النظام', 'error'); });
    });
}

function onShiftStarted() { fetchNewOrders(); }
setTimeout(() => { if (isShiftActive) fetchNewOrders(); }, 500);

if (typeof addPolling === 'function') addPolling(setInterval(fetchNewOrders, 15000));
else setInterval(fetchNewOrders, 15000);

if (typeof window.Echo !== 'undefined') {
    window.Echo.channel('orders').listen('OrderStatusUpdated', (e) => {
        var order = e.message;
        if (order && order.status === 'pending' && !order.delivery_id && !currentOrders.find(o => o.id === order.order_id)) fetchNewOrders();
        else if (order && order.delivery_id && order.delivery_id !== window.myDeliveryId) { currentOrders = currentOrders.filter(o => o.id !== order.order_id); renderNewOrders(); }
    });
}

function formatWaitTime(dateStr) {
    if (!dateStr) return '00:00';
    var diffSecs = Math.floor((new Date() - new Date(dateStr)) / 1000);
    if (diffSecs < 0) diffSecs = 0;
    var hours = Math.floor(diffSecs / 3600);
    var mins = Math.floor((diffSecs % 3600) / 60);
    var secs = diffSecs % 60;
    if (hours > 0) {
        return String(hours).padStart(2, '0') + ':' + String(mins).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
    }
    return String(mins).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
}

setInterval(() => {
    document.querySelectorAll('.order-timer').forEach(el => {
        var t = el.getAttribute('data-time');
        if (t) el.innerText = formatWaitTime(t);
    });
}, 1000);
</script>
