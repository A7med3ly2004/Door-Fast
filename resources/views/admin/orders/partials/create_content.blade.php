{{-- Admin Create Order SPA Partial --}}
{{-- الفرق عن الكول سنتر: المندوب إلزامي، الطلب مباشر لا فترة انتظار --}}
<style>
.cards-wrapper { display:flex; gap:16px; overflow-x:auto; padding-bottom:12px; align-items:flex-start; }
.order-card { flex:0 0 540px; background:var(--card-bg); border:1px solid var(--border); border-radius:16px; display:flex; flex-direction:column; }
.order-card-header { padding:14px 18px; border-bottom:1px solid var(--border); background:var(--bg); border-radius:16px 16px 0 0; display:flex; align-items:center; justify-content:space-between; position:sticky; top:0; z-index:5; }

.order-card-header .order-meta { font-size:11px; color:var(--text-muted); margin-top:2px; }
.order-card-body { padding:16px 18px; flex:1; }
.order-card-footer { padding:14px 18px; border-top:1px solid var(--border); background:var(--bg); border-radius:0 0 16px 16px; position:sticky; bottom:0; }
.section-label { font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px; margin:14px 0 8px; }
.items-table { width:100%; border-collapse:collapse; }
.items-table th { font-size:11px; color:var(--text-muted); font-weight:700; padding:6px 4px; text-align:right; border-bottom:1px solid var(--border); }
.items-table td { padding:4px 3px; vertical-align:middle; }
.items-table .form-control, .items-table .form-select { padding:5px 7px; font-size:12px; border-radius:6px; }
.items-table .item-total { font-size:12px; font-weight:700; color:var(--yellow); white-space:nowrap; padding:0 4px; }
.btn-del-row { background:none; border:none; color:var(--text-muted); cursor:pointer; font-size:16px; padding:2px 6px; }
.btn-del-row:hover { color:var(--red); }
.pricing-row { display:flex; justify-content:space-between; padding:5px 0; font-size:13px; }
.pricing-row.total { font-size:16px; font-weight:800; color:var(--yellow); border-top:1px solid var(--border); margin-top:6px; padding-top:10px; }
.disc-type-wrap { display:flex; gap:6px; }
.disc-btn { padding:5px 12px; border:1px solid var(--border); border-radius:6px; background:none; color:var(--text-muted); font-family:'Cairo',sans-serif; font-size:12px; cursor:pointer; transition:all 0.15s; }
.disc-btn.active { border-color:var(--yellow); background:var(--yellow); color:#000; font-weight:700; }
.sendto-section { display:none; background:rgba(255,255,255,0.03); border:1px dashed var(--border); border-radius:10px; padding:12px; margin-top:8px; }
.sendto-section.open { display:block; }
.add-card-btn { flex:0 0 64px; height:200px; background:var(--card-bg); border:2px dashed var(--border); border-radius:16px; display:flex; flex-direction:column; align-items:center; justify-content:center; cursor:pointer; color:var(--text-muted); font-size:28px; transition:all 0.2s; align-self:flex-start; }
.add-card-btn:hover { border-color:var(--yellow); color:var(--yellow); }
.admin-order-badge { display:inline-flex; align-items:center; gap:6px; background:rgba(245,158,11,.15); color:var(--yellow); border:1px solid rgba(245,158,11,.3); border-radius:8px; padding:6px 14px; font-size:12px; font-weight:700; margin-bottom:14px; }
</style>

<div class="section-header">
    <h2>➕ إنشاء طلب — <span style="color:var(--yellow)">أدمن</span></h2>
</div>
<div class="admin-order-badge">
    ⚡ الطلب يُرسَل مباشرةً للمندوب بدون فترة انتظار — المندوب إلزامي
</div>
<div class="cards-wrapper" id="adm-cards-wrapper">
    <div class="add-card-btn" id="adm-add-btn" onclick="admAddCard()" title="إضافة فاتورة جديدة">
        <span>＋</span><span style="font-size:11px;margin-top:6px">فاتورة</span>
    </div>
</div>

<script>
(function () {
    var SHOPS      = @json($shops);
    var DELIVERIES = @json($deliveries);
    var SEARCH_URL = '{{ route("admin.orders.client-search") }}';
    var STORE_URL  = '{{ route("admin.orders.store") }}';
    var cardCount  = 0;
    var MAX_CARDS  = 4;

    // ─── Add Card ─────────────────────────────────────────────
    window.admAddCard = function () {
        if (cardCount >= MAX_CARDS) { if(typeof showWarning==='function') showWarning('الحد الأقصى 4 فواتير'); return; }
        cardCount++;
        var id   = 'adm-card-' + Date.now();
        var now  = new Date().toLocaleString('ar-EG', { year:'numeric', month:'short', day:'numeric', hour:'2-digit', minute:'2-digit' });
        var shopOpts = SHOPS.map(s => `<option value="${s.id}">${s.name}</option>`).join('');
        var delivOpts = DELIVERIES.length
            ? DELIVERIES.map(d => `<option value="${d.id}">${d.name} (${d.orders_today}/${d.max_orders})</option>`).join('')
            : '<option value="" disabled>لا مناديب في وردية حالياً</option>';

        var card = document.createElement('div');
        card.className = 'order-card'; card.id = id;
        card.innerHTML = `
        <div class="order-card-header">
            <div>
                <div class="order-meta"><span style="font-size:10px;background:rgba(245,158,11,.2);color:var(--yellow);padding:2px 7px;border-radius:4px;margin-bottom:4px;display:inline-block">أدمن مباشر</span><br>${now} &mdash; {{ auth()->user()->name }}</div>
            </div>
            <button class="btn-close" onclick="admRemoveCard('${id}')">✕</button>
        </div>
        <div class="order-card-body">
            <div class="section-label">📞 بيانات العميل</div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">الهاتف *</label><input type="text" class="form-control" id="${id}-phone" placeholder="01xxxxxxxxx" onblur="admSearchClient('${id}','phone')" onkeydown="if(event.key==='Enter') this.blur()"></div>
                <div class="form-group"><label class="form-label">هاتف 2</label><input type="text" class="form-control" id="${id}-phone2" placeholder="اختياري"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">الكود *</label>
                    <div style="display:flex;gap:5px">
                        <input type="text" class="form-control" id="${id}-code" placeholder="XXXXX" onblur="admSearchClient('${id}','code')" onkeydown="if(event.key==='Enter') this.blur()">
                        <button class="btn btn-secondary btn-sm" style="white-space:nowrap" onclick="admGenCode('${id}')">كود جديد</button>
                    </div>
                </div>
                <div class="form-group"><label class="form-label">الاسم *</label><input type="text" class="form-control" id="${id}-name" placeholder="اسم العميل"></div>
            </div>
            <input type="hidden" id="${id}-client-id">
            <input type="hidden" id="${id}-client-found" value="0">

            <div class="section-label">📍 العنوان</div>
            <div class="form-group">
                <label class="form-label">العنوان *</label>
                <select class="form-select" id="${id}-address-sel" onchange="admAddressChange('${id}')">
                    <option value="">— اختر العنوان —</option>
                </select>
                <input type="text" class="form-control" id="${id}-address-txt" placeholder="اكتب العنوان" style="margin-top:6px;display:none">
            </div>
            <input type="hidden" id="${id}-is-new-addr" value="0">

            <div class="form-group" style="margin-bottom:10px">
                <label class="form-label">🚴 المندوب <span style="color:var(--red)">*</span></label>
                <select class="form-select" id="${id}-delivery" required>
                    <option value="">— اختر المندوب —</option>
                    ${delivOpts}
                </select>
                <div style="font-size:11px;color:var(--text-muted);margin-top:4px">⚠️ يجب تحديد مندوب — الطلب يُرسَل فوراً</div>
            </div>

            <button class="btn btn-secondary btn-sm" style="margin-bottom:8px" onclick="admToggleSendTo('${id}')">↗ إرسال إلى عميل آخر</button>
            <div class="sendto-section" id="${id}-sendto">
                <div class="form-row"><div class="form-group"><label class="form-label">هاتف المستلم</label><input type="text" class="form-control" id="${id}-st-phone" placeholder="01xxxxxxxxx" onblur="admStSearchByPhone('${id}')" onkeydown="if(event.key==='Enter') this.blur()"></div><div class="form-group"><label class="form-label">عنوان المستلم *</label><div id="${id}-st-addr-wrap"><input type="text" class="form-control" id="${id}-st-addr-txt" placeholder="العنوان"></div></div></div>
                <div class="form-row"><div class="form-group"><label class="form-label">الكود</label><div style="display:flex;gap:5px"><input type="text" class="form-control" id="${id}-st-code" placeholder="XXXXX" onblur="admStSearchByCode('${id}')" onkeydown="if(event.key==='Enter') this.blur()"><button class="btn btn-secondary btn-sm" style="white-space:nowrap" onclick="admStGenCode('${id}')">كود جديد</button></div></div><div class="form-group"><label class="form-label">اسم المستلم</label><input type="text" class="form-control" id="${id}-st-name" placeholder="Unnamed if left blank"></div></div>
            </div>
            <input type="hidden" id="${id}-st-client-id" value="">
            <input type="hidden" id="${id}-st-client-found" value="0">

            <div class="section-label">📦 الأصناف</div>
            <table class="items-table">
                <thead><tr><th style="min-width:130px">الصنف</th><th style="width:55px">الكمية</th><th style="width:70px">السعر</th><th style="width:65px">الإجمالي</th><th style="min-width:100px">المتجر</th><th style="width:30px"></th></tr></thead>
                <tbody id="${id}-items"></tbody>
            </table>
            <button class="btn btn-secondary btn-sm" style="margin-top:8px" onclick="admAddItemRow('${id}')">＋ إضافة صنف</button>

            <div class="section-label">📝 ملاحظات</div>
            <textarea class="form-control" id="${id}-notes" rows="2" placeholder="ملاحظات اختيارية..."></textarea>
        </div>
        <div class="order-card-footer">
            <div class="form-row" style="margin-bottom:10px">
                <div class="form-group"><label class="form-label">رسوم التوصيل</label><input type="number" class="form-control" id="${id}-fee" value="0" min="0" step="0.5" oninput="admCalcTotals('${id}')"></div>
                <div class="form-group"><label class="form-label">الخصم</label>
                    <div style="display:flex;gap:5px">
                        <input type="number" class="form-control" id="${id}-disc" value="0" min="0" step="0.5" oninput="admCalcTotals('${id}')">
                        <div class="disc-type-wrap">
                            <button class="disc-btn active" id="${id}-disc-jm" onclick="admSetDiscType('${id}','amount')">ج</button>
                            <button class="disc-btn"        id="${id}-disc-pct" onclick="admSetDiscType('${id}','percent')">%</button>
                        </div>
                    </div>
                </div>
            </div>
            <input type="hidden" id="${id}-disc-type" value="amount">
            <div class="pricing-row"><span>إجمالي الأصناف</span><span id="${id}-items-total">0.00 ج</span></div>
            <div class="pricing-row"><span>رسوم التوصيل</span><span id="${id}-fee-display">0.00 ج</span></div>
            <div class="pricing-row"><span>الخصم</span><span id="${id}-disc-display" style="color:var(--red)">0.00 ج</span></div>
            <div class="pricing-row total"><span>الإجمالي النهائي</span><span id="${id}-grand-total">0.00 ج</span></div>
            <div style="display:flex;gap:8px;margin-top:12px">
                <button class="btn btn-secondary" style="flex:1" onclick="admClearCard('${id}')">مسح</button>
                <button class="btn btn-primary" style="flex:2" id="${id}-save-btn" onclick="admSaveCard('${id}')">⚡ إرسال فوري للمندوب</button>
            </div>
        </div>`;

        document.getElementById('adm-cards-wrapper').insertBefore(card, document.getElementById('adm-add-btn'));

        // Add 3 default item rows
        for (let i = 0; i < 3; i++) admAddItemRow(id);
        admResetAddressSection(id, false);
    };

    // ─── Remove Card ──────────────────────────────────────────
    window.admRemoveCard = function (id) {
        document.getElementById(id)?.remove();
        cardCount--;
    };

    // ─── Client Search ────────────────────────────────────────
    window.admSearchClient = async function (cardId, searchBy) {
        var params = {};
        if (searchBy === 'phone') { var phone = document.getElementById(cardId+'-phone')?.value.trim(); if (!phone) return; params = { phone }; }
        else { var code = document.getElementById(cardId+'-code')?.value.trim(); if (!code) return; params = { code }; }
        try {
            var { data } = await axios.get(SEARCH_URL, { params });
            if (data.found) {
                document.getElementById(cardId+'-name').value = data.name;
                document.getElementById(cardId+'-code').value = data.code;
                document.getElementById(cardId+'-phone').value = data.phone;
                if (data.phone2) document.getElementById(cardId+'-phone2').value = data.phone2;
                document.getElementById(cardId+'-client-id').value = data.id;
                document.getElementById(cardId+'-client-found').value = '1';
                admResetAddressSection(cardId, true, data.addresses);
            } else {
                document.getElementById(cardId+'-client-found').value = '0';
                document.getElementById(cardId+'-client-id').value = '';
                admResetAddressSection(cardId, false);
            }
        } catch(e) {}
    };

    window.admGenCode = function (cardId) {
        document.getElementById(cardId+'-code').value = String(Math.floor(10000 + Math.random() * 90000));
    };

    // ─── Address Section ─────────────────────────────────────
    window.admResetAddressSection = function (cardId, hasAddresses, addresses) {
        var sel  = document.getElementById(cardId+'-address-sel');
        var txt  = document.getElementById(cardId+'-address-txt');
        var isNew = document.getElementById(cardId+'-is-new-addr');
        if (hasAddresses && addresses && addresses.length) {
            sel.style.display = ''; txt.style.display = 'none'; isNew.value = '0';
            sel.innerHTML = '<option value="">— اختر العنوان —</option>';
            addresses.slice(0,5).forEach(function (a) {
                var opt = document.createElement('option');
                opt.value = a.address; opt.textContent = a.address + (a.is_default ? ' (افتراضي)' : '');
                if (a.is_default) opt.selected = true;
                sel.appendChild(opt);
            });
            sel.innerHTML += '<option value="__new__">＋ إضافة عنوان جديد</option>';
            txt.value = '';
        } else {
            sel.style.display = 'none'; txt.style.display = ''; isNew.value = '1'; txt.value = '';
        }
    };

    window.admAddressChange = function (cardId) {
        var sel   = document.getElementById(cardId+'-address-sel');
        var txt   = document.getElementById(cardId+'-address-txt');
        var isNew = document.getElementById(cardId+'-is-new-addr');
        if (sel.value === '__new__') { txt.style.display = ''; txt.focus(); isNew.value = '1'; }
        else { txt.style.display = 'none'; isNew.value = '0'; }
    };

    // ─── Send-to Toggle ───────────────────────────────────────
    window.admToggleSendTo = function (cardId) {
        document.getElementById(cardId+'-sendto').classList.toggle('open');
    };
    window.admStSearchByPhone = async function (cardId) {
        var phone = document.getElementById(cardId+'-st-phone').value.trim(); var wrap = document.getElementById(cardId+'-st-addr-wrap'); if (!phone) return;
        try { var { data } = await axios.get(SEARCH_URL, { params: { phone } }); if (data.found) { document.getElementById(cardId+'-st-name').value = data.name; document.getElementById(cardId+'-st-code').value = data.code; document.getElementById(cardId+'-st-client-id').value = data.id; document.getElementById(cardId+'-st-client-found').value = '1'; if (data.addresses.length) { var html = `<select class="form-select" id="${cardId}-st-addr-txt">`; data.addresses.forEach(a => { html += `<option value="${a.address}">${a.address}</option>`; }); html += '</select>'; wrap.innerHTML = html; } else wrap.innerHTML = `<input type="text" class="form-control" id="${cardId}-st-addr-txt" placeholder="العنوان">`; } else { document.getElementById(cardId+'-st-name').value = ''; document.getElementById(cardId+'-st-code').value = ''; document.getElementById(cardId+'-st-client-id').value = ''; document.getElementById(cardId+'-st-client-found').value = '0'; wrap.innerHTML = `<input type="text" class="form-control" id="${cardId}-st-addr-txt" placeholder="العنوان">`; } } catch (e) { }
    };
    window.admStSearchByCode = async function (cardId) {
        var code = document.getElementById(cardId+'-st-code').value.trim(); var wrap = document.getElementById(cardId+'-st-addr-wrap'); if (!code) return;
        try { var { data } = await axios.get(SEARCH_URL, { params: { code } }); if (data.found) { document.getElementById(cardId+'-st-phone').value = data.phone; document.getElementById(cardId+'-st-name').value = data.name; document.getElementById(cardId+'-st-client-id').value = data.id; document.getElementById(cardId+'-st-client-found').value = '1'; if (data.addresses.length) { var html = `<select class="form-select" id="${cardId}-st-addr-txt">`; data.addresses.forEach(a => { html += `<option value="${a.address}">${a.address}</option>`; }); html += '</select>'; wrap.innerHTML = html; } else wrap.innerHTML = `<input type="text" class="form-control" id="${cardId}-st-addr-txt" placeholder="العنوان">`; } else { document.getElementById(cardId+'-st-phone').value = ''; document.getElementById(cardId+'-st-name').value = ''; document.getElementById(cardId+'-st-client-id').value = ''; document.getElementById(cardId+'-st-client-found').value = '0'; wrap.innerHTML = `<input type="text" class="form-control" id="${cardId}-st-addr-txt" placeholder="العنوان">`; } } catch (e) { }
    };
    window.admStGenCode = function (cardId) { document.getElementById(cardId+'-st-code').value = String(Math.floor(10000 + Math.random() * 90000)); };

    // ─── Item Rows ────────────────────────────────────────────
    window.admAddItemRow = function (cardId) {
        var shopOpts = SHOPS.map(s => `<option value="${s.id}">${s.name}</option>`).join('');
        var tbody = document.getElementById(cardId+'-items');
        var rowId = 'adm-row-' + Date.now() + '-' + Math.random().toString(36).slice(2);
        var tr = document.createElement('tr'); tr.id = rowId;
        tr.innerHTML = `
            <td><input type="text" class="form-control" placeholder="اسم الصنف" oninput="admCalcTotals('${cardId}')"></td>
            <td><input type="number" class="form-control" value="1" min="0.01" step="1" style="width:52px" oninput="admCalcRow(this);admCalcTotals('${cardId}')"></td>
            <td><input type="number" class="form-control" value="0" min="0" step="0.5" style="width:68px" oninput="admCalcRow(this);admCalcTotals('${cardId}')"></td>
            <td class="item-total">0.00</td>
            <td><select class="form-select"><option value="">— متجر —</option>${shopOpts}</select></td>
            <td><button class="btn-del-row" onclick="admDelRow('${rowId}','${cardId}')">✕</button></td>`;
        tbody.appendChild(tr);
    };

    window.admCalcRow = function (input) {
        var row = input.closest('tr');
        var qty = parseFloat(row.cells[1].querySelector('input').value) || 0;
        var prc = parseFloat(row.cells[2].querySelector('input').value) || 0;
        row.cells[3].textContent = (qty * prc).toFixed(2);
    };

    window.admDelRow = function (rowId, cardId) {
        document.getElementById(rowId)?.remove();
        admCalcTotals(cardId);
    };

    // ─── Totals ───────────────────────────────────────────────
    window.admCalcTotals = function (cardId) {
        var itemsTotal = 0;
        document.getElementById(cardId+'-items').querySelectorAll('tr').forEach(function (tr) {
            var qty = parseFloat(tr.cells[1].querySelector('input')?.value || 0) || 0;
            var prc = parseFloat(tr.cells[2].querySelector('input')?.value || 0) || 0;
            var t = qty * prc; tr.cells[3].textContent = t.toFixed(2); itemsTotal += t;
        });
        var fee = parseFloat(document.getElementById(cardId+'-fee').value) || 0;
        var disc = parseFloat(document.getElementById(cardId+'-disc').value) || 0;
        var discType = document.getElementById(cardId+'-disc-type').value;
        var discAmt = discType === 'percent' ? (itemsTotal * disc / 100) : disc;
        document.getElementById(cardId+'-items-total').textContent  = itemsTotal.toFixed(2) + ' ج';
        document.getElementById(cardId+'-fee-display').textContent  = fee.toFixed(2) + ' ج';
        document.getElementById(cardId+'-disc-display').textContent = discAmt.toFixed(2) + ' ج';
        document.getElementById(cardId+'-grand-total').textContent  = (itemsTotal + fee - discAmt).toFixed(2) + ' ج';
    };

    window.admSetDiscType = function (cardId, type) {
        document.getElementById(cardId+'-disc-type').value = type;
        document.getElementById(cardId+'-disc-jm').classList.toggle('active', type === 'amount');
        document.getElementById(cardId+'-disc-pct').classList.toggle('active', type === 'percent');
        admCalcTotals(cardId);
    };

    // ─── Clear Card ───────────────────────────────────────────
    window.admClearCard = function (cardId) {
        ['phone','phone2','code','name','notes'].forEach(function (f) {
            var el = document.getElementById(cardId+'-'+f); if(el) el.value = '';
        });
        document.getElementById(cardId+'-client-id').value = '';
        document.getElementById(cardId+'-client-found').value = '0';
        document.getElementById(cardId+'-fee').value = '0';
        document.getElementById(cardId+'-disc').value = '0';
        document.getElementById(cardId+'-items').innerHTML = '';
        admResetAddressSection(cardId, false, []);
        admCalcTotals(cardId);
        for (let i = 0; i < 3; i++) admAddItemRow(cardId);
    };

    // ─── Save Card ────────────────────────────────────────────
    window.admSaveCard = async function (cardId) {
        // Gather values
        var phone    = document.getElementById(cardId+'-phone').value.trim();
        var phone2   = document.getElementById(cardId+'-phone2').value.trim();
        var code     = document.getElementById(cardId+'-code').value.trim();
        var name     = document.getElementById(cardId+'-name').value.trim();
        var delivery = document.getElementById(cardId+'-delivery').value;
        var notes    = document.getElementById(cardId+'-notes').value;
        var fee      = parseFloat(document.getElementById(cardId+'-fee').value) || 0;
        var disc     = parseFloat(document.getElementById(cardId+'-disc').value) || 0;
        var discType = document.getElementById(cardId+'-disc-type').value;
        var stOpen = document.getElementById(cardId + '-sendto')?.classList.contains('open');
        var sendToPhone = ''; var sendToAddr = ''; var sendToCode = ''; var sendToName = ''; var sendToClientId = ''; 
        if (stOpen) { 
            sendToPhone = document.getElementById(cardId + '-st-phone')?.value.trim() || ''; 
            var stEl = document.getElementById(cardId + '-st-addr-txt'); 
            sendToAddr = stEl ? (stEl.value || (stEl.options ? stEl.options[stEl.selectedIndex]?.value : '')) : ''; 
            sendToCode = document.getElementById(cardId + '-st-code')?.value.trim() || ''; 
            var rawName = document.getElementById(cardId + '-st-name')?.value.trim(); 
            sendToName = rawName ? rawName : 'Unnamed'; 
            sendToClientId = (document.getElementById(cardId + '-st-client-found')?.value === '1') ? document.getElementById(cardId + '-st-client-id')?.value : ''; 
        }
        var isNewAddr = document.getElementById(cardId+'-is-new-addr').value;

        var addrSel = document.getElementById(cardId+'-address-sel');
        var addrTxt = document.getElementById(cardId+'-address-txt');
        var clientAddress = '';
        if (addrSel && addrSel.style.display !== 'none') {
            clientAddress = addrSel.value === '__new__' ? addrTxt.value.trim() : addrSel.value;
        } else if (addrTxt) {
            clientAddress = addrTxt.value.trim();
        }

        // Collect items
        var items = [];
        document.getElementById(cardId+'-items').querySelectorAll('tr').forEach(function (tr) {
            var itemName = tr.cells[0].querySelector('input')?.value.trim();
            var qty      = parseFloat(tr.cells[1].querySelector('input')?.value) || 0;
            var price    = parseFloat(tr.cells[2].querySelector('input')?.value) || 0;
            var shopId   = tr.cells[4].querySelector('select')?.value;
            if (itemName) items.push({ item_name: itemName, quantity: qty, unit_price: price, shop_id: shopId || null });
        });

        // Validate
        if (!phone)          { if(typeof showError==='function') showError('رقم الهاتف مطلوب'); return; }
        if (!code)           { if(typeof showError==='function') showError('الكود مطلوب'); return; }
        if (!name)           { if(typeof showError==='function') showError('اسم العميل مطلوب'); return; }
        if (!clientAddress)  { if(typeof showError==='function') showError('العنوان مطلوب'); return; }
        if (!delivery)       { if(typeof showError==='function') showError('يجب تحديد المندوب — الطلب مباشر'); return; }
        if (!items.length)   { if(typeof showError==='function') showError('يجب إضافة صنف واحد على الأقل'); return; }

        var btn = document.getElementById(cardId+'-save-btn');
        btn.disabled = true; btn.textContent = 'جارٍ الإرسال...';

        try {
            var { data } = await axios.post(STORE_URL, {
                phone, phone2, code, name,
                client_address: clientAddress,
                is_new_address: isNewAddr,
                delivery_id: delivery,
                send_to_phone: sendToPhone || null,
                send_to_address: sendToAddr || null,
                send_to_code: sendToCode || null,
                send_to_name: sendToName || null,
                send_to_client_id: sendToClientId || null,
                notes, delivery_fee: fee, discount: disc, discount_type: discType, items
            });
            if (typeof showSuccess === 'function') showSuccess('✅ تم إرسال الطلب ' + data.order_number + ' للمندوب فوراً');
            if (data.warning && typeof showWarning === 'function') showWarning(data.warning);
            document.getElementById(cardId)?.remove();
            cardCount--;
        } catch(e) {
            var errors = e.response?.data?.errors;
            var msg = errors ? Object.values(errors).flat().join(' | ') : (e.response?.data?.message ?? 'حدث خطأ');
            if (typeof showError === 'function') showError(msg);
        } finally {
            if (document.getElementById(cardId+'-save-btn')) {
                btn.disabled = false; btn.textContent = '⚡ إرسال فوري للمندوب';
            }
        }
    };

    // ─── Boot — add first card on page load ──────────────────
    window.admAddCard();

})();
</script>
