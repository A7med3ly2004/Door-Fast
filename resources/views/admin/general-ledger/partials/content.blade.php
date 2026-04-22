{{--
resources/views/admin/general-ledger/partials/content.blade.php
──────────────────────────────────────────────────────────────
كشف حساب عام — يعرض خزائن جميع المستخدمين مع إمكانية عرض كشف تفصيلي
--}}

{{-- ── Page header ──────────────────────────────────────────────── --}}
<div class="section-header">
    <h2>📊 كشف حساب عام</h2>
</div>

{{-- ── Filter Bar ────────────────────────────────────────────────── --}}
<div class="card" style="margin-bottom:20px;">
    <div class="filter-bar" style="margin-bottom:0;">
        <div>
            <div class="form-label" style="margin-bottom:4px;">من تاريخ</div>
            <input type="date" id="gl-filter-from" class="form-control">
        </div>
        <div>
            <div class="form-label" style="margin-bottom:4px;">إلى تاريخ</div>
            <input type="date" id="gl-filter-to" class="form-control">
        </div>
        <div style="display:flex;gap:8px;align-self:flex-end;">
            <button class="btn btn-primary" onclick="glApplyFilters()">🔍 عرض</button>
            <button class="btn btn-secondary" onclick="glResetFilters()" title="إعادة ضبط">↺</button>
        </div>
    </div>
</div>

{{-- ── Users Table ───────────────────────────────────────────────── --}}
<div class="card" style="padding:0;position:relative;">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--border);">
        <span style="font-size:15px;font-weight:700;">خزائن المستخدمين</span>
        <span class="badge badge-gray" id="gl-total-badge">—</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>الاسم</th>
                    <th>الدور</th>
                    <th>إجمالي المدين</th>
                    <th>إجمالي الدائن</th>
                    <th>الرصيد الحالي</th>
                    <th style="text-align:center;">الإجراءات</th>
                </tr>
            </thead>
            <tbody id="gl-tbody">
                <tr>
                    <td colspan="6" style="text-align:center;color:var(--text-muted);padding:60px;">
                        <div class="spin" style="width:24px;height:24px;border-width:3px;margin:0 auto 10px;"></div>
                        جاري التحميل...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

{{-- ── User Statement Modal ──────────────────────────────────────── --}}
<div class="modal-overlay" id="modal-user-statement">
    <div class="modal modal-xl">
        <div class="modal-header">
            <h3>📋 كشف حساب — <span id="stmt-user-name"></span></h3>
            <button class="btn-close" onclick="closeModal('modal-user-statement')">✕</button>
        </div>
        <div class="modal-body" id="stmt-modal-body">
            <div style="text-align:center;padding:40px;color:var(--text-muted);">
                <div class="spin" style="width:30px;height:30px;border-width:3px;margin:0 auto 12px;"></div>
                جاري التحميل...
            </div>
        </div>
    </div>
</div>


<script>
(function () {
    'use strict';

    // ── Filters ──────────────────────────────────────────────────
    function getFilters() {
        return {
            from: document.getElementById('gl-filter-from').value || null,
            to:   document.getElementById('gl-filter-to').value || null,
        };
    }

    // ── Fetch all users ──────────────────────────────────────────
    async function fetchData() {
        const tbody = document.getElementById('gl-tbody');
        tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:40px;">
            <div class="spin" style="width:24px;height:24px;border-width:3px;margin:0 auto 10px;"></div>
            جاري التحميل...</td></tr>`;

        try {
            const filters = getFilters();
            const params = {};
            if (filters.from) params.from = filters.from;
            if (filters.to) params.to = filters.to;

            const res = await axios.get('/admin/general-ledger/data', { params });
            renderTable(res.data.data);
        } catch (e) {
            tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;color:var(--red);padding:40px;">حدث خطأ أثناء تحميل البيانات</td></tr>`;
            console.error('GL fetch error', e);
        }
    }

    // ── Render table ─────────────────────────────────────────────
    function roleBadge(role, label) {
        const colors = {
            admin:            'badge-yellow',
            callcenter:       'badge-blue',
            delivery:         'badge-green',
            reserve_delivery: 'badge-gray',
        };
        return `<span class="badge ${colors[role] || 'badge-gray'}">${escHtml(label)}</span>`;
    }

    function renderTable(rows) {
        const tbody = document.getElementById('gl-tbody');
        const badge = document.getElementById('gl-total-badge');
        badge.textContent = rows.length + ' مستخدم';

        if (!rows || rows.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:60px;">لا توجد بيانات</td></tr>`;
            return;
        }

        tbody.innerHTML = rows.map(r => `
            <tr style="cursor:pointer;" onclick="openUserStatement(${r.user_id}, '${escAttr(r.name)}')">
                <td><strong>${escHtml(r.name)}</strong></td>
                <td>${roleBadge(r.role, r.role_label)}</td>
                <td style="color:var(--success);font-weight:700;">${r.total_debit}</td>
                <td style="color:var(--red);font-weight:700;">${r.total_credit}</td>
                <td style="font-weight:700;color:var(--yellow);">${r.balance}</td>
                <td style="text-align:center;">
                    <button class="btn btn-sm btn-info" onclick="event.stopPropagation();openUserStatement(${r.user_id}, '${escAttr(r.name)}')" title="كشف حساب">📋 كشف</button>
                </td>
            </tr>
        `).join('');
    }

    // ── User statement modal ─────────────────────────────────────
    window.openUserStatement = async function (userId, name) {
        document.getElementById('stmt-user-name').textContent = name;
        document.getElementById('stmt-modal-body').innerHTML = `
            <div style="text-align:center;padding:40px;color:var(--text-muted);">
                <div class="spin" style="width:30px;height:30px;border-width:3px;margin:0 auto 12px;"></div>
                جاري التحميل...
            </div>`;
        openModal('modal-user-statement');

        try {
            const filters = getFilters();
            const params = {};
            if (filters.from) params.from = filters.from;
            if (filters.to) params.to = filters.to;

            const res = await axios.get(`/admin/general-ledger/user/${userId}`, { params });
            renderStatement(res.data);
        } catch (e) {
            document.getElementById('stmt-modal-body').innerHTML =
                '<div style="color:var(--red);text-align:center;padding:30px;">حدث خطأ أثناء تحميل كشف الحساب</div>';
            console.error('Statement fetch error', e);
        }
    };

    function renderStatement(data) {
        const s = data.summary;
        const txs = data.transactions;

        let transactionsHtml = '';
        if (txs.length === 0) {
            transactionsHtml = `<tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:40px;">لا توجد عمليات في هذه الفترة</td></tr>`;
        } else {
            transactionsHtml = txs.map(tx => `
                <tr>
                    <td style="color:var(--text-muted);font-size:12px;">${tx.id}</td>
                    <td>${formatDate(tx.transaction_date)}</td>
                    <td style="font-size:12px;">${escHtml(tx.description)}</td>
                    <td style="color:var(--success);font-weight:700;">${tx.debit || '—'}</td>
                    <td style="color:var(--red);font-weight:700;">${tx.credit || '—'}</td>
                    <td style="font-weight:700;color:var(--yellow);">${tx.balance_after}</td>
                </tr>
            `).join('');
        }

        document.getElementById('stmt-modal-body').innerHTML = `
            <div class="kpi-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:20px;">
                <div class="kpi-card green">
                    <div class="kpi-label">إجمالي المدين</div>
                    <div class="kpi-value" style="font-size:20px;color:var(--success);">${s.total_debit}</div>
                    <div class="kpi-sub">ج.م</div>
                </div>
                <div class="kpi-card red">
                    <div class="kpi-label">إجمالي الدائن</div>
                    <div class="kpi-value" style="font-size:20px;color:var(--red);">${s.total_credit}</div>
                    <div class="kpi-sub">ج.م</div>
                </div>
                <div class="kpi-card blue">
                    <div class="kpi-label">صافي الفترة</div>
                    <div class="kpi-value" style="font-size:20px;">${s.period_balance}</div>
                    <div class="kpi-sub">ج.م</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">الرصيد الحالي</div>
                    <div class="kpi-value" style="font-size:20px;color:var(--yellow);">${s.current_balance}</div>
                    <div class="kpi-sub">ج.م</div>
                </div>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>التاريخ</th>
                            <th>التعريف / الملاحظة</th>
                            <th>مدين</th>
                            <th>دائن</th>
                            <th>الرصيد</th>
                        </tr>
                    </thead>
                    <tbody>${transactionsHtml}</tbody>
                </table>
            </div>
        `;
    }

    // ── Filter actions ───────────────────────────────────────────
    window.glApplyFilters = function () { fetchData(); };
    window.glResetFilters = function () {
        document.getElementById('gl-filter-from').value = '';
        document.getElementById('gl-filter-to').value = '';
        fetchData();
    };

    // ── Helpers ──────────────────────────────────────────────────
    function escHtml(str) {
        if (str == null) return '—';
        return String(str)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
    function escAttr(str) {
        return String(str).replace(/'/g, "\\'").replace(/"/g, '&quot;');
    }
    function formatDate(ymd) {
        if (!ymd) return '—';
        const [y, m, d] = ymd.split('-');
        return `${d}/${m}/${y}`;
    }

    // ── Boot ─────────────────────────────────────────────────────
    fetchData();

})();
</script>
