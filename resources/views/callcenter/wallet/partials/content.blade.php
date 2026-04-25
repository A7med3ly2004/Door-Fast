{{--
resources/views/callcenter/wallet/partials/content.blade.php
كشف حسابي — خزينة الكول سينتر
--}}

<div class="section-header">
    <h2>كشف حسابي</h2>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <button class="btn" onclick="openPayModal()" style="background:#0891b2;color:#fff;">ايصال دفع لمندوب</button>
        <button class="btn" onclick="openReceiveModal()" style="background:#059669;color:#fff;">ايصال استلام من مندوب</button>
    </div>
</div>

{{-- ── Filters ──────────────────────────────────────────────── --}}
<div class="card" style="margin-bottom:18px;">
    <div class="filter-bar" style="margin-bottom:0;">
        <div>
            <div class="form-label" style="margin-bottom:4px;">من تاريخ</div>
            <input type="date" id="w-filter-from" class="form-control">
        </div>
        <div>
            <div class="form-label" style="margin-bottom:4px;">إلى تاريخ</div>
            <input type="date" id="w-filter-to" class="form-control">
        </div>
        <div>
            <div class="form-label" style="margin-bottom:4px;">مندوب</div>
            <select id="w-filter-delivery" class="form-select">
                <option value="">الكل</option>
                @foreach($deliveries as $d)
                    <option value="{{ $d->id }}">{{ $d->name }}</option>
                @endforeach
            </select>
        </div>
        <div style="display:flex;gap:8px;align-self:flex-end;">
            <button class="btn btn-primary" onclick="wApplyFilters()">عرض</button>
            <button class="btn btn-secondary" onclick="wResetFilters()">اعادة</button>
        </div>
    </div>
</div>

{{-- ── KPIs ──────────────────────────────────────────────────── --}}
<div class="kpi-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:18px;">
    <div class="kpi-card green">
        <div class="kpi-label">إجمالي المدين (دخل)</div>
        <div class="kpi-value" id="w-kpi-debit" style="color:var(--success);">—</div>
        <div class="kpi-sub">ج.م</div>
    </div>
    <div class="kpi-card red">
        <div class="kpi-label">إجمالي الدائن (خروج)</div>
        <div class="kpi-value" id="w-kpi-credit" style="color:var(--red);">—</div>
        <div class="kpi-sub">ج.م</div>
    </div>
    <div class="kpi-card yellow">
        <div class="kpi-label">الرصيد الحالي</div>
        <div class="kpi-value" id="w-kpi-balance" style="color:var(--yellow);">—</div>
        <div class="kpi-sub">ج.م</div>
    </div>
</div>

{{-- ── Transactions Table ────────────────────────────────────── --}}
<div class="card" style="padding:0;">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-bottom:1px solid var(--border);">
        <span style="font-size:14px;font-weight:700;">سجل العمليات</span>
        <span class="badge badge-gray" id="w-total-badge">—</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="text-align:center">رقم العملية</th>
                    <th style="text-align:center">التاريخ</th>
                    <th style="text-align:right">التعريف / الملاحظة</th>
                    <th style="text-align:center">مدين</th>
                    <th style="text-align:center">دائن</th>
                    <th style="text-align:center">الرصيد</th>
                </tr>
            </thead>
            <tbody id="w-tbody">
                <tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:50px;">
                    <div class="spin" style="margin:0 auto 10px;"></div>جاري التحميل...
                </td></tr>
            </tbody>
        </table>
    </div>
</div>

{{-- ── Pay Modal ─────────────────────────────────────────────── --}}
<div class="modal-overlay" id="modal-cc-pay">
    <div class="modal">
        <div class="modal-header" style="background:rgba(8,145,178,.08);border-bottom:0;">
            <h3 style="color:#0891b2;">ايصال دفع لمندوب</h3>
            <button class="btn-close" onclick="closeModal('modal-cc-pay')">✕</button>
        </div>
        <div class="modal-body">
            <div id="pay-global-error" style="background:var(--red-light);color:var(--red-dark);padding:10px;border-radius:8px;margin-bottom:12px;display:none;font-size:12px;"></div>
            <div class="form-group">
                <label for="pay-delivery-id" class="form-label">المندوب <span style="color:var(--red)">*</span></label>
                <select id="pay-delivery-id" class="form-select">
                    <option value="">اختر مندوب...</option>
                    @foreach($deliveries as $d)
                        <option value="{{ $d->id }}">{{ $d->name }}</option>
                    @endforeach
                </select>
                <div class="error-text" id="pay-delivery-id-error" style="color:var(--red);font-size:11px;margin-top:4px;"></div>
            </div>
            <div class="form-group">
                <label for="pay-amount" class="form-label">المبلغ <span style="color:var(--red)">*</span></label>
                <div style="display:flex;">
                    <input type="number" id="pay-amount" class="form-control" placeholder="0.00" min="0.01" step="0.01" max="9999999.99" style="border-radius:0 8px 8px 0;flex:1;">
                    <span style="background:var(--input-bg);padding:8px 11px;border-radius:8px 0 0 8px;font-size:12px;color:var(--text-muted);border:1px solid var(--border);border-right:none;">ج.م</span>
                </div>
                <div class="error-text" id="pay-amount-error" style="color:var(--red);font-size:11px;margin-top:4px;"></div>
            </div>
            <div class="form-group">
                <label for="pay-date" class="form-label">التاريخ <span style="color:var(--text-muted);font-weight:400;font-size:10px;">(اختياري)</span></label>
                <input type="date" id="pay-date" class="form-control" max="{{ now()->toDateString() }}">
                <div class="error-text" id="pay-date-error" style="color:var(--red);font-size:11px;margin-top:4px;"></div>
            </div>
            <div class="form-group">
                <label for="pay-description" class="form-label">ملاحظة <span style="color:var(--text-muted);font-weight:400;font-size:10px;">(اختياري)</span></label>
                <textarea id="pay-description" class="form-control" rows="2" maxlength="500" placeholder="وصف مختصر..."></textarea>
                <div class="error-text" id="pay-description-error" style="color:var(--red);font-size:11px;margin-top:4px;"></div>
            </div>
        </div>
        <div class="modal-footer" style="border-top:0;">
            <button class="btn btn-secondary" onclick="closeModal('modal-cc-pay')">إلغاء</button>
            <button class="btn" id="pay-submit-btn" onclick="submitPay()" style="background:#0891b2;color:#fff;">
                <span id="pay-submit-spinner" class="spin" style="display:none;width:14px;height:14px;border-width:2px;"></span>
                تأكيد الدفع
            </button>
        </div>
    </div>
</div>

{{-- ── Receive Modal ─────────────────────────────────────────── --}}
<div class="modal-overlay" id="modal-cc-receive">
    <div class="modal">
        <div class="modal-header" style="background:rgba(5,150,105,.08);border-bottom:0;">
            <h3 style="color:#059669;">ايصال استلام من مندوب</h3>
            <button class="btn-close" onclick="closeModal('modal-cc-receive')">✕</button>
        </div>
        <div class="modal-body">
            <div id="rcv-global-error" style="background:var(--red-light);color:var(--red-dark);padding:10px;border-radius:8px;margin-bottom:12px;display:none;font-size:12px;"></div>
            <div class="form-group">
                <label for="rcv-delivery-id" class="form-label">المندوب <span style="color:var(--red)">*</span></label>
                <select id="rcv-delivery-id" class="form-select">
                    <option value="">اختر مندوب...</option>
                    @foreach($deliveries as $d)
                        <option value="{{ $d->id }}">{{ $d->name }}</option>
                    @endforeach
                </select>
                <div class="error-text" id="rcv-delivery-id-error" style="color:var(--red);font-size:11px;margin-top:4px;"></div>
            </div>
            <div class="form-group">
                <label for="rcv-amount" class="form-label">المبلغ <span style="color:var(--red)">*</span></label>
                <div style="display:flex;">
                    <input type="number" id="rcv-amount" class="form-control" placeholder="0.00" min="0.01" step="0.01" max="9999999.99" style="border-radius:0 8px 8px 0;flex:1;">
                    <span style="background:var(--input-bg);padding:8px 11px;border-radius:8px 0 0 8px;font-size:12px;color:var(--text-muted);border:1px solid var(--border);border-right:none;">ج.م</span>
                </div>
                <div class="error-text" id="rcv-amount-error" style="color:var(--red);font-size:11px;margin-top:4px;"></div>
            </div>
            <div class="form-group">
                <label for="rcv-date" class="form-label">التاريخ <span style="color:var(--text-muted);font-weight:400;font-size:10px;">(اختياري)</span></label>
                <input type="date" id="rcv-date" class="form-control" max="{{ now()->toDateString() }}">
                <div class="error-text" id="rcv-date-error" style="color:var(--red);font-size:11px;margin-top:4px;"></div>
            </div>
            <div class="form-group">
                <label for="rcv-description" class="form-label">ملاحظة <span style="color:var(--text-muted);font-weight:400;font-size:10px;">(اختياري)</span></label>
                <textarea id="rcv-description" class="form-control" rows="2" maxlength="500" placeholder="وصف مختصر..."></textarea>
                <div class="error-text" id="rcv-description-error" style="color:var(--red);font-size:11px;margin-top:4px;"></div>
            </div>
        </div>
        <div class="modal-footer" style="border-top:0;">
            <button class="btn btn-secondary" onclick="closeModal('modal-cc-receive')">إلغاء</button>
            <button class="btn" id="rcv-submit-btn" onclick="submitReceive()" style="background:#059669;color:#fff;">
                <span id="rcv-submit-spinner" class="spin" style="display:none;width:14px;height:14px;border-width:2px;"></span>
                تأكيد الاستلام
            </button>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    // ── Filters ──────────────────────────────────────────────
    function getFilters() {
        return {
            from: document.getElementById('w-filter-from').value || null,
            to:   document.getElementById('w-filter-to').value || null,
            delivery_id: document.getElementById('w-filter-delivery').value || null,
        };
    }

    // ── Fetch statement ──────────────────────────────────────
    async function fetchStatement() {
        const tbody = document.getElementById('w-tbody');
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:40px;"><div class="spin" style="margin:0 auto 10px;"></div>جاري التحميل...</td></tr>';

        try {
            const f = getFilters();
            const params = {};
            if (f.from) params.from = f.from;
            if (f.to) params.to = f.to;
            if (f.delivery_id) params.delivery_id = f.delivery_id;

            const res = await axios.get('/callcenter/wallet/statement', { params });
            const { summary, transactions } = res.data;

            // KPIs
            document.getElementById('w-kpi-debit').textContent = summary.total_debit;
            document.getElementById('w-kpi-credit').textContent = summary.total_credit;
            document.getElementById('w-kpi-balance').textContent = summary.current_balance;

            // Table
            const badge = document.getElementById('w-total-badge');
            badge.textContent = transactions.length + ' عملية';

            if (!transactions.length) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:50px;">لا توجد عمليات</td></tr>';
                return;
            }

            tbody.innerHTML = transactions.map(tx => `
                <tr>
                    <td style="color:var(--text-muted);font-size:12px; text-align:center;">${tx.id}</td>
                    <td style="text-align:center;">${formatDate(tx.transaction_date)}</td>
                    <td style="font-size:12px; text-align:right;">${escHtml(tx.description)}</td>
                    <td style="color:var(--success);font-weight:700; text-align:center;">${tx.debit || '—'}</td>
                    <td style="color:var(--red);font-weight:700; text-align:center;">${tx.credit || '—'}</td>
                    <td style="font-weight:700;color:var(--yellow); text-align:center;">${tx.balance_after}</td>
                </tr>
            `).join('');
        } catch (e) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:var(--red);padding:40px;">حدث خطأ أثناء تحميل البيانات</td></tr>';
            console.error('Statement fetch error', e);
        }
    }

    // ── Filter actions ───────────────────────────────────────
    window.wApplyFilters = function () { fetchStatement(); };
    window.wResetFilters = function () {
        document.getElementById('w-filter-from').value = '';
        document.getElementById('w-filter-to').value = '';
        document.getElementById('w-filter-delivery').value = '';
        fetchStatement();
    };

    // ── Modals ───────────────────────────────────────────────
    window.openPayModal = function () { resetFields('pay'); openModal('modal-cc-pay'); };
    window.openReceiveModal = function () { resetFields('rcv'); openModal('modal-cc-receive'); };

    function resetFields(prefix) {
        ['delivery-id', 'amount', 'date', 'description'].forEach(f => {
            const el = document.getElementById(`${prefix}-${f}`);
            if (el) el.value = '';
        });
        clearErrors(prefix);
    }

    function clearErrors(prefix) {
        ['delivery-id', 'amount', 'date', 'description'].forEach(f => {
            const inp = document.getElementById(`${prefix}-${f}`);
            const err = document.getElementById(`${prefix}-${f}-error`);
            if (inp) inp.style.borderColor = 'var(--border)';
            if (err) err.textContent = '';
        });
        const ge = document.getElementById(`${prefix}-global-error`);
        if (ge) { ge.textContent = ''; ge.style.display = 'none'; }
    }

    async function submitTransfer(prefix, endpoint, modalId, successMsg) {
        clearErrors(prefix);
        const btn = document.getElementById(`${prefix}-submit-btn`);
        const spinner = document.getElementById(`${prefix}-submit-spinner`);
        if (btn) btn.disabled = true;
        if (spinner) spinner.style.display = 'inline-block';

        const payload = {
            delivery_id: document.getElementById(`${prefix}-delivery-id`).value || null,
            amount:      document.getElementById(`${prefix}-amount`).value,
            description: document.getElementById(`${prefix}-description`).value.trim() || null,
            date:        document.getElementById(`${prefix}-date`).value || null,
        };

        try {
            const res = await axios.post(endpoint, payload);
            resetFields(prefix);
            closeModal(modalId);
            if (typeof showSuccess === 'function') showSuccess(res.data.message || successMsg);
            fetchStatement();
        } catch (error) {
            if (error.response?.status === 422) {
                const errors = error.response.data.errors || {};
                const map = { delivery_id: 'delivery-id', amount: 'amount', description: 'description', date: 'date' };
                Object.entries(errors).forEach(([field, msgs]) => {
                    const id = map[field] ?? field;
                    const inp = document.getElementById(`${prefix}-${id}`);
                    const err = document.getElementById(`${prefix}-${id}-error`);
                    if (inp) inp.style.borderColor = 'var(--red)';
                    if (err) err.textContent = msgs[0];
                });
            } else {
                const ge = document.getElementById(`${prefix}-global-error`);
                if (ge) {
                    ge.textContent = error.response?.data?.message || 'حدث خطأ غير متوقع.';
                    ge.style.display = 'block';
                }
            }
        } finally {
            if (btn) btn.disabled = false;
            if (spinner) spinner.style.display = 'none';
        }
    }

    window.submitPay = function () {
        submitTransfer('pay', '/callcenter/wallet/pay-delivery', 'modal-cc-pay', 'تم الدفع بنجاح ✓');
    };
    window.submitReceive = function () {
        submitTransfer('rcv', '/callcenter/wallet/receive-delivery', 'modal-cc-receive', 'تم الاستلام بنجاح ✓');
    };

    // ── Helpers ──────────────────────────────────────────────
    function escHtml(s) {
        if (s == null) return '—';
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function formatDate(ymd) {
        if (!ymd) return '—';
        const [y, m, d] = ymd.split('-');
        return `${d}/${m}/${y}`;
    }

    // ── Boot ─────────────────────────────────────────────────
    fetchStatement();
})();
</script>
