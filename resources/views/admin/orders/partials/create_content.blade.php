{{-- Admin Create Order SPA Partial --}}
{{-- الفرق عن الكول سنتر: المندوب إلزامي، الطلب مباشر لا فترة انتظار --}}
<style>
    .cards-wrapper {
        display: flex;
        gap: 16px;
        overflow-x: auto;
        padding-bottom: 12px;
        align-items: flex-start;
    }

    .order-card {
        flex: 0 0 540px;
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: 16px;
        display: flex;
        flex-direction: column;
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
        font-size: 11px;
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
        text-align: center;
    }

    .items-table .ts-control {
        padding: 6px 8px !important;
        font-size: 12px !important;
        border-radius: 6px !important;
        min-height: 28px !important;
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

    .admin-order-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(245, 158, 11, .15);
        color: var(--yellow);
        border: 1px solid rgba(245, 158, 11, .3);
        border-radius: 8px;
        padding: 6px 14px;
        font-size: 12px;
        font-weight: 700;
        margin-bottom: 14px;
    }

    /* Hide Spin Buttons */
    input::-webkit-outer-spin-button,
    input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    input[type=number] {
        -moz-appearance: textfield;
    }
</style>

<div class="section-header">
    <h2>➕ إنشاء طلب — <span style="color:var(--yellow)">أدمن</span></h2>
</div>
<div class="admin-order-badge">
    ⚡ الطلب يُرسَل فوراً
</div>
<div class="cards-wrapper" id="adm-cards-wrapper">
    <div class="add-card-btn" id="adm-add-btn" onclick="admAddCard()" title="إضافة فاتورة جديدة">
        <span>＋</span><span style="font-size:11px;margin-top:6px">فاتورة</span>
    </div>
</div>

<script>
    (function () {
        var SHOPS = @json($shops);
        var DELIVERIES = @json($deliveries);
        var SEARCH_URL = '{{ route("admin.orders.client-search") }}';
        var STORE_URL = '{{ route("admin.orders.store") }}';
        var cardCount = 0;
        var MAX_CARDS = 4;
        var DRAFTS_KEY = 'admin_drafts';

        // ─── Save Drafts ──────────────────────────────────────────
        function localDateTime() { var d = new Date(); return new Date(d.getTime() - (d.getTimezoneOffset() * 60000)).toISOString().slice(0, 19).replace('T', ' '); }

        function admSaveDrafts() {
            var drafts = [];
            document.querySelectorAll('#adm-cards-wrapper .order-card').forEach(function (card) {
                var id = card.id; var items = [];
                card.querySelectorAll('.items-table tbody tr').forEach(function (tr) {
                    var inputs = tr.querySelectorAll('input'); var sel = tr.querySelector('select');
                    if (inputs.length >= 3) items.push({ name: inputs[0].value, qty: inputs[1].value, price: inputs[2].value, shop: sel ? sel.value : '' });
                });
                var el = function (s) { return document.getElementById(id + '-' + s); };
                drafts.push({
                    id: id,
                    phone: el('phone')?.value || '', phone2: el('phone2')?.value || '',
                    code: el('code')?.value || '', name: el('name')?.value || '',
                    cliId: el('client-id')?.value || '', cliFound: el('client-found')?.value || '0',
                    addrSelHtml: el('address-sel')?.innerHTML || '',
                    addrSelVal: el('address-sel')?.value || '',
                    addrSelDisplay: el('address-sel')?.style.display || '',
                    addrTxt: el('address-txt')?.value || '',
                    addrTxtDisplay: el('address-txt')?.style.display || '',
                    isNewAddr: el('is-new-addr')?.value || '0',
                    delivery: el('delivery')?.value || '',
                    stOpen: el('sendto')?.classList.contains('open') || false,
                    stPhone: el('st-phone')?.value || '', stPhone2: el('st-phone2')?.value || '',
                    stAddrWrap: el('st-addr-wrap')?.innerHTML || '',
                    stAddrSelVal: el('st-addr-sel')?.value || '',
                    stAddrTxtDisplay: el('st-addr-txt')?.style.display || '',
                    stAddrVal: el('st-addr-txt')?.value || '',
                    stCode: el('st-code')?.value || '', stName: el('st-name')?.value || '',
                    stClientId: el('st-client-id')?.value || '', stClientFound: el('st-client-found')?.value || '0',
                    clientDeliveryLink: el('client-delivery-link')?.value || '',
                    stDeliveryLink: el('st-delivery-link')?.value || '',
                    notes: el('notes')?.value || '',
                    fee: el('fee')?.value || '', disc: el('disc')?.value || '0',
                    discType: el('disc-type')?.value || 'amount',
                    openedAt: el('opened-at')?.value || '',
                    displayTime: document.getElementById(id + '-display-time')?.innerText || '',
                    items: items
                });
            });
            sessionStorage.setItem(DRAFTS_KEY, JSON.stringify(drafts));
        }

        // ─── Init Page ────────────────────────────────────────────
        function admInitPage() {
            var stored = sessionStorage.getItem(DRAFTS_KEY);
            if (stored) {
                try {
                    var drafts = JSON.parse(stored);
                    if (drafts.length) { drafts.forEach(function (d) { admAddCard(d); }); }
                } catch (e) { }
            }
        }

        document.getElementById('adm-cards-wrapper').addEventListener('input', function () { setTimeout(admSaveDrafts, 100); });
        document.getElementById('adm-cards-wrapper').addEventListener('change', function () { setTimeout(admSaveDrafts, 100); });
        document.getElementById('adm-cards-wrapper').addEventListener('click', function () { setTimeout(admSaveDrafts, 100); });

        // ─── Add Card ─────────────────────────────────────────────
        window.admAddCard = function (draft) {
            if (!draft && cardCount >= MAX_CARDS) { if (typeof showWarning === 'function') showWarning('الحد الأقصى 4 فواتير'); return; }
            cardCount++;
            var id = draft ? draft.id : ('adm-card-' + Date.now());
            var openedAt = draft && draft.openedAt ? draft.openedAt : localDateTime();
            var displayTime = draft && draft.displayTime ? draft.displayTime : new Date().toLocaleString('en-GB', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hour12: true }).replace('am', 'ص').replace('pm', 'م').replace('AM', 'ص').replace('PM', 'م');
            var shopOpts = SHOPS.map(s => `<option value="${s.id}">${s.name}</option>`).join('');
            var delivOpts = DELIVERIES.length
                ? DELIVERIES.map(d => `<option value="${d.id}">${d.name}</option>`).join('')
                : '<option value="" disabled>لا مناديب في وردية حالياً</option>';

            var card = document.createElement('div');
            card.className = 'order-card'; card.id = id;
            card.innerHTML = `
        <div class="order-card-header">
            <div>
                <div class="order-meta"><span style="font-size:10px;background:rgba(245,158,11,.2);color:var(--yellow);padding:2px 7px;border-radius:4px;margin-bottom:4px;display:inline-block">أدمن مباشر</span><br><span id="${id}-display-time">${displayTime}</span> &mdash; ${@json(auth()->user()->name)}</div>
            </div>
            <button class="btn-close" onclick="admRemoveCard('${id}')">✕</button>
        </div>
        <div class="order-card-body">
            <input type="hidden" id="${id}-opened-at" value="${openedAt}">
            <div class="section-label">بيانات العميل</div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">الهاتف *</label><input type="text" class="form-control" id="${id}-phone" placeholder="01xxxxxxxxx" oninput="admToggleNewCodeBtn('${id}-btn-new-code', false)" onblur="admSearchClient('${id}','phone')" onkeydown="if(event.key==='Enter') this.blur()"></div>
                <div class="form-group"><label class="form-label">هاتف 2</label><input type="text" class="form-control" id="${id}-phone2" placeholder="اختياري"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">الكود *</label>
                    <div style="display:flex;gap:5px">
                        <input type="text" class="form-control" id="${id}-code" placeholder="XXXXX" oninput="admToggleNewCodeBtn('${id}-btn-new-code', false)" onblur="admSearchClient('${id}','code')" onkeydown="if(event.key==='Enter') this.blur()">
                        <button id="${id}-btn-new-code" class="btn btn-secondary btn-sm" style="white-space:nowrap; transition: all 0.2s;" onclick="admGenCode('${id}')">كود جديد</button>
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
                <div class="form-group" style="margin-top:6px">
                    <label class="form-label">لينك التوصيل (اختياري)</label>
                    <input type="url" class="form-control" id="${id}-client-delivery-link" placeholder="https://maps.google.com/..." style="direction:ltr">
                </div>
            </div>
            <input type="hidden" id="${id}-is-new-addr" value="0">

            <div class="form-group" style="margin-bottom:10px">
                <label class="form-label">المندوب <span style="font-size:10px;color:var(--text-muted);font-weight:400">(اختياري)</span></label>
                <select class="form-select" id="${id}-delivery" onchange="admUpdateSubmitBtn('${id}')">
                    <option value="">بدون مندوب</option>
                    ${delivOpts}
                </select>
            </div>

            <button class="btn btn-secondary btn-sm" style="margin-bottom:8px" onclick="admToggleSendTo('${id}')">↗ إرسال إلى عميل آخر</button>
            <div class="sendto-section" id="${id}-sendto">
                <div class="form-row">
                    <div class="form-group"><label class="form-label">هاتف المستلم</label><input type="text" class="form-control" id="${id}-st-phone" placeholder="01xxxxxxxxx" oninput="admToggleNewCodeBtn('${id}-st-btn-new-code', false)" onblur="admStSearchByPhone('${id}')" onkeydown="if(event.key==='Enter') this.blur()"></div>
                    <div class="form-group"><label class="form-label">هاتف 2 (العميل المستلم)</label><input type="text" class="form-control" id="${id}-st-phone2" placeholder="اختياري" dir="ltr" style="text-align:right"></div>
                    <div class="form-group"><label class="form-label">الكود</label><div style="display:flex;gap:5px"><input type="text" class="form-control" id="${id}-st-code" placeholder="XXXXX" oninput="admToggleNewCodeBtn('${id}-st-btn-new-code', false)" onblur="admStSearchByCode('${id}')" onkeydown="if(event.key==='Enter') this.blur()"><button id="${id}-st-btn-new-code" class="btn btn-secondary btn-sm" style="white-space:nowrap; transition: all 0.2s;" onclick="admStGenCode('${id}')">كود جديد</button></div></div>
                    <div class="form-group"><label class="form-label">اسم المستلم</label><input type="text" class="form-control" id="${id}-st-name" placeholder="الاسم"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label class="form-label">عنوان المستلم *</label>
                        <div id="${id}-st-addr-wrap"><input type="text" class="form-control" id="${id}-st-addr-txt" placeholder="العنوان"></div>
                    </div>
                </div>
                <div class="form-group" style="margin-top:6px"><label class="form-label">لينك التوصيل للمستلم (اختياري)</label><input type="url" class="form-control" id="${id}-st-delivery-link" placeholder="https://maps.google.com/..." style="direction:ltr"></div>
            </div>
            <input type="hidden" id="${id}-st-client-id" value="">
            <input type="hidden" id="${id}-st-client-found" value="0">

            <div class="section-label">الأصناف</div>
            <table class="items-table">
                <thead><tr><th style="min-width:130px">الصنف</th><th style="width:55px">الكمية</th><th style="width:70px">السعر</th><th style="width:65px; text-align: center;">الإجمالي</th><th style="min-width:100px">المتجر *</th><th style="width:30px"></th></tr></thead>
                <tbody id="${id}-items"></tbody>
            </table>
            <button class="btn btn-secondary btn-sm" style="margin-top:8px" onclick="admAddItemRow('${id}')">＋ إضافة صنف</button>

            <div class="section-label">ملاحظات</div>
            <textarea class="form-control" id="${id}-notes" rows="2" placeholder="ملاحظات اختيارية..."></textarea>
        </div>
        <div class="order-card-footer">
            <div class="form-row" style="margin-bottom:10px">
                <div class="form-group"><label class="form-label">رسوم التوصيل *</label><input type="number" class="form-control" id="${id}-fee" placeholder="0" min="0" step="0.5" oninput="admCalcTotals('${id}')"></div>
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
            <div class="pricing-row"><span>عدد الأصناف</span><span id="${id}-items-count">0</span></div>
            <div class="pricing-row"><span>إجمالي الأصناف</span><span id="${id}-items-total">0.00 ج</span></div>
            <div class="pricing-row"><span>رسوم التوصيل</span><span id="${id}-fee-display">0.00 ج</span></div>
            <div class="pricing-row"><span>الخصم</span><span id="${id}-disc-display" style="color:var(--red)">0.00 ج</span></div>
            <div class="pricing-row total"><span>الإجمالي النهائي</span><span id="${id}-grand-total">0.00 ج</span></div>
            <div style="display:flex;gap:8px;margin-top:12px">
                <button class="btn btn-secondary" style="flex:1" onclick="admClearCard('${id}')">مسح</button>
                <button class="btn btn-primary" style="flex:2" id="${id}-save-btn" onclick="admSaveCard('${id}')">⚡ إرسال للطلبات الجديدة</button>
            </div>
        </div>`;

            document.getElementById('adm-cards-wrapper').insertBefore(card, document.getElementById('adm-add-btn'));

            if (!draft) {
                for (let i = 0; i < 1; i++) admAddItemRow(id);
                admResetAddressSection(id, false);
                admSaveDrafts();
            } else {
                var el = function (s) { return document.getElementById(id + '-' + s); };
                if (el('phone')) el('phone').value = draft.phone || '';
                if (el('phone2')) el('phone2').value = draft.phone2 || '';
                if (el('code')) el('code').value = draft.code || '';
                if (el('name')) el('name').value = draft.name || '';
                if (el('client-id')) el('client-id').value = draft.cliId || '';
                if (el('client-found')) el('client-found').value = draft.cliFound || '0';
                if (el('address-sel')) { el('address-sel').innerHTML = draft.addrSelHtml || ''; el('address-sel').value = draft.addrSelVal || ''; el('address-sel').style.display = draft.addrSelDisplay || ''; }
                if (el('address-txt')) { el('address-txt').value = draft.addrTxt || ''; el('address-txt').style.display = draft.addrTxtDisplay || ''; }
                if (el('is-new-addr')) el('is-new-addr').value = draft.isNewAddr || '0';
                if (el('delivery')) el('delivery').value = draft.delivery || '';
                if (draft.stOpen && el('sendto')) el('sendto').classList.add('open');
                if (el('st-phone')) el('st-phone').value = draft.stPhone || '';
                if (el('st-phone2')) el('st-phone2').value = draft.stPhone2 || '';
                if (el('st-addr-wrap')) { el('st-addr-wrap').innerHTML = draft.stAddrWrap || el('st-addr-wrap').innerHTML; if (el('st-addr-sel') && draft.stAddrSelVal) el('st-addr-sel').value = draft.stAddrSelVal; if (el('st-addr-txt')) { el('st-addr-txt').value = draft.stAddrVal || ''; if (draft.stAddrTxtDisplay !== undefined) el('st-addr-txt').style.display = draft.stAddrTxtDisplay; } }
                if (el('st-code')) el('st-code').value = draft.stCode || '';
                if (el('st-name')) el('st-name').value = draft.stName || '';
                if (el('st-client-id')) el('st-client-id').value = draft.stClientId || '';
                if (el('st-client-found')) el('st-client-found').value = draft.stClientFound || '0';
                if (draft.cliFound === '1') admToggleNewCodeBtn(id + '-btn-new-code', true);
                if (draft.stClientFound === '1') admToggleNewCodeBtn(id + '-st-btn-new-code', true);
                if (el('client-delivery-link')) el('client-delivery-link').value = draft.clientDeliveryLink || '';
                if (el('st-delivery-link')) el('st-delivery-link').value = draft.stDeliveryLink || '';
                if (el('notes')) el('notes').value = draft.notes || '';
                if (el('fee')) el('fee').value = draft.fee !== undefined ? draft.fee : '';
                if (el('disc')) el('disc').value = draft.disc || '0';
                if (el('disc-type')) el('disc-type').value = draft.discType || 'amount';
                if (el('disc-jm')) el('disc-jm').classList.toggle('active', draft.discType !== 'percent');
                if (el('disc-pct')) el('disc-pct').classList.toggle('active', draft.discType === 'percent');
                if (draft.items && draft.items.length) {
                    draft.items.forEach(function (item) {
                        admAddItemRow(id);
                        var tbody = el('items');
                        if (tbody && tbody.lastElementChild) {
                            var inputs = tbody.lastElementChild.querySelectorAll('input');
                            var selShop = tbody.lastElementChild.querySelector('select');
                            if (inputs[0]) inputs[0].value = item.name;
                            if (inputs[1]) inputs[1].value = item.qty;
                            if (inputs[2]) inputs[2].value = item.price;
                            if (selShop) { if (selShop.tomselect) selShop.tomselect.setValue(item.shop || ''); else selShop.value = item.shop || ''; }
                        }
                    });
                }
                admCalcTotals(id);
                admUpdateSubmitBtn(id);
            }
        };

        // ─── Remove Card ──────────────────────────────────────────
        window.admRemoveCard = function (id) {
            document.getElementById(id)?.remove();
            cardCount--;
            admSaveDrafts();
        };

        // ─── Client Search ────────────────────────────────────────
        window.admToggleNewCodeBtn = function(btnId, disable) {
            var btn = document.getElementById(btnId);
            if (!btn) return;
            if (disable) {
                btn.disabled = true;
                btn.style.opacity = '0.5';
                btn.style.cursor = 'not-allowed';
            } else {
                btn.disabled = false;
                btn.style.opacity = '1';
                btn.style.cursor = 'pointer';
            }
        };

        window.admSearchClient = async function (cardId, searchBy) {
            var params = {};
            if (searchBy === 'phone') { var phone = document.getElementById(cardId + '-phone')?.value.trim(); if (!phone) { admToggleNewCodeBtn(cardId + '-btn-new-code', false); return; } params = { phone }; }
            else { var code = document.getElementById(cardId + '-code')?.value.trim(); if (!code) { admToggleNewCodeBtn(cardId + '-btn-new-code', false); return; } params = { code }; }
            try {
                var { data } = await axios.get(SEARCH_URL, { params });
                if (data.found) {
                    document.getElementById(cardId + '-name').value = data.name;
                    document.getElementById(cardId + '-code').value = data.code;
                    document.getElementById(cardId + '-phone').value = data.phone;
                    if (data.phone2) document.getElementById(cardId + '-phone2').value = data.phone2;
                    document.getElementById(cardId + '-client-id').value = data.id;
                    document.getElementById(cardId + '-client-found').value = '1';
                    admResetAddressSection(cardId, true, data.addresses);
                    admToggleNewCodeBtn(cardId + '-btn-new-code', true);
                } else {
                    document.getElementById(cardId + '-client-found').value = '0';
                    document.getElementById(cardId + '-client-id').value = '';
                    admResetAddressSection(cardId, false);
                    admToggleNewCodeBtn(cardId + '-btn-new-code', false);
                }
                admSaveDrafts();
            } catch (e) { }
        };

        window.admGenCode = function (cardId) {
            document.getElementById(cardId + '-code').value = String(Math.floor(10000 + Math.random() * 90000));
            admSaveDrafts();
        };

        // ─── Address Section ─────────────────────────────────────
        window.admResetAddressSection = function (cardId, hasAddresses, addresses) {
            var sel = document.getElementById(cardId + '-address-sel');
            var txt = document.getElementById(cardId + '-address-txt');
            var isNew = document.getElementById(cardId + '-is-new-addr');
            if (hasAddresses && addresses && addresses.length) {
                sel.style.display = ''; txt.style.display = 'none'; isNew.value = '0';
                sel.innerHTML = '<option value="">— اختر العنوان —</option>';
                let hasDefault = false;
                addresses.slice(0, 5).forEach(function (a) {
                    var opt = document.createElement('option');
                    opt.value = a.address; opt.textContent = a.address + (a.is_default ? ' (افتراضي)' : '');
                    if (a.is_default) { opt.selected = true; hasDefault = true; }
                    sel.appendChild(opt);
                });
                if (!hasDefault && addresses.length > 0) sel.options[1].selected = true;
                var newOpt = document.createElement('option');
                newOpt.value = '__new__'; newOpt.textContent = '＋ إضافة عنوان جديد';
                sel.appendChild(newOpt);
                txt.value = '';
            } else {
                sel.style.display = 'none'; txt.style.display = ''; isNew.value = '1'; txt.value = '';
            }
        };

        window.admAddressChange = function (cardId) {
            var sel = document.getElementById(cardId + '-address-sel');
            var txt = document.getElementById(cardId + '-address-txt');
            var isNew = document.getElementById(cardId + '-is-new-addr');
            if (sel.value === '__new__') { txt.style.display = ''; txt.focus(); isNew.value = '1'; }
            else { txt.style.display = 'none'; isNew.value = '0'; }
            admSaveDrafts();
        };

        // ─── Send-to Toggle ───────────────────────────────────────
        window.admToggleSendTo = function (cardId) {
            document.getElementById(cardId + '-sendto').classList.toggle('open');
            admSaveDrafts();
        };
        window.admOnStAddressChange = function (cardId) {
            var sel = document.getElementById(cardId + '-st-addr-sel');
            var txt = document.getElementById(cardId + '-st-addr-txt');
            if (sel && sel.value === '__new__') { txt.style.display = ''; txt.focus(); txt.value = ''; }
            else if (txt) { txt.style.display = 'none'; }
            admSaveDrafts();
        };
        window.admStSearchByPhone = async function (cardId) {
            var phone = document.getElementById(cardId + '-st-phone').value.trim(); var wrap = document.getElementById(cardId + '-st-addr-wrap'); if (!phone) { admToggleNewCodeBtn(cardId + '-st-btn-new-code', false); return; }
            try { var { data } = await axios.get(SEARCH_URL, { params: { phone } }); if (data.found) { document.getElementById(cardId + '-st-phone').value = data.phone; document.getElementById(cardId + '-st-name').value = data.name; document.getElementById(cardId + '-st-code').value = data.code; if (document.getElementById(cardId + '-st-phone2')) document.getElementById(cardId + '-st-phone2').value = data.phone2 || ''; document.getElementById(cardId + '-st-client-id').value = data.id; document.getElementById(cardId + '-st-client-found').value = '1'; admToggleNewCodeBtn(cardId + '-st-btn-new-code', true); if (data.addresses.length) { let defaultAddr = data.addresses.find(a => a.is_default)?.address || data.addresses[0].address; var html = `<select class="form-select" id="${cardId}-st-addr-sel" onchange="admOnStAddressChange('${cardId}')"><option value="">— اختر العنوان —</option>`; data.addresses.forEach(a => { html += `<option value="${a.address}"${a.address === defaultAddr ? ' selected' : ''}>${a.address}${a.is_default ? ' (افتراضي)' : ''}</option>`; }); html += `<option value="__new__" style="font-weight:bold;color:var(--yellow)">＋ إضافة عنوان جديد...</option></select><input type="text" class="form-control" id="${cardId}-st-addr-txt" placeholder="العنوان" style="margin-top:6px;display:none;">`; wrap.innerHTML = html; } else wrap.innerHTML = `<input type="text" class="form-control" id="${cardId}-st-addr-txt" placeholder="العنوان">`; } else { document.getElementById(cardId + '-st-name').value = ''; document.getElementById(cardId + '-st-code').value = ''; if (document.getElementById(cardId + '-st-phone2')) document.getElementById(cardId + '-st-phone2').value = ''; document.getElementById(cardId + '-st-client-id').value = ''; document.getElementById(cardId + '-st-client-found').value = '0'; admToggleNewCodeBtn(cardId + '-st-btn-new-code', false); wrap.innerHTML = `<input type="text" class="form-control" id="${cardId}-st-addr-txt" placeholder="العنوان">`; } admSaveDrafts(); } catch (e) { }
        };
        window.admStSearchByCode = async function (cardId) {
            var code = document.getElementById(cardId + '-st-code').value.trim(); var wrap = document.getElementById(cardId + '-st-addr-wrap'); if (!code) { admToggleNewCodeBtn(cardId + '-st-btn-new-code', false); return; }
            try { var { data } = await axios.get(SEARCH_URL, { params: { code } }); if (data.found) { document.getElementById(cardId + '-st-phone').value = data.phone; document.getElementById(cardId + '-st-name').value = data.name; if (document.getElementById(cardId + '-st-phone2')) document.getElementById(cardId + '-st-phone2').value = data.phone2 || ''; document.getElementById(cardId + '-st-client-id').value = data.id; document.getElementById(cardId + '-st-client-found').value = '1'; admToggleNewCodeBtn(cardId + '-st-btn-new-code', true); if (data.addresses.length) { let defaultAddr = data.addresses.find(a => a.is_default)?.address || data.addresses[0].address; var html = `<select class="form-select" id="${cardId}-st-addr-sel" onchange="admOnStAddressChange('${cardId}')"><option value="">— اختر العنوان —</option>`; data.addresses.forEach(a => { html += `<option value="${a.address}"${a.address === defaultAddr ? ' selected' : ''}>${a.address}${a.is_default ? ' (افتراضي)' : ''}</option>`; }); html += `<option value="__new__" style="font-weight:bold;color:var(--yellow)">＋ إضافة عنوان جديد...</option></select><input type="text" class="form-control" id="${cardId}-st-addr-txt" placeholder="العنوان" style="margin-top:6px;display:none;">`; wrap.innerHTML = html; } else wrap.innerHTML = `<input type="text" class="form-control" id="${cardId}-st-addr-txt" placeholder="العنوان">`; } else { document.getElementById(cardId + '-st-phone').value = ''; document.getElementById(cardId + '-st-name').value = ''; if (document.getElementById(cardId + '-st-phone2')) document.getElementById(cardId + '-st-phone2').value = ''; document.getElementById(cardId + '-st-client-id').value = ''; document.getElementById(cardId + '-st-client-found').value = '0'; admToggleNewCodeBtn(cardId + '-st-btn-new-code', false); wrap.innerHTML = `<input type="text" class="form-control" id="${cardId}-st-addr-txt" placeholder="العنوان">`; } admSaveDrafts(); } catch (e) { }
        };
        window.admStGenCode = function (cardId) { document.getElementById(cardId + '-st-code').value = String(Math.floor(10000 + Math.random() * 90000)); admSaveDrafts(); };

        // ─── Item Rows ────────────────────────────────────────────
        window.admAddItemRow = function (cardId) {
            var shopOpts = SHOPS.map(s => `<option value="${s.id}">${s.name}</option>`).join('');
            var tbody = document.getElementById(cardId + '-items');
            var rowId = 'adm-row-' + Date.now() + '-' + Math.random().toString(36).slice(2);
            var tr = document.createElement('tr'); tr.id = rowId;
            tr.innerHTML = `
            <td><input type="text" class="form-control" placeholder="اسم الصنف" oninput="admCalcTotals('${cardId}')"></td>
            <td><input type="number" class="form-control" value="1" min="0.01" step="any" style="width:52px" oninput="admCalcRow(this);admCalcTotals('${cardId}')"></td>
            <td><input type="number" class="form-control" value="0" min="0" step="0.5" style="width:68px" oninput="admCalcRow(this);admCalcTotals('${cardId}')"></td>
            <td class="item-total">0.00</td>
            <td><select class="form-select" id="${rowId}-shop"><option value="">— اختر متجر *</option>${shopOpts}</select></td>
            <td><button class="btn-del-row" onclick="admDelRow('${rowId}','${cardId}')">✕</button></td>`;
            tbody.appendChild(tr);

            if (typeof TomSelect !== 'undefined') {
                new TomSelect(`#${rowId}-shop`, {
                    create: false,
                    sortField: { field: "text", direction: "asc" }
                });
            }
            admSaveDrafts();
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
            admSaveDrafts();
        };

        // ─── Totals ───────────────────────────────────────────────
        window.admCalcTotals = function (cardId) {
            var itemsTotal = 0;
            var itemsCount = 0;
            document.getElementById(cardId + '-items').querySelectorAll('tr').forEach(function (tr) {
                var qty = parseFloat(tr.cells[1].querySelector('input')?.value || 0) || 0;
                var prc = parseFloat(tr.cells[2].querySelector('input')?.value || 0) || 0;
                var itemName = tr.cells[0].querySelector('input')?.value.trim();
                var t = qty * prc; tr.cells[3].textContent = t.toFixed(2); itemsTotal += t;
                if (itemName) itemsCount++;
            });
            var fee = parseFloat(document.getElementById(cardId + '-fee').value) || 0;
            var baseTotal = itemsTotal + fee;
            var disc = parseFloat(document.getElementById(cardId + '-disc').value) || 0;
            var discType = document.getElementById(cardId + '-disc-type').value;
            var discAmt = discType === 'percent' ? (baseTotal * disc / 100) : disc;
            if (document.getElementById(cardId + '-items-count')) {
                document.getElementById(cardId + '-items-count').textContent = itemsCount;
            }
            document.getElementById(cardId + '-items-total').textContent = itemsTotal.toFixed(2) + ' ج';
            document.getElementById(cardId + '-fee-display').textContent = fee.toFixed(2) + ' ج';
            document.getElementById(cardId + '-disc-display').textContent = discAmt.toFixed(2) + ' ج';
            document.getElementById(cardId + '-grand-total').textContent = (baseTotal - discAmt).toFixed(2) + ' ج';
        };

        window.admSetDiscType = function (cardId, type) {
            document.getElementById(cardId + '-disc-type').value = type;
            document.getElementById(cardId + '-disc-jm').classList.toggle('active', type === 'amount');
            document.getElementById(cardId + '-disc-pct').classList.toggle('active', type === 'percent');
            admCalcTotals(cardId);
            admSaveDrafts();
        };

        // ─── Update Submit Button & Hint based on delivery selection ──
        window.admUpdateSubmitBtn = function (cardId) {
            var delivery = document.getElementById(cardId + '-delivery')?.value;
            var btn = document.getElementById(cardId + '-save-btn');
            var hint = document.getElementById(cardId + '-delivery-hint');
            if (delivery) {
                if (btn) { btn.textContent = '⚡ إرسال مباشر للمندوب المحدد'; btn.style.background = ''; }
                if (hint) { hint.textContent = '✅ الطلب سيذهب مباشرة إلى قائمة الطلبات المستلمة للمندوب المحدد'; hint.style.color = 'var(--green, #10b981)'; }
            } else {
                if (btn) { btn.textContent = '⚡ إرسال للطلبات الجديدة'; btn.style.background = ''; }
                if (hint) { hint.textContent = '💡 اتركه فارغاً ليظهر في قائمة الطلبات الجديدة لجميع المناديب'; hint.style.color = 'var(--text-muted)'; }
            }
        };

        // ─── Clear Card ───────────────────────────────────────────
        window.admClearCard = function (cardId) {
            ['phone', 'phone2', 'code', 'name', 'notes', 'st-phone', 'st-phone2', 'st-name', 'st-code'].forEach(function (f) {
                var el = document.getElementById(cardId + '-' + f); if (el) el.value = '';
            });
            document.getElementById(cardId + '-client-id').value = '';
            document.getElementById(cardId + '-client-found').value = '0';
            document.getElementById(cardId + '-fee').value = '';
            document.getElementById(cardId + '-disc').value = '0';
            document.getElementById(cardId + '-items').innerHTML = '';
            admResetAddressSection(cardId, false, []);
            admCalcTotals(cardId);
            for (let i = 0; i < 1; i++) admAddItemRow(cardId);
            admSaveDrafts();
        };

        // ─── Save Card ────────────────────────────────────────────
        window.admSaveCard = async function (cardId) {
            // Gather values
            var phone = document.getElementById(cardId + '-phone').value.trim();
            var phone2 = document.getElementById(cardId + '-phone2').value.trim();
            var code = document.getElementById(cardId + '-code').value.trim();
            var name = document.getElementById(cardId + '-name').value.trim();
            var delivery = document.getElementById(cardId + '-delivery').value;
            var notes = document.getElementById(cardId + '-notes').value;
            var fee = parseFloat(document.getElementById(cardId + '-fee').value) || 0;
            var disc = parseFloat(document.getElementById(cardId + '-disc').value) || 0;
            var discType = document.getElementById(cardId + '-disc-type').value;
            var clientDeliveryLink = document.getElementById(cardId + '-client-delivery-link')?.value.trim() || '';
            var stOpen = document.getElementById(cardId + '-sendto')?.classList.contains('open');
            var sendToDeliveryLink = ''; var sendToPhone = ''; var sendToPhone2 = ''; var sendToAddr = ''; var sendToCode = ''; var sendToName = ''; var sendToClientId = '';
            if (stOpen) {
                sendToPhone = document.getElementById(cardId + '-st-phone')?.value.trim() || '';
                sendToPhone2 = document.getElementById(cardId + '-st-phone2')?.value.trim() || '';
                var stSel = document.getElementById(cardId + '-st-addr-sel');
                var stTxt = document.getElementById(cardId + '-st-addr-txt');
                if (stSel && stSel.style.display !== 'none') {
                    sendToAddr = stSel.value === '__new__' ? (stTxt?.value.trim() || '') : stSel.value;
                } else if (stTxt) {
                    sendToAddr = stTxt.value.trim();
                }
                sendToCode = document.getElementById(cardId + '-st-code')?.value.trim() || '';
                var rawName = document.getElementById(cardId + '-st-name')?.value.trim();
                sendToName = rawName ? rawName : 'Unnamed';
                sendToClientId = (document.getElementById(cardId + '-st-client-found')?.value === '1') ? document.getElementById(cardId + '-st-client-id')?.value : '';
                sendToDeliveryLink = document.getElementById(cardId + '-st-delivery-link')?.value.trim() || '';
            }
            var isNewAddr = document.getElementById(cardId + '-is-new-addr').value;

            var addrSel = document.getElementById(cardId + '-address-sel');
            var addrTxt = document.getElementById(cardId + '-address-txt');
            var clientAddress = '';
            if (addrSel && addrSel.style.display !== 'none') {
                clientAddress = addrSel.value === '__new__' ? addrTxt.value.trim() : addrSel.value;
            } else if (addrTxt) {
                clientAddress = addrTxt.value.trim();
            }

            // Collect items
            var items = []; var missingShop = false;
            document.getElementById(cardId + '-items').querySelectorAll('tr').forEach(function (tr) {
                var itemName = tr.cells[0].querySelector('input')?.value.trim();
                var qty = parseFloat(tr.cells[1].querySelector('input')?.value) || 0;
                var price = parseFloat(tr.cells[2].querySelector('input')?.value) || 0;
                var shopSel = tr.querySelector('select[id$="-shop"]');
                var shopId = shopSel ? shopSel.value : null;
                if (itemName) {
                    if (!shopId) missingShop = true;
                    items.push({ item_name: itemName, quantity: qty, unit_price: price, shop_id: shopId || null });
                }
            });

            // Validate
            if (!phone) { if (typeof showError === 'function') showError('رقم الهاتف مطلوب'); return; }
            if (!code) { if (typeof showError === 'function') showError('الكود مطلوب'); return; }
            if (!name) { if (typeof showError === 'function') showError('اسم العميل مطلوب'); return; }
            if (!clientAddress) { if (typeof showError === 'function') showError('العنوان مطلوب'); return; }
            
            // ✅ التحقق من عنوان المستلم
            if (stOpen && !sendToAddr) { if (typeof showError === 'function') showError('عنوان العميل المستلم مطلوب'); return; }
            
            // delivery is optional — no validation required
            if (!items.length) { if (typeof showError === 'function') showError('يجب إضافة صنف واحد على الأقل'); return; }
            if (missingShop) { if (typeof showError === 'function') showError('يجب اختيار المتجر لكل صنف'); return; }

            var feeInput = document.getElementById(cardId + '-fee').value;
            if (feeInput === '') { if (typeof showError === 'function') showError('رسوم التوصيل مطلوبة'); return; }

            var openedAt = document.getElementById(cardId + '-opened-at')?.value || '';

            var btn = document.getElementById(cardId + '-save-btn');
            btn.disabled = true; btn.textContent = 'جارٍ الإرسال...';

            try {
                var { data } = await axios.post(STORE_URL, {
                    phone, phone2, code, name,
                    client_address: clientAddress,
                    is_new_address: isNewAddr,
                    delivery_id: delivery || null,
                    send_to_phone: sendToPhone || null,
                    send_to_phone2: sendToPhone2 || null,
                    send_to_address: sendToAddr || null,
                    send_to_code: sendToCode || null,
                    send_to_name: sendToName || null,
                    send_to_client_id: sendToClientId || null,
                    client_delivery_link: clientDeliveryLink || null,
                    send_to_delivery_link: sendToDeliveryLink || null,
                    notes, delivery_fee: fee, discount: disc, discount_type: discType, items, opened_at: openedAt
                });
                var successLabel = data.has_delivery
                    ? '✅ تم إرسال الطلب ' + data.order_number + ' مباشرةً للمندوب'
                    : '✅ تم إرسال الطلب ' + data.order_number + ' إلى قائمة الطلبات الجديدة';
                if (typeof showSuccess === 'function') showSuccess(successLabel);
                if (data.warning && typeof showWarning === 'function') showWarning(data.warning);
                document.getElementById(cardId)?.remove();
                cardCount--;
                admSaveDrafts();
            } catch (e) {
                var errors = e.response?.data?.errors;
                var msg = errors ? Object.values(errors).flat().join(' | ') : (e.response?.data?.message ?? 'حدث خطأ');
                if (typeof showError === 'function') showError(msg);
            } finally {
                if (document.getElementById(cardId + '-save-btn')) {
                    btn.disabled = false;
                    admUpdateSubmitBtn(cardId);
                }
            }
        };

        // Disable wheel on number inputs to prevent accidental value changes during scrolling
        document.getElementById('adm-cards-wrapper').addEventListener('wheel', function (e) {
            if (e.target.type === 'number' && document.activeElement === e.target) {
                e.target.blur();
            }
        });

        // ─── Boot — add first card on page load ──────────────────
        admInitPage();

    })();
</script>