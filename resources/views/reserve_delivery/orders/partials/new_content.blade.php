{{-- Reserve Delivery New Orders SPA partial --}}
@php $reserveDelayMin = \App\Models\Setting::where('key','reserve_delay_minutes')->value('value') ?? 5; @endphp
<style>
.reserve-banner { background-color:#fef3c7; color:var(--primary); padding:12px 15px; border-radius:12px; font-weight:600; font-size:14px; margin-bottom:15px; text-align:center; border:1px solid #fde68a; }
.top-info-bar { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; background:white; padding:15px 20px; border-radius:12px; border:1px solid var(--border-color); }
.orders-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:20px; }
.order-card { background:white; border-radius:12px; padding:20px; border:1px solid var(--border-color); box-shadow:0 2px 4px rgba(0,0,0,0.05); }
.order-card.urgent { border-color:#f59e0b; box-shadow:0 0 0 2px #fef3c7; }
.order-card.very-urgent { border-color:var(--secondary); box-shadow:0 0 0 2px #fee2e2; }
.order-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; }
.order-number { font-size:24px; font-weight:800; color:var(--text-dark); }
.wait-alert { background:#fef3c7; color:var(--primary); padding:4px 10px; border-radius:15px; font-size:12px; font-weight:700; }
.hidden-zone { background-color:#f9fafb; border-radius:8px; padding:15px; margin-bottom:20px; position:relative; overflow:hidden; }
.hidden-content { filter:blur(5px); user-select:none; opacity:0.5; }
.blur-overlay { position:absolute; top:0;left:0;right:0;bottom:0; display:flex; flex-direction:column; justify-content:center; align-items:center; z-index:2; }
.btn-accept { width:100%; padding:12px; background-color:var(--success); color:white; border:none; border-radius:8px; font-size:16px; font-weight:700; cursor:pointer; transition:0.3s; }
.btn-accept:disabled { background-color:#9ca3af; cursor:not-allowed; }
.empty-state { text-align:center; padding:50px 20px; color:var(--text-muted); }
</style>

<div class="reserve-banner">الطلبات هنا لم يقبلها أي دلفري أساسي خلال <span id="delay-min-display">{{ $reserveDelayMin }}</span> دقيقة — أنت الآن أولوية التوصيل</div>
<div class="top-info-bar"></div>
<div class="orders-grid" id="new-reserve-orders-grid"></div>
<div class="empty-state" id="new-reserve-empty-state" style="display:none"><h3 style="font-size:24px;color:var(--text-dark)">لا توجد طلبات جديدة الآن</h3><p>سيتم إعلامك تلقائياً عند وجود طلب جديد لم يُقبل</p></div>

<script>
var currentOrders = [];
var currentOrders = [];
var reserveDelayMin = {{ \App\Models\Setting::where('key','reserve_delay_minutes')->value('value') ?? 5 }};

function fetchNewOrders() {
    if (!isShiftActive) return;
    axios.get('{{ route("reserve.orders.new-data") }}').then(res => { currentOrders = res.data.orders; updateDashboardCapacity(); renderNewOrders(); });
}
function updateDashboardCapacity() {
    // Capacity tracking removed as per user request
}
function renderNewOrders() {
    var grid = document.getElementById('new-reserve-orders-grid');
    var empty = document.getElementById('new-reserve-empty-state');
    if (!grid) return;
    grid.innerHTML = '';
    if (!currentOrders || !currentOrders.length) { if (empty) empty.style.display = 'block'; return; }
    if (empty) empty.style.display = 'none';
    currentOrders.forEach(order => {
        var itemsCount = order.items ? order.items.reduce((s,i) => s+i.quantity, 0) : 0;
        var waitedMins = order.sent_to_delivery_at ? Math.floor((new Date() - new Date(order.sent_to_delivery_at))/60000) : 0;
        var cardClass = 'order-card';
        var alertHtml = `<div class="wait-alert">انتظر ${waitedMins} دقيقة بدون قبول</div>`;
        if (waitedMins > reserveDelayMin+10) { cardClass += ' very-urgent'; alertHtml = `<div class="wait-alert" style="color:var(--secondary);background:#fee2e2">انتظر ${waitedMins} دقيقة ⚠️</div>`; }
        else if (waitedMins > reserveDelayMin+5) cardClass += ' urgent';
        var card = document.createElement('div'); card.className = cardClass; card.id = `order-${order.id}`;
        card.innerHTML = `<div class="order-header"><div class="order-number">#${order.order_number}</div>${alertHtml}</div><div style="font-weight:600;margin-bottom:15px;color:var(--primary)">${itemsCount} أصناف | إجمالي: ${order.total} ج</div><div class="hidden-zone"><div class="blur-overlay"><span style="font-size:18px">🔒 إخفاء</span></div><div class="hidden-content"><strong>الاسم:</strong> ${order.client?.name ?? ''}<br><strong>الهاتف:</strong> ${order.client?.phone ?? ''}</div></div><button class="btn-accept" onclick="acceptOrder(${order.id})">✔ قبول الطلب</button>`;
        grid.appendChild(card);
    });
}
function acceptOrder(id) {
    Swal.fire({ title: 'تأكيد', text: 'هل أنت متأكد من قبول الطلب؟', icon: 'question', showCancelButton: true, confirmButtonText: 'نعم', cancelButtonText: 'إلغاء' }).then(result => {
        if (!result.isConfirmed) return;
        axios.post(`/reserve/orders/${id}/accept`).then(res => {
            if (res.data.success) { Swal.fire({ icon: 'success', title: 'تم القبول', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 }); currentOrders = currentOrders.filter(o => o.id !== id); renderNewOrders(); updateDashboardCapacity(); checkNewOrdersBadge(); }
            else { Swal.fire('خطأ', res.data.message, 'error'); fetchNewOrders(); }
        });
    });
}
function onShiftStarted() { fetchNewOrders(); }
setTimeout(() => { if (isShiftActive) fetchNewOrders(); }, 500);
if (typeof addPolling === 'function') addPolling(setInterval(fetchNewOrders, 20000));
else setInterval(fetchNewOrders, 20000);
if (typeof window.Echo !== 'undefined') {
    window.Echo.channel('orders').listen('OrderStatusUpdated', (e) => {
        var order = e.message;
        if (order && order.status === 'pending' && !order.delivery_id && !currentOrders.find(o => o.id === order.order_id)) fetchNewOrders();
        else if (order && order.delivery_id && order.delivery_id !== window.myDeliveryId) { currentOrders = currentOrders.filter(o => o.id !== order.order_id); renderNewOrders(); }
    });
}
</script>
