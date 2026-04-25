{{-- Reserve delivery wallet statement partial --}}
<style>
    .w-filter-bar { display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end; margin-bottom:18px; }
    .w-card { background:#fff; border:1px solid var(--border-color); border-radius:14px; padding:18px; }
    .w-kpi-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:18px; }
    .w-kpi { background:#fff; border:1px solid var(--border-color); border-radius:12px; padding:16px; position:relative; overflow:hidden; }
    .w-kpi::before { content:''; position:absolute; top:0; right:0; width:4px; height:100%; background:var(--primary); border-radius:0 12px 12px 0; }
    .w-kpi.green::before { background:var(--success); }
    .w-kpi.red::before { background:var(--secondary); }
    .w-kpi-label { font-size:11px; color:var(--text-muted); font-weight:600; margin-bottom:4px; }
    .w-kpi-value { font-size:22px; font-weight:800; color:var(--text-dark); }
    .w-kpi-sub { font-size:10px; color:var(--text-muted); }
    .w-table-wrap { overflow-x:auto; border-radius:10px; border:1px solid var(--border-color); }
    .w-table { width:100%; border-collapse:collapse; }
    .w-table thead th { background:#f9fafb; padding:10px 14px; font-size:12px; font-weight:700; color:var(--text-muted); text-align:right; white-space:nowrap; border-bottom:1px solid var(--border-color); }
    .w-table tbody td { padding:10px 14px; font-size:13px; border-bottom:1px solid var(--border-color); vertical-align:middle; }
    .w-table tbody tr:last-child td { border-bottom:none; }
    .w-table tbody tr:hover { background:rgba(245,158,11,.04); }
    .w-btn { display:inline-flex; align-items:center; gap:5px; padding:7px 16px; border-radius:8px; font-family:'Cairo',sans-serif; font-size:13px; font-weight:600; cursor:pointer; border:none; transition:all .2s; }
    .w-btn-primary { background:var(--primary); color:#000; }
    .w-btn-secondary { background:#e5e7eb; color:var(--text-dark); }
    .w-input { background:#fff; border:1px solid var(--border-color); border-radius:8px; padding:8px 11px; color:var(--text-dark); font-family:'Cairo',sans-serif; font-size:13px; outline:none; }
    .w-input:focus { border-color:var(--primary); }
    .w-label { display:block; font-size:11px; font-weight:700; color:var(--text-muted); margin-bottom:4px; }
    @media (max-width:767px) {
        .w-kpi-grid { grid-template-columns:1fr; }
        .w-kpi-value { font-size:20px; }
    }
</style>

<h2 style="font-size:18px;font-weight:700;margin-bottom:16px;">كشف حسابي</h2>

{{-- Filters --}}
<div class="w-card" style="margin-bottom:16px;">
    <div class="w-filter-bar">
        <div>
            <span class="w-label">من تاريخ</span>
            <input type="date" id="rw-from" class="w-input">
        </div>
        <div>
            <span class="w-label">إلى تاريخ</span>
            <input type="date" id="rw-to" class="w-input">
        </div>
        <button class="w-btn w-btn-primary" onclick="rwApply()">عرض</button>
        <button class="w-btn w-btn-secondary" onclick="rwReset()">تصفير</button>
    </div>
</div>

{{-- KPIs --}}
<div class="w-kpi-grid">
    <div class="w-kpi green">
        <div class="w-kpi-label">إجمالي المدين (ما استلمته)</div>
        <div class="w-kpi-value" id="rw-kpi-debit" style="color:var(--success);">—</div>
        <div class="w-kpi-sub">ج.م</div>
    </div>
    <div class="w-kpi red">
        <div class="w-kpi-label">إجمالي الدائن (ما دفعته)</div>
        <div class="w-kpi-value" id="rw-kpi-credit" style="color:var(--secondary);">—</div>
        <div class="w-kpi-sub">ج.م</div>
    </div>
    <div class="w-kpi">
        <div class="w-kpi-label">الرصيد الحالي</div>
        <div class="w-kpi-value" id="rw-kpi-balance" style="color:var(--primary);">—</div>
        <div class="w-kpi-sub">ج.م</div>
    </div>
</div>

{{-- Transactions --}}
<div class="w-card" style="padding:0;">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-bottom:1px solid var(--border-color);">
        <span style="font-size:14px;font-weight:700;">سجل العمليات</span>
        <span class="badge" id="rw-badge" style="background:#e5e7eb;color:#475569;">—</span>
    </div>
    <div class="w-table-wrap">
        <table class="w-table">
            <thead>
                <tr>
                    <th style="text-align: center;">رقم العملية</th>
                    <th style="text-align: center;">التاريخ</th>
                    <th style="text-align: right;">التعريف / الملاحظة</th>
                    <th style="text-align: center;">مدين</th>
                    <th style="text-align: center;">دائن</th>
                    <th style="text-align: center;">الرصيد</th>
                </tr>
            </thead>
            <tbody id="rw-tbody">
                <tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:40px;">جاري التحميل...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
(function(){
    'use strict';

    const BASE = '/reserve/wallet/statement';

    async function fetchStatement() {
        const tbody = document.getElementById('rw-tbody');
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:40px;">جاري التحميل...</td></tr>';
        try {
            const params = {};
            const f = document.getElementById('rw-from').value;
            const t = document.getElementById('rw-to').value;
            if (f) params.from = f;
            if (t) params.to = t;

            const res = await axios.get(BASE, { params });
            const { summary, transactions } = res.data;

            document.getElementById('rw-kpi-debit').textContent = summary.total_debit;
            document.getElementById('rw-kpi-credit').textContent = summary.total_credit;
            document.getElementById('rw-kpi-balance').textContent = summary.current_balance;
            document.getElementById('rw-badge').textContent = transactions.length + ' عملية';

            if (!transactions.length) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:40px;">لا توجد عمليات</td></tr>';
                return;
            }

            tbody.innerHTML = transactions.map(tx => `
                <tr>
                    <td style="font-size:12px;text-align: center;">${tx.id}</td>
                    <td style="text-align: center;">${fmtDate(tx.transaction_date)}</td>
                    <td style="font-size:12px;text-align: right;">${esc(tx.description)}</td>
                    <td style="color:var(--success);font-weight:700;text-align: center;">${tx.debit || '—'}</td>
                    <td style="color:var(--secondary);font-weight:700;text-align: center;">${tx.credit || '—'}</td>
                    <td style="font-weight:700;color:var(--primary);text-align: center;">${tx.balance_after}</td>
                </tr>
            `).join('');
        } catch(e) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:var(--secondary);padding:40px;">حدث خطأ</td></tr>';
        }
    }

    window.rwApply = function(){ fetchStatement(); };
    window.rwReset = function(){
        document.getElementById('rw-from').value = '';
        document.getElementById('rw-to').value = '';
        fetchStatement();
    };

    function esc(s){ return s==null?'—':String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
    function fmtDate(ymd){ if(!ymd) return '—'; const [y,m,d]=ymd.split('-'); return `${d}/${m}/${y}`; }

    fetchStatement();
})();
</script>
