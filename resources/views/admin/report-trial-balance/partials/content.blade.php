<style>
    /* إخفاء سكرول بار - مُحسَّن: بدون محدد * العام */
    .page-content::-webkit-scrollbar {
        display: none;
    }

    .page-content {
        scrollbar-width: none;
        -ms-overflow-style: none;
    }
</style>

<div class="filter-bar" style="background:var(--card-bg);border:1px solid var(--border);border-radius:14px; padding: 10px;
    margin-bottom: 25px; gap: 10px; align-items: flex-end;">
    <div>
        <label class="form-label" style="font-size: 12px; margin-bottom: 2px;">بحث</label>
        <input type="text" id="rtb-search" class="form-control" placeholder="بحث بالاسم أو كود الموظف"
            style="min-width: 200px;">
    </div>
    <div>
        <label class="form-label" style="font-size: 12px; margin-bottom: 2px;">تاريخ من</label>
        <input type="date" id="rtb-from" class="form-control" placeholder="من تاريخ">
    </div>
    <div>
        <label class="form-label" style="font-size: 12px; margin-bottom: 2px;">تاريخ إلى</label>
        <input type="date" id="rtb-to" class="form-control" placeholder="إلى تاريخ">
    </div>
    <div>
        <label class="form-label" style="font-size: 12px; margin-bottom: 2px;">الوظيفة</label>
        <div class="relative group" style="min-width:140px; z-index: 10;">
            <div class="form-control"
                style="cursor:pointer; display:flex; justify-content:space-between; align-items:center;">
                <span id="label-rtb-role">كل الوظائف</span>
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </div>
            <input type="hidden" id="rtb-role" value="">
            <div class="absolute top-full right-0 w-full opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all shadow-lg rounded-md mt-1 overflow-hidden"
                style="border:1px solid var(--border); background-color: var(--text); max-height:200px; overflow-y:auto;">
                <div class="px-3 py-2 cursor-pointer hover:bg-green-50 hover:text-green-700 text-sm transition-colors text-gray-800"
                    onclick="selectDropdown('rtb-role', '', 'كل الوظائف')">كل الوظائف</div>
                <div class="px-3 py-2 cursor-pointer hover:bg-green-50 hover:text-green-700 text-sm transition-colors text-gray-800"
                    onclick="selectDropdown('rtb-role', 'admin', 'مدير')">مدير</div>
                <div class="px-3 py-2 cursor-pointer hover:bg-green-50 hover:text-green-700 text-sm transition-colors text-gray-800"
                    onclick="selectDropdown('rtb-role', 'callcenter', 'كول سنتر')">كول سنتر</div>
                <div class="px-3 py-2 cursor-pointer hover:bg-green-50 hover:text-green-700 text-sm transition-colors text-gray-800"
                    onclick="selectDropdown('rtb-role', 'delivery', 'مندوب')">مندوب</div>
                <div class="px-3 py-2 cursor-pointer hover:bg-green-50 hover:text-green-700 text-sm transition-colors text-gray-800"
                    onclick="selectDropdown('rtb-role', 'expense', 'مصروف')">مصروف</div>
                <div class="px-3 py-2 cursor-pointer hover:bg-green-50 hover:text-green-700 text-sm transition-colors text-gray-800"
                    onclick="selectDropdown('rtb-role', 'safe', 'الخزنة')">الخزنة</div>
                <div class="px-3 py-2 cursor-pointer hover:bg-green-50 hover:text-green-700 text-sm transition-colors text-gray-800"
                    onclick="selectDropdown('rtb-role', 'discount', 'خصومات')">خصومات</div>
            </div>
        </div>
    </div>
    <div style="display: flex; gap: 5px; margin-bottom: 2px;">
        <button class="btn btn-primary" onclick="loadTrialBalance()"
            style="padding: 6px 24px;border-radius:8px;background:var(--yellow);border:none;color:#000;font-weight:700;cursor:pointer;">
            بحث
        </button>
        <button class="btn btn-success" onclick="exportTrialBalanceExcel()"
            style="background:#217346;color:#fff;padding:6px 24px;border-radius:8px;border:none;font-weight:700;cursor:pointer;">تصدير
            Excel</button>
    </div>
</div>

<div id="rtb-period-badge" style="margin-bottom:12px; text-align:right;"></div>

<div class="kpi-grid" id="rtb-kpis" style="margin-top: 10px; grid-template-columns: repeat(3, 1fr);">
    <div class="kpi-card yellow">
        <div class="kpi-label">الخزينة الرئيسية</div>
        <div class="kpi-value spin"></div>
    </div>
    <div class="kpi-card blue">
        <div class="kpi-label">إجمالي المديرين</div>
        <div class="kpi-value spin"></div>
    </div>
    <div class="kpi-card red">
        <div class="kpi-label">إجمالي المصروفات</div>
        <div class="kpi-value spin"></div>
    </div>
    <div class="kpi-card cyan">
        <div class="kpi-label">إجمالي الكول سنتر</div>
        <div class="kpi-value spin"></div>
    </div>
    <div class="kpi-card green">
        <div class="kpi-label">إجمالي المناديب</div>
        <div class="kpi-value spin"></div>
    </div>
    <div class="kpi-card red">
        <div class="kpi-label">إجمالي الخصومات</div>
        <div class="kpi-value spin"></div>
    </div>
</div>

<div class="card" style="background:var(--card-bg);border:1px solid var(--border);border-radius:14px;overflow:hidden;">
    <div class="table-responsive" style="overflow-x:auto;">
        <table class="table" style="width:100%;border-collapse:collapse;text-align:center;">
            <thead>
                <tr style="background:rgba(255,255,255,0.02);border-bottom:1px solid var(--border);">
                    <th style="padding:16px;font-size:16px;color:var(--text-muted); text-align: center;">المستخدم /
                        الكيان</th>
                    <th style="padding:16px;font-size:16px;color:var(--text-muted); text-align: center;">الدور</th>
                    <th style="padding:16px;font-size:16px;color:var(--text-muted); text-align: center;">اجمالي الصندوق
                        (اجمالي الرصيد
                        الحالي لكل واحد)</th>
                </tr>
            </thead>
            <tbody id="rtb-tbody">
                <tr>
                    <td colspan="3" style="text-align:center;padding:20px;">الرجاء تحديد فترة والبحث...</td>
                </tr>
            </tbody>
            <tfoot id="rtb-tfoot"
                style="background:rgba(255,255,255,0.05);font-weight:bold;border-top:2px solid var(--border);">
            </tfoot>
        </table>
    </div>
</div>
</div>

<script>
    function formatMoneyEn(val) {
        return parseFloat(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2 }) + ' ج';
    }

    var rtbCurrentData = null;

    function renderTable() {
        if (!rtbCurrentData) return;

        const searchQ = document.getElementById('rtb-search').value.trim().toLowerCase();
        const roleQ = document.getElementById('rtb-role').value;
        const tbody = document.getElementById('rtb-tbody');
        const tfoot = document.getElementById('rtb-tfoot');

        let allRows = [];
        allRows.push({ type: 'safe', name: 'الخزينة الرئيسية', roleLabel: '<span class="badge badge-yellow">خزينة</span>', balance: rtbCurrentData.main_safe, code: '' });
        if (rtbCurrentData.admin_rows) {
            rtbCurrentData.admin_rows.forEach(a => {
                allRows.push({ type: 'admin', name: a.name, roleLabel: '<span class="badge badge-blue">مدير</span>', balance: a.balance, code: a.code || '' });
            });
        }
        allRows.push({ type: 'expense', name: 'إجمالي المصروفات', roleLabel: '<span class="badge badge-red">مصروف</span>', balance: rtbCurrentData.total_expenses, code: '' });
        allRows.push({ type: 'discount', name: 'إجمالي الخصومات', roleLabel: '<span class="badge badge-gray">نظام</span>', balance: rtbCurrentData.total_discounts, code: '' });

        rtbCurrentData.callcenter_rows.forEach(cc => {
            allRows.push({ type: 'callcenter', name: cc.name, roleLabel: '<span class="badge badge-cyan">كول سنتر</span>', balance: cc.balance, code: cc.code || '' });
        });

        rtbCurrentData.delivery_rows.forEach(d => {
            allRows.push({ type: 'delivery', name: d.name, roleLabel: '<span class="badge badge-green">مندوب</span>', balance: d.balance, code: d.code || '' });
        });


        const filteredRows = allRows.filter(row => {
            let matchSearch = true;
            if (searchQ) {
                matchSearch = row.name.toLowerCase().includes(searchQ) || (row.code && row.code.toLowerCase().includes(searchQ));
            }
            let matchRole = true;
            if (roleQ) {
                matchRole = row.type === roleQ;
            }
            return matchSearch && matchRole;
        });

        let html = '';

        if (filteredRows.length === 0) {
            html = '<tr><td colspan="3" style="text-align:center;padding:20px;">لا توجد نتائج مطابقة</td></tr>';
        } else {
            filteredRows.forEach(row => {
                let color = '';
                if (row.type === 'callcenter' && row.balance > 0) color = 'color:var(--red);font-weight:bold;';
                if (row.type === 'delivery' && row.balance > 0) color = 'color:var(--red);font-weight:bold;';

                let nameHtml = row.name;
                if (row.code) nameHtml += ` <small style="color:var(--text-muted);font-size:11px;">(${row.code})</small>`;

                html += `<tr>
                    <td style="padding:16px;">${nameHtml}</td>
                    <td style="padding:16px;">${row.roleLabel}</td>
                    <td style="padding:16px;${color}" dir="ltr">${formatMoneyEn(row.balance)}</td>
                 </tr>`;
            });
        }
        tbody.innerHTML = html;
        tfoot.innerHTML = '';
    }

    // Attach local filtering triggers
    document.getElementById('rtb-search').addEventListener('input', renderTable);
    document.getElementById('rtb-role').addEventListener('change', renderTable);

    async function loadTrialBalance() {
        const from = document.getElementById('rtb-from').value;
        const to = document.getElementById('rtb-to').value;
        const tbody = document.getElementById('rtb-tbody');
        const kpis = document.getElementById('rtb-kpis');

        tbody.innerHTML = '<tr><td colspan="3" style="text-align:center;padding:40px;"><div class="spin"></div></td></tr>';

        try {
            const res = await axios.get(`{{ route('admin.report-trial-balance.data') }}`, {
                params: { from, to }
            });

            rtbCurrentData = res.data;
            const data = rtbCurrentData;
            const period = data.period;

            if (data.is_always) {
                document.getElementById('rtb-from').value = '';
                document.getElementById('rtb-to').value = '';
            } else if (period.from && period.to) {
                document.getElementById('rtb-from').value = period.from;
                document.getElementById('rtb-to').value = period.to;
            }



            const colHeader = document.querySelector('#rtb-tbody').closest('table').querySelector('thead th:last-child');
            if (colHeader) {
                colHeader.textContent = data.is_always
                    ? 'الرصيد الحالي'
                    : `صافي الفترة (${period.from} → ${period.to})`;
            }

            renderTable();

            let totalCCBalance = 0;
            data.callcenter_rows.forEach(cc => { totalCCBalance += cc.balance; });

            let totalDelBalance = 0;
            data.delivery_rows.forEach(d => { totalDelBalance += d.balance; });

            let totalAdminBalance = 0;
            if (data.admin_rows) {
                data.admin_rows.forEach(a => { totalAdminBalance += a.balance; });
            }

            // Update KPIs
            kpis.style.gridTemplateColumns = 'repeat(3, 1fr)';
            kpis.innerHTML = `
                <div class="kpi-card yellow">
                    <div class="kpi-label">الخزينة الرئيسية</div>
                    <div class="kpi-value" dir="ltr">${formatMoneyEn(data.main_safe)}</div>
                </div>
                <div class="kpi-card blue">
                    <div class="kpi-label">إجمالي المديرين</div>
                    <div class="kpi-value" dir="ltr">${formatMoneyEn(totalAdminBalance)}</div>
                </div>
                <div class="kpi-card red">
                    <div class="kpi-label">إجمالي المصروفات</div>
                    <div class="kpi-value" dir="ltr">${formatMoneyEn(data.total_expenses)}</div>
                </div>
                <div class="kpi-card cyan">
                    <div class="kpi-label">إجمالي الكول سنتر</div>
                    <div class="kpi-value" dir="ltr">${formatMoneyEn(totalCCBalance)}</div>
                </div>
                <div class="kpi-card green">
                    <div class="kpi-label">إجمالي المناديب</div>
                    <div class="kpi-value" dir="ltr">${formatMoneyEn(totalDelBalance)}</div>
                </div>
                <div class="kpi-card red">
                    <div class="kpi-label">إجمالي الخصومات</div>
                    <div class="kpi-value" dir="ltr">${formatMoneyEn(data.total_discounts)}</div>
                </div>
            `;

        } catch (error) {
            console.error(error);
            const tbody = document.getElementById('rtb-tbody');
            tbody.innerHTML = '<tr><td colspan="3" style="text-align:center;padding:20px;color:var(--red);">حدث خطأ أثناء جلب البيانات</td></tr>';
            showError('فشل تحميل ميزان المراجعة');
        }
    }

    // Auto load on init
    setTimeout(loadTrialBalance, 100);

    window.exportTrialBalanceExcel = function () {
        if (!rtbCurrentData) { if (typeof showError === 'function') showError('يرجى تحميل البيانات أولاً'); return; }

        const allRows = [];
        allRows.push({ name: 'الخزينة الرئيسية', code: '', role: 'خزينة', balance: rtbCurrentData.main_safe });
        if (rtbCurrentData.admin_rows) {
            rtbCurrentData.admin_rows.forEach(r => allRows.push({ ...r, role: 'مدير' }));
        }
        allRows.push({ name: 'إجمالي المصروفات', code: '', role: 'مصروف', balance: rtbCurrentData.total_expenses });
        allRows.push({ name: 'إجمالي الخصومات', code: '', role: 'خصم', balance: rtbCurrentData.total_discounts });
        rtbCurrentData.callcenter_rows.forEach(r => allRows.push({ ...r, role: 'كول سنتر' }));
        rtbCurrentData.delivery_rows.forEach(r => allRows.push({ ...r, role: 'مندوب' }));


        const from = document.getElementById('rtb-from').value;
        const to = document.getElementById('rtb-to').value;

        const columns = [
            { header: 'الاسم', key: 'name', width: 24 },
            { header: 'الكود', key: 'code', width: 12 },
            { header: 'الدور', key: 'role', width: 16 },
            { header: 'الرصيد', key: 'balance', width: 16 },
        ];

        const filename = 'trial-balance'
            + (from ? '-' + from : '')
            + (to ? '-to-' + to : '')
            + '-' + new Date().toISOString().slice(0, 10);

        exportToExcel(allRows, columns, filename, 'ميزان المراجعة');
        if (typeof showSuccess === 'function') showSuccess('تم التصدير');
    };
</script>