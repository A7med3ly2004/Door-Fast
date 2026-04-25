<style>
    /* إخفاء سكرول بار من هذه الصفحة */
    ::-webkit-scrollbar { display: none !important; }
    * { scrollbar-width: none !important; -ms-overflow-style: none !important; }
</style>

<div class="filter-bar" style="background:var(--card-bg);border:1px solid var(--border);border-radius:14px;overflow:hidden; padding: 10px;
    margin-bottom: 25px; gap: 10px;">
    <input type="date" id="rtb-from" class="form-control" placeholder="من تاريخ">
    <input type="date" id="rtb-to" class="form-control" placeholder="إلى تاريخ">
    <select id="rtb-role" class="form-select" style="min-width:140px;">
        <option value="">كل الوظائف</option>
        <option value="admin">مدير</option>
        <option value="callcenter">كول سنتر</option>
        <option value="delivery">مندوب</option>
        <option value="expense">مصروف</option>
        <option value="safe">الخزنة</option>
        <option value="discount">خصومات</option>
    </select>
    <input type="text" id="rtb-search" class="form-control" placeholder="بحث بالاسم أو كود الموظف"
        style="min-width: 200px;">
    <button class="btn btn-primary" onclick="loadTrialBalance()"
        style="padding: 6px 24px;border-radius:8px;background:var(--yellow);border:none;color:#000;font-weight:700;cursor:pointer;">
        بحث
    </button>
</div>

<div class="kpi-grid" id="rtb-kpis" style="margin-top: 10px; grid-template-columns: repeat(5, 1fr);">
    <div class="kpi-card blue">
        <div class="kpi-label">الخزينة الرئيسية</div>
        <div class="kpi-value spin"></div>
    </div>
    <div class="kpi-card yellow">
        <div class="kpi-label">إجمالي الكول سنتر</div>
        <div class="kpi-value spin"></div>
    </div>
    <div class="kpi-card red">
        <div class="kpi-label">إجمالي المناديب</div>
        <div class="kpi-value spin"></div>
    </div>
    <div class="kpi-card cyan">
        <div class="kpi-label">إجمالي المديرين</div>
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
                    <th style="padding:16px;font-size:16px;color:var(--text-muted); text-align: center;">المستخدم / الكيان</th>
                    <th style="padding:16px;font-size:16px;color:var(--text-muted); text-align: center;">الدور</th>
                    <th style="padding:16px;font-size:16px;color:var(--text-muted); text-align: center;">اجمالي الصندوق (اجمالي الرصيد
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
        allRows.push({ type: 'safe', name: 'الخزينة الرئيسية', roleLabel: '<span class="badge badge-blue">خزينة</span>', balance: rtbCurrentData.main_safe, code: '' });
        allRows.push({ type: 'expense', name: 'إجمالي المصروفات', roleLabel: '<span class="badge badge-red">مصروف</span>', balance: rtbCurrentData.total_expenses, code: '' });
        allRows.push({ type: 'discount', name: 'إجمالي الخصومات', roleLabel: '<span class="badge badge-gray">نظام</span>', balance: rtbCurrentData.total_discounts, code: '' });

        rtbCurrentData.callcenter_rows.forEach(cc => {
            allRows.push({ type: 'callcenter', name: cc.name, roleLabel: '<span class="badge badge-yellow">كول سنتر</span>', balance: cc.balance, code: cc.code || '' });
        });

        rtbCurrentData.delivery_rows.forEach(d => {
            allRows.push({ type: 'delivery', name: d.name, roleLabel: '<span class="badge badge-green">مندوب</span>', balance: d.balance, code: d.code || '' });
        });

        if (rtbCurrentData.admin_rows) {
            rtbCurrentData.admin_rows.forEach(a => {
                allRows.push({ type: 'admin', name: a.name, roleLabel: '<span class="badge badge-blue">مدير</span>', balance: a.balance, code: a.code || '' });
            });
        }

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
                if (row.type === 'callcenter' && row.balance < 0) color = 'color:var(--red);font-weight:bold;';
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

            if (!from && !to && period) {
                document.getElementById('rtb-from').value = period.from;
                document.getElementById('rtb-to').value = period.to;
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
            kpis.style.gridTemplateColumns = 'repeat(5, 1fr)';
            kpis.innerHTML = `
                <div class="kpi-card blue">
                    <div class="kpi-label">الخزينة الرئيسية</div>
                    <div class="kpi-value" dir="ltr">${formatMoneyEn(data.main_safe)}</div>
                </div>
                <div class="kpi-card yellow">
                    <div class="kpi-label">إجمالي الكول سنتر</div>
                    <div class="kpi-value" dir="ltr">${formatMoneyEn(totalCCBalance)}</div>
                </div>
                <div class="kpi-card red">
                    <div class="kpi-label">إجمالي المناديب</div>
                    <div class="kpi-value" dir="ltr">${formatMoneyEn(totalDelBalance)}</div>
                </div>
                <div class="kpi-card cyan">
                    <div class="kpi-label">إجمالي المديرين</div>
                    <div class="kpi-value" dir="ltr">${formatMoneyEn(totalAdminBalance)}</div>
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
</script>