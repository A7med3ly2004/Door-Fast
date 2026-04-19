{{-- Reserve Delivery Delivered Orders SPA partial --}}
<div style="display:flex;justify-content:space-around;background:white;padding:20px;border-radius:12px;border:1px solid var(--border-color);box-shadow:0 2px 4px rgba(0,0,0,0.05);margin-bottom:25px">
    <div style="text-align:center"><div style="color:var(--text-muted);font-size:14px;font-weight:600;margin-bottom:5px">إجمالي الطلبات الموصلة</div><div style="font-size:24px;font-weight:800;color:var(--primary)" id="sum-count">0</div></div>
    <div style="text-align:center"><div style="color:var(--text-muted);font-size:14px;font-weight:600;margin-bottom:5px">إجمالي التحصيل</div><div style="font-size:24px;font-weight:800;color:var(--success)" id="sum-total">0 ج</div></div>
    <div style="text-align:center"><div style="color:var(--text-muted);font-size:14px;font-weight:600;margin-bottom:5px">إجمالي رسوم التوصيل</div><div style="font-size:24px;font-weight:800;color:var(--success)" id="sum-fees">0 ج</div></div>
</div>
<div style="background:white;border-radius:12px;border:1px solid var(--border-color);overflow:hidden;box-shadow:0 2px 4px rgba(0,0,0,0.05)">
    <table style="width:100%;border-collapse:collapse;text-align:right">
        <thead><tr style="background:#f9fafb">
            <th style="padding:15px;font-size:14px;color:var(--text-muted);font-weight:700;border-bottom:1px solid var(--border-color)">رقم الطلب</th>
            <th style="padding:15px;font-size:14px;color:var(--text-muted);font-weight:700;border-bottom:1px solid var(--border-color)">العميل</th>
            <th style="padding:15px;font-size:14px;color:var(--text-muted);font-weight:700;border-bottom:1px solid var(--border-color)">العنوان</th>
            <th style="padding:15px;font-size:14px;color:var(--text-muted);font-weight:700;border-bottom:1px solid var(--border-color)">الإجمالي</th>
            <th style="padding:15px;font-size:14px;color:var(--text-muted);font-weight:700;border-bottom:1px solid var(--border-color)">التوصيل</th>
            <th style="padding:15px;font-size:14px;color:var(--text-muted);font-weight:700;border-bottom:1px solid var(--border-color)">الخصم</th>
            <th style="padding:15px;font-size:14px;color:var(--text-muted);font-weight:700;border-bottom:1px solid var(--border-color)">وقت التوصيل</th>
            <th style="padding:15px;font-size:14px;color:var(--text-muted);font-weight:700;border-bottom:1px solid var(--border-color)">مدة التوصيل</th>
        </tr></thead>
        <tbody id="table-body"></tbody>
    </table>
    <div id="empty-state" style="display:none;text-align:center;padding:30px;color:var(--text-muted);font-weight:600">لا توجد طلبات موصلة اليوم حتى الآن</div>
</div>
<script>
function fetchDeliveredOrders() {
    if (!isShiftActive) return;
    axios.get('{{ route("reserve.orders.delivered-data") }}').then(res => renderData(res.data.orders));
}
function renderData(orders) {
    var sumTotal = 0, sumFees = 0;
    var tbody = document.getElementById('table-body');
    var empty = document.getElementById('empty-state');
    if (!tbody || !empty) return;
    tbody.innerHTML = '';
    if (!orders.length) { empty.style.display = 'block'; } else { empty.style.display = 'none'; }
    orders.forEach(order => {
        sumTotal += parseFloat(order.total||0); sumFees += parseFloat(order.delivery_fee||0);
        var deliveredAt = order.delivered_at ? new Date(order.delivered_at).toLocaleTimeString('ar-EG',{hour:'2-digit',minute:'2-digit'}) : '';
        var duration = '-';
        if (order.accepted_at && order.delivered_at) duration = Math.floor((new Date(order.delivered_at)-new Date(order.accepted_at))/60000) + ' دقيقة';
        var tr = document.createElement('tr');
        tr.innerHTML = `<td style="padding:15px;border-bottom:1px solid var(--border-color)"><span style="color:var(--primary);font-weight:800">#${order.order_number}</span></td><td style="padding:15px;border-bottom:1px solid var(--border-color)">${order.client?.name??'غير محدد'}<br><small style="color:var(--text-muted)">${order.client?.phone??''}</small></td><td style="padding:15px;border-bottom:1px solid var(--border-color)">${order.client_address||'غير محدد'}</td><td style="padding:15px;border-bottom:1px solid var(--border-color)"><span style="background:#ecfdf5;color:var(--success);padding:4px 10px;border-radius:15px;font-size:12px">${order.total} ج</span></td><td style="padding:15px;border-bottom:1px solid var(--border-color)">${order.delivery_fee} ج</td><td style="padding:15px;border-bottom:1px solid var(--border-color)">${order.discount} ج</td><td style="padding:15px;border-bottom:1px solid var(--border-color)">${deliveredAt}</td><td style="padding:15px;border-bottom:1px solid var(--border-color)">${duration}</td>`;
        tbody.appendChild(tr);
    });
    var sumCount = document.getElementById('sum-count');
    if (sumCount) sumCount.innerText = orders.length;
    var sumTot = document.getElementById('sum-total');
    if (sumTot) sumTot.innerText = sumTotal + ' ج';
    var sumFe = document.getElementById('sum-fees');
    if (sumFe) sumFe.innerText = sumFees + ' ج';
}
function onShiftStarted() { fetchDeliveredOrders(); }
setTimeout(() => { if (isShiftActive) fetchDeliveredOrders(); }, 500);
if (typeof addPolling === 'function') addPolling(setInterval(fetchDeliveredOrders, 30000));
else setInterval(fetchDeliveredOrders, 30000);
</script>
