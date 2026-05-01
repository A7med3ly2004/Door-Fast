{{-- Reserve Delivery Delivered Orders SPA partial --}}

<style>
/* Base Modal Styles */
.modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:1000; display:none; align-items:center; justify-content:center; padding:20px; }
.modal-overlay.open { display:flex; }
.modal-content { background:white; width:100%; max-width:600px; max-height:90vh; overflow-y:auto; border-radius:16px; position:relative; display:flex; flex-direction:column; box-shadow:0 10px 25px rgba(0,0,0,0.2); animation:modalIn 0.3s ease; }
@keyframes modalIn { from{ opacity:0; transform:translateY(-20px); } to{ opacity:1; transform:translateY(0); } }
.modal-header { padding:20px; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center; position:sticky; top:0; background:white; z-index:10; border-radius:16px 16px 0 0; }
.modal-header h3 { font-size:20px; font-weight:800; color:var(--text-dark); margin:0; }
.btn-close-modal { background:none; border:none; font-size:24px; cursor:pointer; color:var(--text-muted); transition:0.2s; }
.modal-body { padding:20px; flex:1; }
.modal-footer { padding:20px; border-top:1px solid var(--border-color); display:flex; gap:10px; background:white; position:sticky; bottom:0; border-radius:0 0 16px 16px; z-index:10; }

/* Client Info & Items CSS */
.two-party-info { display:flex; flex-direction:column; gap:8px; background:#f8fafc; border-radius:10px; padding:12px; margin-bottom:15px; border:1px solid #e2e8f0; }
.party-label { font-size:12px; color:var(--text-muted); margin-bottom:4px; font-weight:600; }
.party { display:flex; flex-direction:column; gap:4px; font-size:14px; }
.party.sender { color:#475569; }
.party.receiver { color:var(--text-dark); background:#ecfdf5; padding:12px; border-radius:8px; border:1px dashed #34d399; margin-top:4px; }
.party a { color:#2563eb; text-decoration:none; font-weight:600; direction:ltr; display:inline-block; }
.party-divider { display:flex; justify-content:center; color:#94a3b8; margin:10px 0; }
.single-party-info { display:flex; flex-direction:column; gap:10px; margin-bottom:15px; background:#f8fafc; padding:15px; border-radius:10px; border:1px solid #e2e8f0; }
.party-row { display:flex; align-items:flex-start; gap:8px; font-size:14.5px; color:var(--text-dark); line-height:1.4; }

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

/* Desktop Elements */
.desktop-only { display: block; }
.mobile-only { display: none !important; }

/* Mobile Cards List */
.delivered-mobile-list { flex-direction: column; gap: 12px; }
.delivered-mobile-card { background: white; border: 1px solid var(--border-color); border-radius: 12px; padding: 16px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
.dmc-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 10px; }
.dmc-order-num { font-size: 18px; font-weight: 800; color: var(--primary); }
.dmc-total { font-size: 16px; font-weight: 800; color: var(--success); background: #ecfdf5; padding: 4px 10px; border-radius: 20px; }
.dmc-client-row { display: flex; align-items: center; gap: 8px; font-size: 15px; margin-bottom: 8px; color: var(--text-dark); }
.dmc-client-row .icon { font-size: 16px; }
.btn-dmc-view { width: 100%; padding: 12px; background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; border-radius: 8px; font-weight: 700; font-size: 15px; margin-top: 10px; cursor: pointer; transition: 0.2s; }
.btn-dmc-view:hover { background: #e2e8f0; }

.btn-invoice { flex: 1; padding: 14px; background-color: #25d366; color: white; border: none; border-radius: 8px; font-size: 15px; font-weight: 700; cursor: pointer; display: flex; justify-content: center; align-items: center; gap: 6px; transition: 0.3s; }
.btn-invoice:hover { background-color: #128c7e; }

.btn-chat { flex: 1; padding: 14px; background-color: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 15px; font-weight: 700; cursor: pointer; display: flex; justify-content: center; align-items: center; gap: 6px; transition: 0.3s; }
.btn-chat:hover { background-color: #e2e8f0; }

/* MOBILE RESPONSIVE */
@media (max-width: 768px) {
    .summary-bar { flex-direction: column !important; gap: 12px !important; padding: 14px !important; margin-bottom: 16px !important; }
    .summary-bar > div { display: flex !important; justify-content: space-between !important; align-items: center !important; text-align: right !important; padding: 6px 0 !important; border-bottom: 1px solid var(--border-color) !important; }
    .summary-bar > div:last-child { border-bottom: none !important; }
    
    .desktop-only { display: none !important; }
    .mobile-only { display: flex !important; }

    .modal-overlay { padding: 16px; align-items: flex-end; }
    .modal-content { max-width: 100%; max-height: 85vh; border-radius: 16px; height: auto; }
    .modal-header { border-radius: 16px 16px 0 0; padding: 14px 16px; }
    .modal-header h3 { font-size: 16px; }
    .modal-body { padding: 16px; }
    .modal-footer { padding: 14px 16px; border-radius: 0 0 16px 16px; }
}
</style>

<div class="summary-bar" style="display:flex;justify-content:space-around;background:white;padding:20px;border-radius:12px;border:1px solid var(--border-color);box-shadow:0 2px 4px rgba(0,0,0,0.05);margin-bottom:25px">
    <div style="text-align:center"><div style="color:var(--text-muted);font-size:14px;font-weight:600;margin-bottom:5px">إجمالي الطلبات الموصلة</div><div style="font-size:24px;font-weight:800;color:var(--primary)" id="sum-count">0</div></div>
    <div style="text-align:center"><div style="color:var(--text-muted);font-size:14px;font-weight:600;margin-bottom:5px">إجمالي التحصيل</div><div style="font-size:24px;font-weight:800;color:var(--success)" id="sum-total">0 ج</div></div>
    <div style="text-align:center"><div style="color:var(--text-muted);font-size:14px;font-weight:600;margin-bottom:5px">إجمالي رسوم التوصيل</div><div style="font-size:24px;font-weight:800;color:var(--success)" id="sum-fees">0 ج</div></div>
</div>

<div class="desktop-only" style="background:white;border-radius:12px;border:1px solid var(--border-color);overflow:hidden;box-shadow:0 2px 4px rgba(0,0,0,0.05)">
    <table style="width:100%;border-collapse:collapse;text-align:right">
        <thead><tr style="background:#f9fafb">
            <th style="padding:15px;font-size:14px;color:var(--text-muted);font-weight:700;border-bottom:1px solid var(--border-color)">رقم الطلب</th>
            <th style="padding:15px;font-size:14px;color:var(--text-muted);font-weight:700;border-bottom:1px solid var(--border-color)">العميل</th>
            <th style="padding:15px;font-size:14px;color:var(--text-muted);font-weight:700;border-bottom:1px solid var(--border-color)">العنوان</th>
            <th style="padding:15px;font-size:14px;color:var(--text-muted);font-weight:700;border-bottom:1px solid var(--border-color)">الإجمالي</th>
            <th style="padding:15px;font-size:14px;color:var(--text-muted);font-weight:700;border-bottom:1px solid var(--border-color)">التوصيل</th>
            <th style="padding:15px;font-size:14px;color:var(--text-muted);font-weight:700;border-bottom:1px solid var(--border-color)">إجراءات</th>
        </tr></thead>
        <tbody id="delivered-table-body"></tbody>
    </table>
</div>

<div class="mobile-only delivered-mobile-list" id="delivered-mobile-list"></div>

<div id="delivered-empty-state" style="display:none;text-align:center;padding:30px;color:var(--text-muted);font-weight:600">لا توجد طلبات موصلة اليوم حتى الآن</div>

<!-- Modal Container -->
<div class="modal-overlay" id="delivered-details-modal">
    <div class="modal-content" id="delivered-modal-content"></div>
</div>

<script>
var cachedDeliveredOrders = [];

function fetchDeliveredOrders() {
    if (!isShiftActive) return;
    axios.get('{{ route("reserve.orders.delivered-data") }}').then(res => {
        cachedDeliveredOrders = res.data.orders;
        renderData();
    });
}

function renderData() {
    var sumTotal = 0, sumFees = 0;
    var tbody = document.getElementById('delivered-table-body');
    var mobileList = document.getElementById('delivered-mobile-list');
    var empty = document.getElementById('delivered-empty-state');
    
    if (!tbody || !mobileList || !empty) return;
    
    tbody.innerHTML = '';
    mobileList.innerHTML = '';
    
    if (!cachedDeliveredOrders.length) { 
        empty.style.display = 'block'; 
        document.querySelector('.desktop-only').style.display = 'none';
    } else { 
        empty.style.display = 'none'; 
        document.querySelector('.desktop-only').style.display = 'block';
    }
    
    cachedDeliveredOrders.forEach(order => {
        sumTotal += parseFloat(order.total || 0);
        sumFees += parseFloat(order.delivery_fee || 0);
        var clientName = order.client?.name ?? 'غير محدد';
        var clientPhone = order.client?.phone ?? '';
        
        // Desktop Row
        var tr = document.createElement('tr');
        tr.innerHTML = `
            <td style="padding:15px;border-bottom:1px solid var(--border-color)"><span style="color:var(--primary);font-weight:800">#${order.order_number}</span></td>
            <td style="padding:15px;border-bottom:1px solid var(--border-color)">${clientName}<br><small style="color:var(--text-muted)">${clientPhone}</small></td>
            <td style="padding:15px;border-bottom:1px solid var(--border-color);max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${order.client_address || 'غير محدد'}</td>
            <td style="padding:15px;border-bottom:1px solid var(--border-color)"><span style="background:#ecfdf5;color:var(--success);padding:4px 10px;border-radius:15px;font-size:12px">${order.total} ج</span></td>
            <td style="padding:15px;border-bottom:1px solid var(--border-color)">${order.delivery_fee} ج</td>
            <td style="padding:15px;border-bottom:1px solid var(--border-color)"><button class="btn-dmc-view" style="width:auto; margin:0; padding:6px 12px" onclick="openDeliveredModal(${order.id})">عرض التفاصيل</button></td>
        `;
        tbody.appendChild(tr);

        // Mobile Card
        var mCard = document.createElement('div');
        mCard.className = 'delivered-mobile-card';
        mCard.innerHTML = `
            <div class="dmc-header">
                <div class="dmc-order-num">#${order.order_number}</div>
                <div class="dmc-total">${order.total} ج</div>
            </div>
            <div class="dmc-client-row"><span class="icon">👤</span> <strong>${clientName}</strong></div>
            <div class="dmc-client-row"><span class="icon">📞</span> <span style="direction:ltr">${clientPhone}</span></div>
            <button class="btn-dmc-view" onclick="openDeliveredModal(${order.id})">📋 عرض تفاصيل الطلب</button>
        `;
        mobileList.appendChild(mCard);
    });
    
    var sumCount = document.getElementById('sum-count');
    if (sumCount) sumCount.innerText = cachedDeliveredOrders.length;
    var sumTot = document.getElementById('sum-total');
    if (sumTot) sumTot.innerText = sumTotal + ' ج';
    var sumFe = document.getElementById('sum-fees');
    if (sumFe) sumFe.innerText = sumFees + ' ج';
}

function openDeliveredModal(orderId) {
    const order = cachedDeliveredOrders.find(o => o.id === orderId);
    if (!order) return;
    
    var clientName = order.client?.name ?? 'غير محدد';
    var clientPhone = order.client?.phone ?? '';
    var phoneHtml = `<a href="tel:${clientPhone}" style="color:#2563eb;text-decoration:none;direction:ltr;display:inline-block">📞 ${clientPhone}</a>`;
    
    var clientSectionHtml = '';
    if (order.send_to_phone) {
        clientSectionHtml = `
            <div class="two-party-info" style="margin-bottom:20px; font-size:15px">
                <div class="party sender">
                    <div class="party-label" style="font-size:13px">العميل المالك (المرسل)</div>
                    <div class="party-row" style="gap:8px; margin-bottom:5px"><span class="icon">👤</span> <strong>${clientName}</strong></div>
                    <div class="party-row" style="gap:8px; margin-bottom:5px"><span class="icon">📞</span> ${phoneHtml}</div>
                </div>
                <div class="party-divider">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:24px;height:24px"><path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
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
    }

    const modalContent = document.getElementById('delivered-modal-content');
    modalContent.innerHTML = `
        <div class="modal-header">
            <h3>تفاصيل الطلب #${order.order_number}</h3>
            <button class="btn-close-modal" onclick="closeDeliveredModal()">✕</button>
        </div>
        <div class="modal-body">
            ${clientSectionHtml}
            ${itemsHtml}
            <div class="money-total">
                الإجمالي النهائي: ${order.total} ج
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-chat" onclick="openWhatsAppChat('${order.send_to_phone || clientPhone}', '${order.order_number}')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:20px;height:20px"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                محادثة العميل
            </button>
            <button class="btn-invoice" onclick="sendInvoice(${order.id}, '${order.order_number}', '${order.send_to_phone || clientPhone}', this)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:20px;height:20px"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                مشاركة الفاتورة
            </button>
        </div>
    `;
    
    document.getElementById('delivered-details-modal').classList.add('open');
}

function closeDeliveredModal() {
    document.getElementById('delivered-details-modal').classList.remove('open');
}

document.getElementById('delivered-details-modal').addEventListener('click', function(e) {
    if (e.target === this) closeDeliveredModal();
});

async function sendInvoice(orderId, orderNumber, phoneStr, btnElement) {
    var dlUrl = '/reserve/orders/' + orderId + '/invoice/download';
    
    if (btnElement) {
        btnElement.dataset.originalHtml = btnElement.innerHTML;
        btnElement.innerHTML = 'جاري التجهيز... ⏳';
        btnElement.style.pointerEvents = 'none';
        btnElement.style.opacity = '0.7';
    }

    try {
        const response = await fetch(dlUrl);
        if (!response.ok) throw new Error('Network response was not ok');
        const blob = await response.blob();
        
        const fileName = 'Invoice_ORD-' + orderNumber + '.pdf';
        const file = new File([blob], fileName, { type: 'application/pdf' });
        
        var msg = "مرحباً،\n\nإليك فاتورة طلبك رقم #" + orderNumber + " من DoorFast 📦.\n\nشكراً لثقتك بنا!";

        if (navigator.canShare && navigator.canShare({ files: [file] })) {
            await navigator.share({
                files: [file],
                title: 'فاتورة طلب #' + orderNumber,
                text: msg
            });
        } else {
            // Fallback for browsers that do not support Web Share API with files
            var link = document.createElement('a');
            link.href = window.URL.createObjectURL(blob);
            link.download = fileName;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            if (!window.isSecureContext) {
                alert('عفواً، ميزة المشاركة المباشرة تتطلب اتصالاً آمناً (HTTPS). نظراً لأنك تختبر على (HTTP)، المتصفح يمنع هذه الميزة أمنياً. لذلك تم تحميل الفاتورة في جهازك كحل بديل.');
            } else {
                alert('متصفحك لا يدعم المشاركة المباشرة للملفات. تم تحميل الفاتورة في جهازك.');
            }
        }
    } catch (error) {
        console.error('Error sharing invoice:', error);
        if (error.name === 'NotAllowedError') {
             alert('تم منع المشاركة. قد يكون السبب أن المتصفح يحتاج لضغطك المباشر بدون تأخير التحميل.');
             // Fallback
             var link = document.createElement('a');
             link.href = window.URL.createObjectURL(file);
             link.download = fileName;
             document.body.appendChild(link);
             link.click();
             document.body.removeChild(link);
        } else if (error.name !== 'AbortError') {
             alert('حدث خطأ أثناء محاولة المشاركة.');
        }
    } finally {
        if (btnElement) {
            btnElement.innerHTML = btnElement.dataset.originalHtml;
            btnElement.style.pointerEvents = 'auto';
            btnElement.style.opacity = '1';
        }
    }
}

function openWhatsAppChat(phoneStr, orderNumber) {
    var phoneNum = phoneStr.replace(/[^0-9]/g, '');
    if(phoneNum.length > 0 && !phoneNum.startsWith('20')) {
        if(phoneNum.length == 11) phoneNum = '20' + phoneNum;
    }
    var msg = "مرحباً،\nأنا مندوب DoorFast 📦 بخصوص طلبك رقم #" + orderNumber + ".";
    var encodedMsg = encodeURIComponent(msg);
    window.open("https://wa.me/" + phoneNum + "?text=" + encodedMsg, '_blank');
}

function onShiftStarted() { fetchDeliveredOrders(); }
setTimeout(() => { if (isShiftActive) fetchDeliveredOrders(); }, 500);
if (typeof addPolling === 'function') addPolling(setInterval(fetchDeliveredOrders, 30000));
else setInterval(fetchDeliveredOrders, 30000);
</script>
