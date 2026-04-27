{{-- Callcenter Orders Create SPA partial --}}
<style>
    .cards-wrapper {
        display: flex;
        gap: 16px;
        overflow-x: auto;
        padding-bottom: 12px;
        align-items: flex-start;
    }

    .order-card {
        flex: 0 0 520px;
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: 16px;
        display: flex;
        flex-direction: column;
        height: max-content;
    }

    .order-card-header {
        padding: 14px 18px;
        border-bottom: 1px solid var(--border);
        background: var(--bg);
        border-radius: 16px 16px 0 0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: sticky;
        top: 0;
        z-index: 5;
    }

    .order-card-header .order-meta {
        font-size: 14px;
        color: var(--text-muted);
        margin-top: 2px;
    }

    .order-card-body {
        padding: 16px 18px;
        flex: 1;
    }

    .order-card-footer {
        padding: 14px 18px;
        border-top: 1px solid var(--border);
        background: var(--bg);
        border-radius: 0 0 16px 16px;
        position: sticky;
        bottom: 0;
    }

    .section-label {
        font-size: 11px;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin: 14px 0 8px;
    }

    .items-table {
        width: 100%;
        border-collapse: collapse;
    }

    .items-table th {
        font-size: 11px;
        color: var(--text-muted);
        font-weight: 700;
        padding: 6px 4px;
        text-align: right;
        border-bottom: 1px solid var(--border);
    }

    .items-table td {
        padding: 4px 3px;
        vertical-align: middle;
    }

    .items-table .form-control,
    .items-table .form-select {
        padding: 5px 7px;
        font-size: 12px;
        border-radius: 6px;
    }

    .items-table .item-total {
        font-size: 12px;
        font-weight: 700;
        color: var(--yellow);
        white-space: nowrap;
        padding: 0 4px;
    }

    .btn-del-row {
        background: none;
        border: none;
        color: var(--text-muted);
        cursor: pointer;
        font-size: 16px;
        padding: 2px 6px;
    }

    .btn-del-row:hover {
        color: var(--red);
    }

    .pricing-row {
        display: flex;
        justify-content: space-between;
        padding: 5px 0;
        font-size: 13px;
    }

    .pricing-row.total {
        font-size: 16px;
        font-weight: 800;
        color: var(--yellow);
        border-top: 1px solid var(--border);
        margin-top: 6px;
        padding-top: 10px;
    }

    .disc-type-wrap {
        display: flex;
        gap: 6px;
    }

    .disc-btn {
        padding: 5px 12px;
        border: 1px solid var(--border);
        border-radius: 6px;
        background: none;
        color: var(--text-muted);
        font-family: 'Cairo', sans-serif;
        font-size: 12px;
        cursor: pointer;
        transition: all 0.15s;
    }

    .disc-btn.active {
        border-color: var(--yellow);
        background: var(--yellow);
        color: #000;
        font-weight: 700;
    }

    .sendto-section {
        display: none;
        background: rgba(255, 255, 255, 0.03);
        border: 1px dashed var(--border);
        border-radius: 10px;
        padding: 12px;
        margin-top: 8px;
    }

    .sendto-section.open {
        display: block;
    }

    .add-card-btn {
        flex: 0 0 64px;
        height: 200px;
        background: var(--card-bg);
        border: 2px dashed var(--border);
        border-radius: 16px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        color: var(--text-muted);
        font-size: 28px;
        transition: all 0.2s;
        align-self: flex-start;
    }

    .add-card-btn:hover {
        border-color: var(--yellow);
        color: var(--yellow);
    }
</style>

<div class="section-header">
    <h2>➕ إنشاء طلب جديد</h2>
</div>
<div class="cards-wrapper" id="cards-wrapper">
    <div class="add-card-btn" id="add-card-btn" onclick="addCard()" title="إضافة فاتورة جديدة"><span>＋</span><span
            style="font-size:11px;margin-top:6px">فاتورة</span></div>
</div>

<script>
    var SHOPS = @json($shops);
    var activeDeliveries = [];
    var cardCount = 0;
    var MAX_CARDS = 4;
    var SEARCH_URL = '{{ route("callcenter.clients.search") }}';
    var STORE_URL = '{{ route("callcenter.orders.store") }}';
    var DRAFTS_KEY = 'callcenter_drafts';

    async function loadActiveDeliveries() {
        try { const { data } = await axios.get('{{ route("callcenter.delivery.active") }}'); activeDeliveries = data; } catch (e) { }
    }

    async function initPage() {
        await loadActiveDeliveries();
        var stored = sessionStorage.getItem(DRAFTS_KEY);
        if (stored) { try { const drafts = JSON.parse(stored); if (drafts.length) drafts.forEach(d => addCard(d)); else addCard(); } catch (e) { addCard(); } } else { addCard(); }
    }
    initPage();

    document.getElementById('cards-wrapper').addEventListener('input', () => setTimeout(saveDrafts, 100));
    document.getElementById('cards-wrapper').addEventListener('change', () => setTimeout(saveDrafts, 100));
    document.getElementById('cards-wrapper').addEventListener('click', () => setTimeout(saveDrafts, 100));

    function saveDrafts() {
        var drafts = [];
        document.querySelectorAll('.order-card').forEach(card => {
            var id = card.id; const items = [];
            card.querySelectorAll('.items-table tbody tr').forEach(tr => {
                var inputs = tr.querySelectorAll('input'); const sel = tr.querySelector('select');
                if (inputs.length >= 3) items.push({ name: inputs[0].value, qty: inputs[1].value, price: inputs[2].value, shop: sel ? sel.value : '' });
            });
            drafts.push({
                id: id, editOrderId: document.getElementById(id + '-edit-id')?.value || '',
                phone: document.getElementById(id + '-phone')?.value || '', phone2: document.getElementById(id + '-phone2')?.value || '', code: document.getElementById(id + '-code')?.value || '', name: document.getElementById(id + '-name')?.value || '', cliId: document.getElementById(id + '-client-id')?.value || '', cliFound: document.getElementById(id + '-client-found')?.value || '0',
                addrSelHtml: document.getElementById(id + '-address-sel')?.innerHTML || '', addrSelVal: document.getElementById(id + '-address-sel')?.value || '', addrSelDisplay: document.getElementById(id + '-address-sel')?.style.display || '', addrTxt: document.getElementById(id + '-address-txt')?.value || '', addrTxtDisplay: document.getElementById(id + '-address-txt')?.style.display || '', isNewAddr: document.getElementById(id + '-is-new-addr')?.value || '0',
                delivery: document.getElementById(id + '-delivery')?.value || '', stOpen: document.getElementById(id + '-sendto')?.classList.contains('open') || false, stPhone: document.getElementById(id + '-st-phone')?.value || '', stAddrWrap: document.getElementById(id + '-st-addr-wrap')?.innerHTML || '', stAddrVal: document.getElementById(id + '-st-addr-txt')?.value || '', stCode: document.getElementById(id + '-st-code')?.value || '', stName: document.getElementById(id + '-st-name')?.value || '', stClientId: document.getElementById(id + '-st-client-id')?.value || '', stClientFound: document.getElementById(id + '-st-client-found')?.value || '0',
                notes: document.getElementById(id + '-notes')?.value || '', fee: document.getElementById(id + '-fee')?.value || '0', disc: document.getElementById(id + '-disc')?.value || '0', discType: document.getElementById(id + '-disc-type')?.value || 'amount', items: items
            });
        });
        sessionStorage.setItem(DRAFTS_KEY, JSON.stringify(drafts));
    }

    function genOrderNum() { return 'ORD-' + String(Date.now()).slice(-5).padStart(5, '0') + Math.floor(Math.random() * 10); }
    function nowStr() { return new Date().toLocaleString('ar-EG', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }); }

    function addCard(draft = null) {
        if (!draft && cardCount >= MAX_CARDS) { showWarning('الحد الأقصى 4 فواتير'); return; }
        cardCount++; const id = draft ? draft.id : ('card-' + Date.now()); const isEdit = draft && draft.editOrderId;
        var shopOptions = SHOPS.map(s => `<option value="${s.id}">${s.name}</option>`).join('');
        var card = document.createElement('div'); card.className = 'order-card'; card.id = id;
        card.innerHTML = `<div class="order-card-header"><div><div class="order-meta">${isEdit ? '<span class="badge badge-info" style="font-size:10px;padding:2px 6px;margin-bottom:4px;display:inline-block">✏️ وضع التعديل</span><br>' : ''}${nowStr()} &mdash; {{ auth()->user()->name }}</div></div><button class="btn-close" onclick="removeCard('${id}')" title="إغلاق">✕</button></div>
    <div class="order-card-body"><input type="hidden" id="${id}-edit-id" value="${isEdit ? draft.editOrderId : ''}">
        <div class="section-label">📞 بيانات العميل</div><div class="form-row"><div class="form-group"><label class="form-label">الهاتف *</label><input type="text" class="form-control" id="${id}-phone" placeholder="01xxxxxxxxx" onblur="searchClient('${id}', 'phone')" onkeydown="if(event.key==='Enter') this.blur()"></div><div class="form-group"><label class="form-label">هاتف 2</label><input type="text" class="form-control" id="${id}-phone2" placeholder="اختياري"></div></div><div class="form-row"><div class="form-group"><label class="form-label">الكود *</label><div style="display:flex;gap:5px"><input type="text" class="form-control" id="${id}-code" placeholder="XXXXX" onblur="searchClient('${id}', 'code')" onkeydown="if(event.key==='Enter') this.blur()"><button class="btn btn-secondary btn-sm" style="white-space:nowrap" onclick="genCode('${id}')">كود جديد</button></div></div><div class="form-group"><label class="form-label">الاسم *</label><input type="text" class="form-control" id="${id}-name" placeholder="اسم العميل"></div></div><input type="hidden" id="${id}-client-id" value=""><input type="hidden" id="${id}-client-found" value="0">
        <div class="section-label">📍 العنوان</div><div class="form-group"><label class="form-label">العنوان *</label><select class="form-select" id="${id}-address-sel" onchange="onAddressChange('${id}')"><option value="">— اختر العنوان —</option></select><input type="text" class="form-control" id="${id}-address-txt" placeholder="اكتب العنوان" style="margin-top:6px;display:none"></div><input type="hidden" id="${id}-is-new-addr" value="0">
        <div class="form-group"><label class="form-label">المندوب</label><select class="form-select" id="${id}-delivery"><option value="">— تلقائي —</option>${buildDeliveryOptions()}</select></div>
        <button class="btn btn-secondary btn-sm" style="margin-bottom:8px" onclick="toggleSendTo('${id}')">↗ إرسال إلى عميل آخر</button><div class="sendto-section" id="${id}-sendto"><div class="form-row"><div class="form-group"><label class="form-label">هاتف المستلم</label><input type="text" class="form-control" id="${id}-st-phone" placeholder="01xxxxxxxxx" onblur="stSearchByPhone('${id}')" onkeydown="if(event.key==='Enter') this.blur()"></div><div class="form-group"><label class="form-label">عنوان المستلم *</label><div id="${id}-st-addr-wrap"><input type="text" class="form-control" id="${id}-st-addr-txt" placeholder="العنوان"></div></div></div><div class="form-row"><div class="form-group"><label class="form-label">الكود</label><div style="display:flex;gap:5px"><input type="text" class="form-control" id="${id}-st-code" placeholder="XXXXX" onblur="stSearchByCode('${id}')" onkeydown="if(event.key==='Enter') this.blur()"><button class="btn btn-secondary btn-sm" style="white-space:nowrap" onclick="stGenCode('${id}')">كود جديد</button></div></div><div class="form-group"><label class="form-label">اسم المستلم</label><input type="text" class="form-control" id="${id}-st-name" placeholder="Unnamed if left blank"></div></div></div>
        <input type="hidden" id="${id}-st-client-id" value="">
        <input type="hidden" id="${id}-st-client-found" value="0">
        <div class="section-label">📦 الأصناف</div><table class="items-table"><thead><tr><th style="min-width:120px">الصنف</th><th style="width:55px">الكمية</th><th style="width:70px">السعر</th><th style="width:65px">الإجمالي</th><th style="min-width:100px">المتجر</th><th style="width:30px"></th></tr></thead><tbody id="${id}-items"></tbody></table><button class="btn btn-secondary btn-sm" style="margin-top:8px" onclick="addItemRow('${id}')">＋ إضافة صنف</button>
        <div class="section-label">📝 ملاحظات</div><textarea class="form-control" id="${id}-notes" rows="2" placeholder="ملاحظات اختيارية..."></textarea>
    </div>
    <div class="order-card-footer">
        <div class="form-row" style="margin-bottom:10px"><div class="form-group"><label class="form-label">رسوم التوصيل</label><input type="number" class="form-control" id="${id}-fee" value="0" min="0" step="0.5" oninput="calcTotals('${id}')"></div><div class="form-group"><label class="form-label">الخصم</label><div style="display:flex;gap:5px"><input type="number" class="form-control" id="${id}-disc" value="0" min="0" step="0.5" oninput="calcTotals('${id}')"><div class="disc-type-wrap"><button class="disc-btn active" id="${id}-disc-jm" onclick="setDiscType('${id}','amount')">ج</button><button class="disc-btn" id="${id}-disc-pct" onclick="setDiscType('${id}','percent')">%</button></div></div></div></div><input type="hidden" id="${id}-disc-type" value="amount">
        <div class="pricing-row"><span>إجمالي الأصناف</span><span id="${id}-items-total">0.00 ج</span></div><div class="pricing-row"><span>رسوم التوصيل</span><span id="${id}-fee-display">0.00 ج</span></div><div class="pricing-row"><span>الخصم</span><span id="${id}-disc-display" style="color:var(--red)">0.00 ج</span></div><div class="pricing-row total"><span>الإجمالي النهائي</span><span id="${id}-grand-total">0.00 ج</span></div>
        <div style="display:flex;gap:8px;margin-top:12px"><button class="btn btn-secondary" style="flex:1" onclick="clearCard('${id}')">مسح</button><button class="btn btn-primary" style="flex:2" onclick="saveCard('${id}')">${isEdit ? 'تعديل الطلب ✔' : 'حفظ الطلب ✔'}</button></div>
    </div>`;
        document.getElementById('cards-wrapper').insertBefore(card, document.getElementById('add-card-btn'));
        if (!draft) { for (let i = 0; i < 3; i++) addItemRow(id); resetAddressSection(id, false); saveDrafts(); }
        else {
            var el = s => document.getElementById(id + '-' + s);
            if (el('edit-id')) el('edit-id').value = draft.editOrderId || ''; if (el('phone')) el('phone').value = draft.phone || ''; if (el('phone2')) el('phone2').value = draft.phone2 || ''; if (el('code')) el('code').value = draft.code || ''; if (el('name')) el('name').value = draft.name || ''; if (el('client-id')) el('client-id').value = draft.cliId || ''; if (el('client-found')) el('client-found').value = draft.cliFound || '0';
            if (el('address-sel')) { el('address-sel').innerHTML = draft.addrSelHtml || ''; el('address-sel').value = draft.addrSelVal || ''; el('address-sel').style.display = draft.addrSelDisplay || ''; }
            if (el('address-txt')) { el('address-txt').value = draft.addrTxt || ''; el('address-txt').style.display = draft.addrTxtDisplay || ''; }
            if (el('is-new-addr')) el('is-new-addr').value = draft.isNewAddr || '0'; if (el('delivery')) el('delivery').value = draft.delivery || '';
            if (draft.stOpen && el('sendto')) el('sendto').classList.add('open'); if (el('st-phone')) el('st-phone').value = draft.stPhone || '';
            if (el('st-code')) el('st-code').value = draft.stCode || ''; if (el('st-name')) el('st-name').value = draft.stName || ''; if (el('st-client-id')) el('st-client-id').value = draft.stClientId || ''; if (el('st-client-found')) el('st-client-found').value = draft.stClientFound || '0';
            if (el('st-addr-wrap')) { el('st-addr-wrap').innerHTML = draft.stAddrWrap || el('st-addr-wrap').innerHTML; if (el('st-addr-txt') && draft.stAddrVal) el('st-addr-txt').value = draft.stAddrVal; }
            if (el('notes')) el('notes').value = draft.notes || ''; if (el('fee')) el('fee').value = draft.fee || '0'; if (el('disc')) el('disc').value = draft.disc || '0'; if (el('disc-type')) el('disc-type').value = draft.discType || 'amount';
            if (el('disc-jm')) el('disc-jm').classList.toggle('active', draft.discType !== 'percent'); if (el('disc-pct')) el('disc-pct').classList.toggle('active', draft.discType === 'percent');
            if (draft.items && draft.items.length) { draft.items.forEach(item => { addItemRow(id); const tbody = el('items'); if (tbody && tbody.lastElementChild) { const inputs = tbody.lastElementChild.querySelectorAll('input'); const selShop = tbody.lastElementChild.querySelector('select'); if (inputs[0]) inputs[0].value = item.name; if (inputs[1]) inputs[1].value = item.qty; if (inputs[2]) inputs[2].value = item.price; if (selShop) selShop.value = item.shop || ''; } }); }
            calcTotals(id);
        }
    }

    function buildDeliveryOptions() { return activeDeliveries.map(d => `<option value="${d.id}">${d.name} (${d.orders_today}/${d.max_orders})</option>`).join(''); }
    function refreshDeliveryDropdowns() { document.querySelectorAll('[id$="-delivery"]').forEach(sel => { const current = sel.value; sel.innerHTML = '<option value="">— تلقائي —</option>' + buildDeliveryOptions(); if (current) sel.value = current; }); }

    async function searchClient(cardId, searchBy = 'phone') {
        var params = {};
        if (searchBy === 'phone') { const phone = document.getElementById(cardId + '-phone').value.trim(); if (!phone) return; params = { phone }; }
        else { const code = document.getElementById(cardId + '-code').value.trim(); if (!code) return; params = { code }; }
        try {
            const { data } = await axios.get(SEARCH_URL, { params });
            if (data.found) { document.getElementById(cardId + '-name').value = data.name; document.getElementById(cardId + '-code').value = data.code; document.getElementById(cardId + '-phone').value = data.phone; document.getElementById(cardId + '-phone2').value = data.phone2 ?? ''; document.getElementById(cardId + '-client-id').value = data.id; document.getElementById(cardId + '-client-found').value = '1'; resetAddressSection(cardId, true, data.addresses); }
            else { document.getElementById(cardId + '-client-found').value = '0'; document.getElementById(cardId + '-client-id').value = ''; resetAddressSection(cardId, false); }
            saveDrafts();
        } catch (e) { }
    }

    function resetAddressSection(cardId, hasAddresses, addresses = []) {
        var sel = document.getElementById(cardId + '-address-sel'); const txt = document.getElementById(cardId + '-address-txt'); const isNew = document.getElementById(cardId + '-is-new-addr');
        if (hasAddresses && addresses.length) { 
            sel.style.display = ''; txt.style.display = 'none'; isNew.value = '0'; 
            sel.innerHTML = '<option value="">— اختر العنوان —</option>'; 
            let hasDefault = false;
            addresses.slice(0, 5).forEach(a => { 
                const opt = document.createElement('option'); 
                opt.value = a.address; 
                opt.textContent = a.address + (a.is_default ? ' (افتراضي)' : ''); 
                if (a.is_default) { opt.selected = true; hasDefault = true; } 
                sel.appendChild(opt); 
            }); 
            if (!hasDefault && addresses.length > 0) sel.options[1].selected = true;
            const newOpt = document.createElement('option');
            newOpt.value = '__new__'; newOpt.textContent = '＋ إضافة عنوان جديد';
            sel.appendChild(newOpt);
            txt.value = ''; 
        }
        else { sel.style.display = 'none'; txt.style.display = ''; isNew.value = '1'; txt.value = ''; }
    }

    function onAddressChange(cardId) {
        var sel = document.getElementById(cardId + '-address-sel'); const txt = document.getElementById(cardId + '-address-txt'); const isNew = document.getElementById(cardId + '-is-new-addr');
        if (sel.value === '__new__') { txt.style.display = ''; txt.focus(); isNew.value = '1'; } else { txt.style.display = 'none'; isNew.value = '0'; } saveDrafts();
    }

    async function genCode(cardId) { document.getElementById(cardId + '-code').value = String(Math.floor(10000 + Math.random() * 90000)); saveDrafts(); }
    function toggleSendTo(cardId) { document.getElementById(cardId + '-sendto').classList.toggle('open'); saveDrafts(); }
    async function stSearchByPhone(cardId) {
        var phone = document.getElementById(cardId + '-st-phone').value.trim(); const wrap = document.getElementById(cardId + '-st-addr-wrap'); if (!phone) return;
        try { const { data } = await axios.get(SEARCH_URL, { params: { phone } }); if (data.found) { document.getElementById(cardId + '-st-name').value = data.name; document.getElementById(cardId + '-st-code').value = data.code; document.getElementById(cardId + '-st-client-id').value = data.id; document.getElementById(cardId + '-st-client-found').value = '1'; if (data.addresses.length) { let html = `<select class="form-select" id="${cardId}-st-addr-txt">`; data.addresses.forEach(a => { html += `<option value="${a.address}">${a.address}</option>`; }); html += '</select>'; wrap.innerHTML = html; } else wrap.innerHTML = `<input type="text" class="form-control" id="${cardId}-st-addr-txt" placeholder="العنوان">`; } else { document.getElementById(cardId + '-st-name').value = ''; document.getElementById(cardId + '-st-code').value = ''; document.getElementById(cardId + '-st-client-id').value = ''; document.getElementById(cardId + '-st-client-found').value = '0'; wrap.innerHTML = `<input type="text" class="form-control" id="${cardId}-st-addr-txt" placeholder="العنوان">`; } saveDrafts(); } catch (e) { }
    }
    async function stSearchByCode(cardId) {
        var code = document.getElementById(cardId + '-st-code').value.trim(); const wrap = document.getElementById(cardId + '-st-addr-wrap'); if (!code) return;
        try { const { data } = await axios.get(SEARCH_URL, { params: { code } }); if (data.found) { document.getElementById(cardId + '-st-phone').value = data.phone; document.getElementById(cardId + '-st-name').value = data.name; document.getElementById(cardId + '-st-client-id').value = data.id; document.getElementById(cardId + '-st-client-found').value = '1'; if (data.addresses.length) { let html = `<select class="form-select" id="${cardId}-st-addr-txt">`; data.addresses.forEach(a => { html += `<option value="${a.address}">${a.address}</option>`; }); html += '</select>'; wrap.innerHTML = html; } else wrap.innerHTML = `<input type="text" class="form-control" id="${cardId}-st-addr-txt" placeholder="العنوان">`; } else { document.getElementById(cardId + '-st-phone').value = ''; document.getElementById(cardId + '-st-name').value = ''; document.getElementById(cardId + '-st-client-id').value = ''; document.getElementById(cardId + '-st-client-found').value = '0'; wrap.innerHTML = `<input type="text" class="form-control" id="${cardId}-st-addr-txt" placeholder="العنوان">`; } saveDrafts(); } catch (e) { }
    }
    function stGenCode(cardId) { document.getElementById(cardId + '-st-code').value = String(Math.floor(10000 + Math.random() * 90000)); saveDrafts(); }

    function addItemRow(cardId) {
        var shopOptionsRaw = SHOPS.map(s => `<option value="${s.id}">${s.name}</option>`).join('');
        var tbody = document.getElementById(cardId + '-items'); const rowId = 'row-' + Date.now() + Math.random(); const tr = document.createElement('tr'); tr.id = rowId;
        tr.innerHTML = `<td><input type="text" class="form-control" placeholder="اسم الصنف" oninput="calcTotals('${cardId}')"></td><td><input type="number" class="form-control" value="1" min="0.01" step="1" style="width:52px" oninput="calcRowTotal(this);calcTotals('${cardId}')"></td><td><input type="number" class="form-control" value="0" min="0" step="0.5" style="width:68px" oninput="calcRowTotal(this);calcTotals('${cardId}')"></td><td class="item-total">0.00</td><td><select class="form-select"><option value="">— متجر —</option>${shopOptionsRaw}</select></td><td><button class="btn-del-row" onclick="delRow('${rowId}','${cardId}')">✕</button></td>`;
        tbody.appendChild(tr); saveDrafts();
    }

    function calcRowTotal(input) { const row = input.closest('tr'); const qty = parseFloat(row.cells[1].querySelector('input').value) || 0; const prc = parseFloat(row.cells[2].querySelector('input').value) || 0; row.cells[3].textContent = (qty * prc).toFixed(2); }
    function delRow(rowId, cardId) { document.getElementById(rowId)?.remove(); calcTotals(cardId); saveDrafts(); }
    function calcTotals(cardId) {
        var itemsTotal = 0; document.getElementById(cardId + '-items').querySelectorAll('tr').forEach(tr => { const qty = parseFloat(tr.cells[1].querySelector('input')?.value || 0) || 0; const prc = parseFloat(tr.cells[2].querySelector('input')?.value || 0) || 0; const t = qty * prc; tr.cells[3].textContent = t.toFixed(2); itemsTotal += t; });
        var fee = parseFloat(document.getElementById(cardId + '-fee').value) || 0; const disc = parseFloat(document.getElementById(cardId + '-disc').value) || 0; const discType = document.getElementById(cardId + '-disc-type').value; const discAmt = discType === 'percent' ? (itemsTotal * disc / 100) : disc;
        document.getElementById(cardId + '-items-total').textContent = itemsTotal.toFixed(2) + ' ج'; document.getElementById(cardId + '-fee-display').textContent = fee.toFixed(2) + ' ج'; document.getElementById(cardId + '-disc-display').textContent = discAmt.toFixed(2) + ' ج'; document.getElementById(cardId + '-grand-total').textContent = (itemsTotal + fee - discAmt).toFixed(2) + ' ج';
    }
    function setDiscType(cardId, type) { document.getElementById(cardId + '-disc-type').value = type; document.getElementById(cardId + '-disc-jm').classList.toggle('active', type === 'amount'); document.getElementById(cardId + '-disc-pct').classList.toggle('active', type === 'percent'); calcTotals(cardId); saveDrafts(); }

    async function clearCard(cardId) {
        var ok = await confirmAction('مسح البيانات', 'هل تريد مسح جميع بيانات هذه الفاتورة؟'); if (!ok) return;
        document.getElementById(cardId + '-phone').value = ''; document.getElementById(cardId + '-phone2').value = ''; document.getElementById(cardId + '-code').value = ''; document.getElementById(cardId + '-name').value = ''; document.getElementById(cardId + '-client-id').value = ''; document.getElementById(cardId + '-client-found').value = '0'; document.getElementById(cardId + '-notes').value = ''; document.getElementById(cardId + '-fee').value = '0'; document.getElementById(cardId + '-disc').value = '0'; document.getElementById(cardId + '-items').innerHTML = ''; resetAddressSection(cardId, false); calcTotals(cardId); for (let i = 0; i < 3; i++) addItemRow(cardId); saveDrafts();
    }
    async function removeCard(cardId) {
        var phone = document.getElementById(cardId + '-phone')?.value; const name = document.getElementById(cardId + '-name')?.value; if (phone || name) { const ok = await confirmAction('إغلاق الفاتورة', 'البيانات غير محفوظة. هل تريد الإغلاق؟', 'إغلاق'); if (!ok) return; }
        document.getElementById(cardId)?.remove(); cardCount--; saveDrafts();
    }

    async function saveCard(cardId) {
        var phone = document.getElementById(cardId + '-phone').value.trim(); const phone2 = document.getElementById(cardId + '-phone2').value.trim(); const code = document.getElementById(cardId + '-code').value.trim(); const name = document.getElementById(cardId + '-name').value.trim();
        var addrSel = document.getElementById(cardId + '-address-sel'); const addrTxt = document.getElementById(cardId + '-address-txt'); const isNewAddr = document.getElementById(cardId + '-is-new-addr').value;
        var clientAddress = ''; if (addrSel && addrSel.style.display !== 'none') clientAddress = addrSel.value === '__new__' ? addrTxt.value.trim() : addrSel.value; else if (addrTxt) clientAddress = addrTxt.value.trim();
        var deliveryId = document.getElementById(cardId + '-delivery').value; var stOpen = document.getElementById(cardId + '-sendto')?.classList.contains('open'); var sendToPhone = ''; var sendToAddr = ''; var sendToCode = ''; var sendToName = ''; var sendToClientId = ''; if (stOpen) { sendToPhone = document.getElementById(cardId + '-st-phone')?.value.trim() || ''; var stEl = document.getElementById(cardId + '-st-addr-txt'); sendToAddr = stEl ? (stEl.value || (stEl.options ? stEl.options[stEl.selectedIndex]?.value : '')) : ''; sendToCode = document.getElementById(cardId + '-st-code')?.value.trim() || ''; var rawName = document.getElementById(cardId + '-st-name')?.value.trim(); sendToName = rawName ? rawName : 'Unnamed'; sendToClientId = (document.getElementById(cardId + '-st-client-found')?.value === '1') ? document.getElementById(cardId + '-st-client-id')?.value : ''; }
        var notes = document.getElementById(cardId + '-notes').value; const fee = parseFloat(document.getElementById(cardId + '-fee').value) || 0; const disc = parseFloat(document.getElementById(cardId + '-disc').value) || 0; const discType = document.getElementById(cardId + '-disc-type').value;
        var items = []; document.getElementById(cardId + '-items').querySelectorAll('tr').forEach(tr => { const itemName = tr.cells[0].querySelector('input')?.value.trim(); const qty = parseFloat(tr.cells[1].querySelector('input')?.value) || 0; const price = parseFloat(tr.cells[2].querySelector('input')?.value) || 0; const shopId = tr.cells[4].querySelector('select')?.value; if (itemName) items.push({ item_name: itemName, quantity: qty, unit_price: price, shop_id: shopId || null }); });
        if (!phone) { showError('رقم الهاتف مطلوب'); return; } if (!code) { showError('الكود مطلوب'); return; } if (!name) { showError('اسم العميل مطلوب'); return; } if (!clientAddress) { showError('العنوان مطلوب'); return; } if (!items.length) { showError('يجب إضافة صنف واحد على الأقل'); return; }
        var btn = document.querySelector(`#${cardId} .btn-primary`); btn.disabled = true; btn.textContent = 'جاري الحفظ...'; const editId = document.getElementById(cardId + '-edit-id')?.value;
        var payload = { phone, phone2, code, name, client_address: clientAddress, is_new_address: isNewAddr, delivery_id: deliveryId || null, send_to_phone: sendToPhone || null, send_to_address: sendToAddr || null, send_to_code: sendToCode || null, send_to_name: sendToName || null, send_to_client_id: sendToClientId || null, notes, delivery_fee: fee, discount: disc, discount_type: discType, items };
        try {
            var data; if (editId) { const res = await axios.put('/callcenter/orders/' + editId, payload); data = res.data; } else { const res = await axios.post(STORE_URL, payload); data = res.data; }
            showSuccess(editId ? data.message : 'تم حفظ الطلب ' + data.order_number); if (data.warning) showWarning(data.warning); document.getElementById(cardId)?.remove(); cardCount--; saveDrafts(); await loadActiveDeliveries(); refreshDeliveryDropdowns();
        } catch (e) { const errors = e.response?.data?.errors; if (errors) showError(Object.values(errors).flat().join(' | ')); else showError(e.response?.data?.message ?? 'حدث خطأ'); } finally { btn.disabled = false; btn.textContent = 'حفظ الطلب ✔'; }
    }
</script>