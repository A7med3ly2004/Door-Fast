    <div class="filter-bar">
        <input type="date" id="rtb-from" class="form-control" placeholder="من تاريخ">
        <input type="date" id="rtb-to" class="form-control" placeholder="إلى تاريخ">
        <select id="rtb-role" class="form-select" style="min-width:140px;">
            <option value="">كل الوظائف</option>
            <option value="callcenter">كول سنتر</option>
            <option value="delivery">مندوب</option>
            <option value="expense">مصروف</option>
            <option value="safe">الخزنة</option>
            <option value="discount">خصومات</option>
        </select>
        <input type="text" id="rtb-search" class="form-control" placeholder="بحث بالاسم أو كود الموظف" style="min-width: 200px;">
        <button class="btn btn-primary" onclick="loadTrialBalance()" style="padding:9px 24px;border-radius:8px;background:var(--yellow);border:none;color:#000;font-weight:700;cursor:pointer;">
            بحث
        </button>
    </div>

    <div class="kpi-grid" id="rtb-kpis">
        <div class="kpi-card blue"><div class="kpi-label">الخزينة الرئيسية</div><div class="kpi-value spin"></div></div>
        <div class="kpi-card yellow"><div class="kpi-label">إجمالي الكول سنتر</div><div class="kpi-value spin"></div></div>
        <div class="kpi-card red"><div class="kpi-label">إجمالي المناديب</div><div class="kpi-value spin"></div></div>
        <div class="kpi-card"><div class="kpi-label">إجمالي الخصومات</div><div class="kpi-value spin"></div></div>
    </div>

    <div class="card" style="background:var(--card-bg);border:1px solid var(--border);border-radius:14px;overflow:hidden;">
        <div class="table-responsive" style="overflow-x:auto;">
            <table class="table" style="width:100%;border-collapse:collapse;text-align:right;">
                <thead>
                    <tr style="background:rgba(255,255,255,0.02);border-bottom:1px solid var(--border);">
                        <th style="padding:16px;font-size:12px;color:var(--text-muted);">المستخدم / الكيان</th>
                        <th style="padding:16px;font-size:12px;color:var(--text-muted);">الدور</th>
                        <th style="padding:16px;font-size:12px;color:var(--text-muted);">اجمالي الصندوق (اجمالي الرصيد الحالي لكل واحد)</th>
                    </tr>
                </thead>
                <tbody id="rtb-tbody">
                    <tr><td colspan="3" style="text-align:center;padding:20px;">الرجاء تحديد فترة والبحث...</td></tr>
                </tbody>
                <tfoot id="rtb-tfoot" style="background:rgba(255,255,255,0.05);font-weight:bold;border-top:2px solid var(--border);">
                </tfoot>
            </table>
        </div>
    </div>
</div>

<script>
    function formatMoneyEn(val) {
        return parseFloat(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2 }) + ' ج';
    }

    let currentData = null;

    function renderTable() {
        if (!currentData) return;
        
        const searchQ = document.getElementById('rtb-search').value.trim().toLowerCase();
        const roleQ = document.getElementById('rtb-role').value;
        const tbody = document.getElementById('rtb-tbody');
        const tfoot = document.getElementById('rtb-tfoot');

        let allRows = [];
        allRows.push({ type: 'safe', name: 'الخزينة الرئيسية', roleLabel: '<span class="badge badge-blue">خزينة</span>', balance: currentData.main_safe, code: '' });
        allRows.push({ type: 'expense', name: 'إجمالي المصروفات', roleLabel: '<span class="badge badge-red">مصروف</span>', balance: currentData.total_expenses, code: '' });
        allRows.push({ type: 'discount', name: 'إجمالي الخصومات', roleLabel: '<span class="badge badge-gray">نظام</span>', balance: currentData.total_discounts, code: '' });

        currentData.callcenter_rows.forEach(cc => {
            allRows.push({ type: 'callcenter', name: cc.name, roleLabel: '<span class="badge badge-yellow">كول سنتر</span>', balance: cc.balance, code: cc.code || '' });
        });

        currentData.delivery_rows.forEach(d => {
            allRows.push({ type: 'delivery', name: d.name, roleLabel: '<span class="badge badge-green">مندوب</span>', balance: d.balance, code: d.code || '' });
        });

        const filteredRows = allRows.filter(row => {
            let matchSearch = true;
            if(searchQ) {
                matchSearch = row.name.toLowerCase().includes(searchQ) || (row.code && row.code.toLowerCase().includes(searchQ));
            }
            let matchRole = true;
            if(roleQ) {
                matchRole = row.type === roleQ;
            }
            return matchSearch && matchRole;
        });

        let html = '';
        let grandTotal = 0;
        
        if(filteredRows.length === 0) {
             html = '<tr><td colspan="3" style="text-align:center;padding:20px;">لا توجد نتائج مطابقة</td></tr>';
        } else {
             filteredRows.forEach(row => {
                 grandTotal += row.balance;
                 let color = '';
                 if(row.type === 'callcenter' && row.balance < 0) color = 'color:var(--red);font-weight:bold;';
                 if(row.type === 'delivery' && row.balance > 0) color = 'color:var(--red);font-weight:bold;';
                 
                 let nameHtml = row.name;
                 if(row.code) nameHtml += ` <small style="color:var(--text-muted);font-size:11px;">(${row.code})</small>`;
                 
                 html += `<tr>
                    <td style="padding:16px;">${nameHtml}</td>
                    <td style="padding:16px;">${row.roleLabel}</td>
                    <td style="padding:16px;${color}" dir="ltr">${formatMoneyEn(row.balance)}</td>
                 </tr>`;
             });
        }
        tbody.innerHTML = html;

        tfoot.innerHTML = `<tr>
            <td style="padding:16px;" colspan="2">الإجمالي للجدول المعروض</td>
            <td style="padding:16px;" dir="ltr">${formatMoneyEn(grandTotal)}</td>
        </tr>`;
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

            currentData = res.data;
            const data = currentData;
            const period = data.period;
            
            if(!from && !to && period) {
                document.getElementById('rtb-from').value = period.from;
                document.getElementById('rtb-to').value = period.to;
            }

            renderTable();

            let totalCCBalance = 0;
            data.callcenter_rows.forEach(cc => { totalCCBalance += cc.balance; });
            
            let totalDelBalance = 0;
            data.delivery_rows.forEach(d => { totalDelBalance += d.balance; });

            // Update KPIs
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
                <div class="kpi-card gray">
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
