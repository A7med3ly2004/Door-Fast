{{-- Reserve Delivery Received Orders SPA partial --}}
<style>
    .orders-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 20px;
    }

    .order-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        border: 1px solid var(--border-color);
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        display: flex;
        flex-direction: column;
    }

    .order-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        border-bottom: 1px solid var(--border-color);
        padding-bottom: 10px;
    }

    .order-number {
        font-size: 24px;
        font-weight: 800;
        color: var(--primary);
    }

    .time-badge {
        background-color: #fee2e2;
        color: var(--secondary);
        padding: 4px 10px;
        border-radius: 15px;
        font-size: 12px;
        font-weight: 700;
    }

    .info-group {
        margin-bottom: 15px;
    }

    .info-label {
        font-size: 13px;
        color: var(--text-muted);
        font-weight: 600;
        margin-bottom: 4px;
    }

    .info-value {
        font-size: 16px;
        color: var(--text-dark);
        font-weight: 700;
    }

    .phone-link {
        color: #2563eb;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        direction: ltr;
    }

    .btn-view {
        width: 100%;
        padding: 14px;
        background-color: var(--primary);
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 700;
        cursor: pointer;
        transition: 0.3s;
        margin-top: auto;
    }

    .btn-view:hover {
        background-color: #d97706;
        transform: translateY(-2px);
    }

    .two-party-info {
        display: flex;
        flex-direction: column;
        gap: 8px;
        background: #f8fafc;
        border-radius: 10px;
        padding: 12px;
        margin-bottom: 15px;
        border: 1px solid #e2e8f0;
    }

    .party-label {
        font-size: 12px;
        color: var(--text-muted);
        margin-bottom: 4px;
        font-weight: 600;
    }

    .party {
        display: flex;
        flex-direction: column;
        gap: 4px;
        font-size: 14px;
    }

    .party.sender {
        color: #475569;
    }

    .party.receiver {
        color: var(--text-dark);
        background: #ecfdf5;
        padding: 12px;
        border-radius: 8px;
        border: 1px dashed #34d399;
        margin-top: 4px;
    }

    .party a {
        color: #2563eb;
        text-decoration: none;
        font-weight: 600;
        direction: ltr;
        display: inline-block;
    }

    .party-divider {
        display: flex;
        justify-content: center;
        color: #94a3b8;
    }

    .arrow-icon {
        width: 20px;
        height: 20px;
    }

    .single-party-info {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-bottom: 15px;
        padding: 5px 0;
    }

    .party-row {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        font-size: 14.5px;
        color: var(--text-dark);
        line-height: 1.4;
        width: 100%;
    }

    .party-row .icon {
        flex-shrink: 0;
        width: 22px;
        text-align: center;
        font-size: 16px;
    }

    .party-row button {
        white-space: nowrap;
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
    }

    .total-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #f0f9ff;
        padding: 12px 15px;
        border-radius: 10px;
        margin-bottom: 15px;
        border: 1px solid #bae6fd;
    }

    .total-label {
        font-weight: 700;
        color: #0369a1;
        font-size: 15px;
    }

    .total-amount {
        font-size: 22px;
        font-weight: 800;
        color: #0284c7;
    }

    .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.6);
        z-index: 1000;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .modal-overlay.open {
        display: flex;
    }

    .modal-content {
        background: white;
        width: 100%;
        max-width: 600px;
        max-height: 90vh;
        overflow-y: auto;
        border-radius: 16px;
        position: relative;
        display: flex;
        flex-direction: column;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        animation: modalIn 0.3s ease;
    }

    @keyframes modalIn {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .modal-header {
        padding: 20px;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: sticky;
        top: 0;
        background: white;
        z-index: 10;
        border-radius: 16px 16px 0 0;
    }

    .modal-header h3 {
        font-size: 20px;
        font-weight: 800;
        color: var(--text-dark);
        margin: 0;
    }

    .btn-close-modal {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: var(--text-muted);
        transition: 0.2s;
    }

    .btn-close-modal:hover {
        color: var(--secondary);
    }

    .modal-body {
        padding: 20px;
        flex: 1;
    }

    .items-list-container {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        margin-bottom: 20px;
        overflow: hidden;
    }

    .items-list-header {
        background: #f1f5f9;
        padding: 12px 15px;
        font-weight: 700;
        color: #334155;
        font-size: 15px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .items-list-body {
        background: #ffffff;
        padding: 10px 15px;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .item-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-bottom: 10px;
        border-bottom: 1px dashed #e2e8f0;
    }

    .item-row:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .item-main {
        display: flex;
        align-items: center;
        gap: 12px;
        flex: 1;
    }

    .item-qty {
        background: #e0f2fe;
        color: #0369a1;
        font-weight: 800;
        font-size: 14px;
        padding: 4px 8px;
        border-radius: 6px;
        min-width: 35px;
        text-align: center;
        flex-shrink: 0;
    }

    .item-details {
        flex: 1;
    }

    .item-name {
        font-size: 15px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 4px;
        line-height: 1.3;
    }

    .item-shop {
        font-size: 13px;
        color: #64748b;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .item-pricing {
        text-align: left;
        flex-shrink: 0;
        margin-right: 10px;
    }

    .item-total {
        font-weight: 800;
        font-size: 15px;
        color: var(--primary);
    }

    .item-unit {
        font-size: 12px;
        color: var(--text-muted);
        margin-top: 2px;
    }

    /* Shop Group Styles */
    .shop-group {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        margin-bottom: 14px;
        overflow: hidden;
    }

    .shop-group:last-child {
        margin-bottom: 0;
    }

    .shop-group-header {
        background: linear-gradient(135deg, #f1f5f9 0%, #e8f4fd 100%);
        padding: 10px 14px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #e2e8f0;
    }

    .shop-group-name {
        font-weight: 800;
        font-size: 14px;
        color: #1e3a5f;
        display: flex;
        align-items: center;
        gap: 6px;
    }



    .shop-group-items {
        background: #ffffff;
        padding: 8px 12px;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .shop-group-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 7px 4px;
        border-bottom: 1px dashed #f1f5f9;
    }

    .shop-group-item:last-child {
        border-bottom: none;
    }

    .shop-item-left {
        display: flex;
        align-items: center;
        gap: 10px;
        flex: 1;
    }

    .shop-item-qty {
        background: #e0f2fe;
        color: #0369a1;
        font-weight: 800;
        font-size: 13px;
        padding: 3px 7px;
        border-radius: 6px;
        min-width: 32px;
        text-align: center;
        flex-shrink: 0;
    }

    .shop-item-name {
        font-size: 14px;
        font-weight: 700;
        color: #1e293b;
    }

    .shop-item-price {
        font-weight: 800;
        font-size: 14px;
        color: var(--primary);
        white-space: nowrap;
    }

    .shop-group-footer {
        background: #f8fafc;
        padding: 8px 14px;
        text-align: left;
        border-top: 1px solid #e2e8f0;
        font-size: 13px;
        color: #475569;
        font-weight: 700;
    }

    .shop-group-footer span {
        color: #1e40af;
        font-size: 15px;
        font-weight: 800;
    }

    .money-total {
        font-size: 26px;
        font-weight: 800;
        color: var(--success);
        text-align: center;
        padding: 15px;
        background: #ecfdf5;
        border-radius: 8px;
        border: 1px dashed var(--success);
        margin-bottom: 20px;
    }

    .modal-footer {
        padding: 20px;
        border-top: 1px solid var(--border-color);
        display: flex;
        gap: 10px;
        background: white;
        position: sticky;
        bottom: 0;
        border-radius: 0 0 16px 16px;
        z-index: 10;
    }

    .btn-deliver {
        flex: 2;
        padding: 14px;
        background-color: var(--success);
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 18px;
        font-weight: 700;
        cursor: pointer;
        justify-content: center;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: 0.3s;
    }

    .btn-deliver:hover {
        background-color: #059669;
    }

    .btn-cancel {
        flex: 1;
        padding: 12px;
        background-color: white;
        color: var(--secondary);
        border: 1px solid var(--secondary);
        border-radius: 8px;
        font-size: 16px;
        font-weight: 700;
        cursor: pointer;
        transition: 0.3s;
    }

    .btn-cancel:hover {
        background-color: #fee2e2;
    }
    /* MOBILE: responsive received orders */
    @media (max-width: 768px) {

        /* Reduce font sizes of phone numbers inside modal on mobile */
        .party-row, .party-row a, .phone-link {
            font-size: 13.5px !important;
            word-break: break-word;
        }
        .party-row strong {
            font-size: 14px !important;
        }

        /* MOBILE: single column grid */
        .orders-grid {
            grid-template-columns: 1fr;
            gap: 12px;
        }

        .order-card {
            padding: 16px;
        }

        .order-number {
            font-size: 20px;
        }

        .info-label {
            font-size: 12px;
        }

        .info-value {
            font-size: 14px;
        }

        .btn-view {
            padding: 12px;
            font-size: 15px;
        }

        /* MOBILE: full-screen modal on phones */
        .modal-overlay {
            padding: 16px;
            align-items: flex-end;
        }

        .modal-content {
            max-width: 100%;
            max-height: 85vh;
            border-radius: 16px;
            height: auto;
        }

        .modal-header {
            border-radius: 16px 16px 0 0;
            padding: 14px 16px;
        }

        .modal-header h3 {
            font-size: 16px;
        }

        .modal-body {
            padding: 16px;
        }

        .items-list {
            padding: 12px;
            font-size: 13px;
        }

        .money-total {
            font-size: 20px;
            padding: 12px;
        }

        /* MOBILE: stack modal footer buttons vertically */
        .modal-footer {
            flex-direction: column-reverse;
            padding: 14px 16px;
            border-radius: 0 0 16px 16px;
        }

        .modal-footer .btn-deliver,
        .modal-footer .btn-cancel {
            flex: unset;
            width: 100%;
        }

        .btn-deliver {
            font-size: 16px;
            padding: 14px;
        }

        .btn-cancel {
            font-size: 14px;
            padding: 12px;
        }
    }
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
    // Version: 1.0.4 - WhatsApp & UI Sync
    var cachedReserveOrders = [];

    function openWhatsApp(phone, clientName, orderNumber) {
        if (!phone) {
            Swal.fire('خطأ', 'لا يوجد رقم هاتف لهذا العميل', 'error');
            return;
        }
        let formattedPhone = phone.replace(/\s+/g, '').replace(/^0/, '20');
        var myName = @json(auth()->user()->name);
        const message = `اهلا وسهلا , مع حضرتك ${myName} مندوب توصيل دوور فاست.`;
        const url = `https://wa.me/${formattedPhone}?text=${encodeURIComponent(message)}`;
        window.open(url, '_blank');
    }

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
            var clientInfoHtml = '';
            if (order.send_to_phone) {
                clientInfoHtml = `
                <div class="two-party-info">
                    <div class="party sender">
                        <div class="party-label">العميل</div>
                        <div class="party-row" style="gap:5px; margin-bottom:2px"><span class="icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:16px;height:16px;display:block"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg></span> <strong>${clientName}</strong></div>
                        <div class="party-row" style="gap:5px; margin-bottom:2px"><span class="icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:16px;height:16px;display:block"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg></span> <a href="tel:${clientPhone}" onclick="event.stopPropagation()">${clientPhone}</a>${order.client?.phone2 ? ` / <a href="tel:${order.client.phone2}" onclick="event.stopPropagation()">${order.client.phone2}</a>` : ''}</div>
                        <div class="party-row" style="gap:5px; color:var(--text-muted); font-size:13px"><span class="icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:16px;height:16px;display:block"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg></span> <span style="flex-grow:1">${order.client_address || 'بدون عنوان'}</span></div>
                    </div>
                    <div class="party-divider">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="arrow-icon"><path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                    </div>
                    <div class="party receiver">
                        <div class="party-label" style="color:#059669">العميل المستلم (وجهة التوصيل)</div>
                        <div class="party-row" style="gap:5px; margin-bottom:2px"><span class="icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:16px;height:16px;display:block"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg></span> <a href="tel:${order.send_to_phone}" onclick="event.stopPropagation()">${order.send_to_phone}</a>${order.send_to_phone2 ? ` / <a href="tel:${order.send_to_phone2}" onclick="event.stopPropagation()">${order.send_to_phone2}</a>` : ''}</div>
                        <div class="party-row" style="gap:5px"><span class="icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:16px;height:16px;display:block"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg></span> <strong style="flex-grow:1">${order.send_to_address || 'بدون عنوان'}</strong></div>
                    </div>
                </div>`;
            } else {
                clientInfoHtml = `
                <div class="single-party-info">
                    <div class="party-row"><span class="icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:16px;height:16px;display:block"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg></span> <strong>${clientName}</strong></div>
                    <div class="party-row" style="align-items: center;"><span class="icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:16px;height:16px;display:block"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg></span> <a href="tel:${clientPhone}" class="phone-link" onclick="event.stopPropagation()">${clientPhone}</a>${order.client?.phone2 ? ` / <a href="tel:${order.client.phone2}" class="phone-link" onclick="event.stopPropagation()">${order.client.phone2}</a>` : ''}</div>
                    <div class="party-row"><span class="icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:16px;height:16px;display:block"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg></span> <span style="flex-grow:1">${order.client_address || 'لم يتم تحديده'}</span></div>
                </div>`;
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
            <button class="btn-view" onclick="openReserveModal(${order.id})">📋 عرض تفاصيل الطلب</button>`;
            grid.appendChild(card);
        });
    }

    function openReserveModal(orderId) {
        const order = cachedReserveOrders.find(o => o.id === orderId);
        if (!order) return;
        var clientName = order.client?.name ?? 'غير محدد';
        var clientPhone = order.client?.phone ?? '';

        var primaryWaButtonsHtml = '';
        if (order.client?.phone) {
            if (order.client.phone2) {
                primaryWaButtonsHtml = `
                <div class="party-row" style="margin-bottom:8px; gap:8px; flex-wrap:wrap; width:100%; padding-right:30px;">
                    <button onclick="openWhatsApp('${order.client.phone}', '${clientName}', '${order.order_number}')" style="flex:1; background:#25D366;color:white;border:none;border-radius:4px;padding:6px 10px;font-size:12px;cursor:pointer;font-weight:700;">واتساب 1</button>
                    <button onclick="openWhatsApp('${order.client.phone2}', '${clientName}', '${order.order_number}')" style="flex:1; background:#128C7E;color:white;border:none;border-radius:4px;padding:6px 10px;font-size:12px;cursor:pointer;font-weight:700;">واتساب 2</button>
                </div>`;
            } else {
                primaryWaButtonsHtml = `
                <div class="party-row" style="margin-bottom:8px; gap:8px; width:100%; padding-right:30px;">
                    <button onclick="openWhatsApp('${order.client.phone}', '${clientName}', '${order.order_number}')" style="flex:1; background:#25D366;color:white;border:none;border-radius:4px;padding:6px 10px;font-size:12px;cursor:pointer;font-weight:700;">تواصل عبر واتساب</button>
                </div>`;
            }
        }

        var phoneHtml = `<a href="tel:${clientPhone}" class="phone-link">${clientPhone}</a>`;
        if (order.client?.phone2) {
            phoneHtml += ` / <a href="tel:${order.client.phone2}" class="phone-link">${order.client.phone2}</a>`;
        }

        var clientSectionHtml = '';
        if (order.send_to_phone) {
            var sendToWaButtonsHtml = '';
            if (order.send_to_phone2) {
                sendToWaButtonsHtml = `
                <div class="party-row" style="margin-top:5px; gap:8px; flex-wrap:wrap; width:100%; padding-right:30px;">
                    <button onclick="openWhatsApp('${order.send_to_phone}', 'عميل مستلم', '${order.order_number}')" style="flex:1; background:#25D366;color:white;border:none;border-radius:4px;padding:6px 10px;font-size:12px;cursor:pointer;font-weight:700;">واتساب 1</button>
                    <button onclick="openWhatsApp('${order.send_to_phone2}', 'عميل مستلم', '${order.order_number}')" style="flex:1; background:#128C7E;color:white;border:none;border-radius:4px;padding:6px 10px;font-size:12px;cursor:pointer;font-weight:700;">واتساب 2</button>
                </div>`;
            } else {
                sendToWaButtonsHtml = `
                <div class="party-row" style="margin-top:5px; gap:8px; width:100%; padding-right:30px;">
                    <button onclick="openWhatsApp('${order.send_to_phone}', 'عميل مستلم', '${order.order_number}')" style="flex:1; background:#25D366;color:white;border:none;border-radius:4px;padding:6px 10px;font-size:12px;cursor:pointer;font-weight:700;">تواصل عبر واتساب</button>
                </div>`;
            }
            clientSectionHtml = `
            <div class="two-party-info" style="margin-bottom:20px; font-size:15px">
                <div class="party sender">
                    <div class="party-label" style="font-size:13px">العميل (المرسل)</div>
                    <div class="party-row" style="gap:8px; margin-bottom:5px"><span class="icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:16px;height:16px;display:block"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg></span> <strong>${clientName}</strong></div>
                    <div class="party-row" style="gap:8px; margin-bottom:5px"><span class="icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:16px;height:16px;display:block"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg></span> ${phoneHtml}</div>
                    ${primaryWaButtonsHtml}
                    <div class="party-row" style="gap:8px; color:var(--text-muted)"><span class="icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:16px;height:16px;display:block"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg></span> <span style="flex-grow:1">${order.client_address || 'بدون عنوان'}</span>${order.client_delivery_link ? `<a href="${order.client_delivery_link}" target="_blank" style="display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:8px;background:#e0f2fe;color:#0369a1;border:1px solid #bae6fd;text-decoration:none;flex-shrink:0;margin-left:auto" title="فتح الخريطة"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:22px;height:22px;display:block"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg></a>` : ''}</div>
                </div>
                <div class="party-divider" style="margin:10px 0">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="arrow-icon" style="width:24px;height:24px"><path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                </div>
                <div class="party receiver" style="padding:15px">
                    <div class="party-label" style="color:#059669; font-size:13px">العميل المستلم (وجهة التوصيل النهائية)</div>
                    <div class="party-row" style="gap:8px; margin-bottom:5px"><span class="icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:16px;height:16px;display:block"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg></span> <a href="tel:${order.send_to_phone}" style="font-size:16px">${order.send_to_phone}</a>${order.send_to_phone2 ? ` / <a href="tel:${order.send_to_phone2}" style="font-size:16px">${order.send_to_phone2}</a>` : ''}</div>
                    ${sendToWaButtonsHtml}
                    <div class="party-row" style="gap:8px"><span class="icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:16px;height:16px;display:block"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg></span> <strong style="font-size:16px;flex-grow:1">${order.send_to_address || 'بدون عنوان'}</strong>${order.send_to_delivery_link ? `<a href="${order.send_to_delivery_link}" target="_blank" style="display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:8px;background:#d1fae5;color:#059669;border:1px solid #6ee7b7;text-decoration:none;flex-shrink:0;margin-left:auto" title="فتح خريطة المستلم"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:22px;height:22px;display:block"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg></a>` : ''}</div>
                </div>
            </div>`;
        } else {
            clientSectionHtml = `
            <div class="single-party-info" style="background:#f8fafc; padding:15px; border-radius:10px; border:1px solid #e2e8f0; margin-bottom:20px">
                <div class="party-row" style="margin-bottom:8px"><span class="icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:16px;height:16px;display:block"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg></span> <strong style="font-size:16px">${clientName}</strong></div>
                <div class="party-row" style="margin-bottom:8px"><span class="icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:16px;height:16px;display:block"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg></span> ${phoneHtml}</div>
                ${primaryWaButtonsHtml}
                <div class="party-row"><span class="icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:16px;height:16px;display:block"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg></span> <span style="font-size:15px;flex-grow:1">${order.client_address || 'لم يتم تحديده'}</span>${order.client_delivery_link ? `<a href="${order.client_delivery_link}" target="_blank" style="display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:8px;background:#e0f2fe;color:#0369a1;border:1px solid #bae6fd;text-decoration:none;flex-shrink:0;margin-left:auto" title="فتح الخريطة"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:22px;height:22px;display:block"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg></a>` : ''}</div>
            </div>`;
        }

        // ── تجميع الأصناف حسب المتجر ──────────────────────────────
        function groupItemsByShop(items) {
            const groups = {};
            items.forEach(i => {
                const shopKey = i.shop?.name ?? 'بدون متجر';
                if (!groups[shopKey]) {
                    groups[shopKey] = { name: shopKey, items: [], total: 0 };
                }
                const itemTotal = i.total ? parseFloat(i.total) : (parseFloat(i.unit_price || 0) * parseFloat(i.quantity || 1));
                groups[shopKey].items.push({ ...i, computedTotal: itemTotal });
                groups[shopKey].total += itemTotal;
            });
            return Object.values(groups);
        }

        function buildGroupedItemsHtml(items) {
            if (!items || items.length === 0) {
                return `
                    <div class="items-list-container">
                        <div class="items-list-header">🛒 قائمة المنتجات (0)</div>
                        <div class="items-list-body">
                            <div style="text-align:center; padding:10px; color:var(--text-muted);">لا توجد أصناف</div>
                        </div>
                    </div>`;
            }

            const groups = groupItemsByShop(items);
            const totalUniqueItems = items.length;

            const groupsHtml = groups.map(group => {
                const itemsRows = group.items.map(i => `
                    <div class="shop-group-item">
                        <div class="shop-item-left">
                            <div class="shop-item-qty">${i.quantity}×</div>
                            <div class="shop-item-name">${i.item_name}</div>
                        </div>
                        <div class="shop-item-price">${i.computedTotal.toFixed(2)} ج</div>
                    </div>
                `).join('');

                return `
                    <div class="shop-group">
                        <div class="shop-group-header">
                            <div class="shop-group-name">${group.name}</div>

                        </div>
                        <div class="shop-group-items">
                            ${itemsRows}
                        </div>
                        <div class="shop-group-footer">
                            مجموع المتجر: <span>${group.total.toFixed(2)} ج</span>
                        </div>
                    </div>
                `;
            }).join('');

            return `
                <div class="items-list-container">
                    <div class="items-list-header">🛒 قائمة المنتجات (${totalUniqueItems} صنف)</div>
                    <div class="items-list-body" style="gap:0; padding:12px;">
                        ${groupsHtml}
                    </div>
                </div>`;
        }

        var itemsHtml = buildGroupedItemsHtml(order.items);
        const itemsCount = order.items_count ?? (order.items?.length ?? 0);
        const modalContent = document.getElementById('reserve-modal-content');
        modalContent.innerHTML = `
        <div class="modal-header">
            <h3>تفاصيل الطلب #${order.order_number}</h3>
            <button class="btn-close-modal" onclick="closeReserveModal()">✕</button>
        </div>
        <div class="modal-body">
            ${clientSectionHtml}
            <div class="info-group"><div class="info-label">ملاحظات الطلب</div><div class="info-value" style="color:var(--secondary);font-size:15px;background:#fffbeb;padding:12px;border-radius:8px;border:1px dashed #fcd34d">${order.notes || '- لا توجد ملاحظات -'}</div></div>
            ${itemsHtml}
            <div class="money-total">
                المطلوب تحصيله: ${order.total} ج
                <div style="font-size:13px;color:var(--text-muted);font-weight:600;margin-top:8px;">( يشمل توصيل: ${order.delivery_fee} ج | خصم: ${order.discount} ${order.discount_type === 'percent' ? '%' : 'ج'} )</div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel" onclick="cancelReserveOrder(${order.id})">إلغاء الطلب</button>
            <button class="btn-deliver" onclick="markReserveDelivered(${order.id}, '${order.order_number}')">✔ تم التوصيل بنجاح</button>
        </div>`;
        document.getElementById('reserve-order-modal').classList.add('open');
    }

    function closeReserveModal() { document.getElementById('reserve-order-modal').classList.remove('open'); }
    document.getElementById('reserve-order-modal').addEventListener('click', function (e) { if (e.target === this) closeReserveModal(); });

    function markReserveDelivered(id, orderNumber) {
        Swal.fire({ title: 'تأكيد التوصيل', text: 'هل تم تحصيل المبلغ وتوصيل الطلب بنجاح؟', icon: 'question', showCancelButton: true, confirmButtonText: 'نعم', cancelButtonText: 'تراجع', confirmButtonColor: '#10b981' }).then(result => {
            if (!result.isConfirmed) return;
            closeReserveModal();
            axios.post('/reserve/orders/' + id + '/deliver').then(res => {
                if (res.data.success) { Swal.fire({ title: 'تم التوصيل ✅', icon: 'success', confirmButtonText: 'حسناً' }).then(() => fetchReceivedOrders()); }
                else { Swal.fire('خطأ', res.data.message, 'error'); }
            });
        });
    }

    function cancelReserveOrder(id) {
        Swal.fire({ title: 'إلغاء الطلب', input: 'text', inputLabel: 'سبب الإلغاء:', inputPlaceholder: 'مثال: العميل لا يرد', showCancelButton: true, confirmButtonText: 'تأكيد', cancelButtonText: 'تراجع', confirmButtonColor: '#dc2626', preConfirm: r => { if (!r) Swal.showValidationMessage('يجب كتابة سبب'); return r; } }).then(result => {
            if (!result.isConfirmed) return;
            closeReserveModal();
            axios.post('/reserve/orders/' + id + '/cancel', { reason: result.value }).then(res => {
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