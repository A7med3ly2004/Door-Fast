<div class="section-header">
    <h2>تقارير الكول سنتر</h2>
</div>

<div class="card" style="margin-bottom:20px;">
    <div class="filter-bar" style="margin-bottom:0;">
        <div style="flex:1;min-width:200px;">
            <label class="form-label">الموظف <span style="color:var(--red)">*</span></label>
            <div class="relative group" style="z-index: 50;">
                <div class="form-control"
                    style="cursor:pointer; display:flex; justify-content:space-between; align-items:center;">
                    <span id="label-filter-callcenter-id">اختر الموظف</span>
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </div>
                <input type="hidden" id="filter-callcenter-id" value="">
                <div class="absolute top-full right-0 w-full opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all bg-white/80 backdrop-blur shadow-lg rounded-md mt-1 overflow-hidden"
                    style="border:1px solid var(--border); background-color: rgba(255, 255, 255, 0.9); max-height:200px; overflow-y:auto;">
                    <div class="px-3 py-2 cursor-pointer hover:bg-green-50 hover:text-green-700 text-sm transition-colors text-gray-800"
                        onclick="selectDropdown('filter-callcenter-id', '', 'اختر الموظف')">اختر الموظف</div>
                    @foreach($callcenters as $cc)
                        <div class="px-3 py-2 cursor-pointer hover:bg-green-50 hover:text-green-700 text-sm transition-colors text-gray-800"
                            onclick="selectDropdown('filter-callcenter-id', '{{ $cc->id }}', '{{ $cc->name }}')">
                            {{ $cc->name }}
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div>
            <label class="form-label">من تاريخ (اختياري)</label>
            <input type="date" id="filter-from" class="form-control">
        </div>
        <div>
            <label class="form-label">إلى تاريخ (اختياري)</label>
            <input type="date" id="filter-to" class="form-control">
        </div>
        <div style="display:flex;gap:8px;align-self:flex-end;">
            <button class="btn btn-primary" id="search-btn" onclick="loadReport(1)">عرض التقرير</button>
            <button class="btn btn-success" id="export-cc-excel-btn" onclick="exportCCReportExcel()"
                style="background:#217346;color:#fff;display:none;">تصدير Excel</button>
            <span id="report-spinner" class="spin" style="display:none;align-self:center;margin-right:10px;"></span>
        </div>
    </div>
</div>

<div id="report-results" style="display:none;">
    <div class="card" style="margin-bottom:20px;padding:24px;">
        <h3 id="report-agent-name" style="margin-bottom:20px;font-size:18px;color:var(--info);"></h3>

        <div class="kpi-grid" style="grid-template-columns:repeat(5,1fr);gap:20px;">
            <div class="kpi-card yellow">
                <div class="kpi-label">إجمالي الطلبات</div>
                <div class="kpi-value" id="kpi-total-orders">0</div>
            </div>
            <div class="kpi-card green">
                <div class="kpi-label">إجمالي الموصله</div>
                <div class="kpi-value" id="kpi-total-received">0</div>
            </div>
            <div class="kpi-card yellow">
                <div class="kpi-label">الطلبات المعلقة</div>
                <div class="kpi-value" id="kpi-pending">0</div>
                <div class="kpi-sub">معلقة + مستلمة للمندوب</div>
            </div>
            <div class="kpi-card red">
                <div class="kpi-label">الطلبات الملغية</div>
                <div class="kpi-value" id="kpi-cancelled">0</div>
            </div>
            <div class="kpi-card red">
                <div class="kpi-label">إجمالي الخصومات</div>
                <div class="kpi-value" id="kpi-total-discounts">0</div>
                <div class="kpi-sub">ج.م</div>
            </div>
            <div class="kpi-card yellow" id="kpi-period-safe-card">
                <div class="kpi-label">رصيد الخزنة في الفترة</div>
                <div class="kpi-value" id="kpi-period-safe-balance">0</div>
                <div class="kpi-sub">ج.م</div>
            </div>
            <div class="kpi-card red">
                <div class="kpi-label">مدين</div>
                <div class="kpi-value" id="kpi-debtor">0</div>
                <div class="kpi-sub">ج.م</div>
            </div>
            <div class="kpi-card green">
                <div class="kpi-label">دائن</div>
                <div class="kpi-value" id="kpi-creditor">0</div>
                <div class="kpi-sub">ج.م</div>
            </div>
            <div class="kpi-card cyan">
                <div class="kpi-label">إجمالي الطلبات الموصلة</div>
                <div class="kpi-value" id="kpi-total-delivered-revenue">0</div>
                <div class="kpi-sub">ج.م</div>
            </div>
            <div class="kpi-card green">
                <div class="kpi-label">إجمالي رسوم التوصيل</div>
                <div class="kpi-value" id="kpi-total-fees">0</div>
                <div class="kpi-sub">ج.م</div>
            </div>
            <div class="kpi-card" style="border-right:4px solid #a855f7;">
                <div class="kpi-label">الشريحة المحققة</div>
                <div class="kpi-value" id="kpi-cc-tier-number" style="color:#a855f7">—</div>
            </div>
            <div class="kpi-card" style="border-right:4px solid #a855f7;">
                <div class="kpi-label">إجمالي الأرباح</div>
                <div class="kpi-value" id="kpi-total-cc-profits" style="color:#a855f7">0</div>
                <div class="kpi-sub">ج.م</div>
            </div>
            <div class="kpi-card" style="border-right:4px solid lightblue;">
                <div class="kpi-label">إجمالي ساعات العمل</div>
                <div class="kpi-value" id="kpi-total-work-hours" style="color:lightblue">00:00</div>
                <div class="kpi-sub">ساعة : دقيقة</div>
            </div>
            <div class="kpi-card" style="border-right:4px solid lightblue;">
                <div class="kpi-label">إجمالي أيام العمل</div>
                <div class="kpi-value" id="kpi-total-work-days" style="color:lightblue">0</div>
                <div class="kpi-sub">يوم عمل</div>
            </div>
        </div>
    </div>

    <div class="card" id="cc-daily-breakdown-card" style="margin-bottom:20px; display:none;">
        <div
            style="padding:16px 20px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
            <span style="font-size:15px; font-weight:700;">تفصيل الشرائح اليومية</span>
            <span style="font-size:13px; color:var(--text-muted);">كل يوم محسوب بشريحته المستقلة</span>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th style="text-align:center;">التاريخ</th>
                        <th style="text-align:center;">عدد الطلبات</th>
                        <th style="text-align:center;">الشريحة</th>
                        <th style="text-align:center;">مبلغ الطلب</th>
                        <th style="text-align:center;">ربح اليوم</th>
                    </tr>
                </thead>
                <tbody id="cc-daily-breakdown-body"></tbody>
                <tfoot>
                    <tr style="background:#f8fafc; font-weight:800;">
                        <td colspan="4" style="text-align:center; padding:12px; color:#475569;">إجمالي الأرباح</td>
                        <td style="text-align:center; padding:12px; color:#a855f7; font-size:16px;"
                            id="cc-daily-breakdown-total">0 ج</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="card" style="padding:0;">
        <div
            style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
            <span style="font-size:15px;font-weight:700;">تفاصيل الطلبات</span>
            <span class="badge badge-gray" id="datatable-total">0 طلب</span>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th style="text-align:center;">كود الطلب</th>
                        <th style="text-align:center;">التاريخ</th>
                        <th style="text-align:right;">العميل</th>
                        <th style="text-align:center;">المندوب</th>
                        <th style="text-align:center;">رسوم التوصيل</th>
                        <th style="text-align:center;">الخصم</th>
                        <th style="text-align:center;">الإجمالي</th>
                        <th style="text-align:center;">الحالة</th>
                        <th style="text-align:center;">الإجراءات</th>
                    </tr>
                </thead>
                <tbody id="datatable-tbody">
                </tbody>
            </table>
        </div>
        <div style="padding:16px;border-top:1px solid var(--border);display:none;" id="pagination-wrapper">
            <div id="datatable-pagination" class="pagination"></div>
        </div>
    </div>
</div>

<script>
    (function () {
        'use strict';

        var DATA_URL = '{{ route('admin.report-callcenter.data') }}';
        var currentPage = 1;

        window.loadReport = function (page) {
            page = page || 1;
            currentPage = page;

            var callcenterId = document.getElementById('filter-callcenter-id').value;
            if (!callcenterId) {
                showError('الرجاء اختيار الموظف أولاً');
                return;
            }

            var btn = document.getElementById('search-btn');
            var spinner = document.getElementById('report-spinner');
            btn.disabled = true;
            spinner.style.display = 'inline-block';

            var params = {
                callcenter_id: callcenterId,
                from: document.getElementById('filter-from').value || null,
                to: document.getElementById('filter-to').value || null,
                page: page
            };

            axios.get(DATA_URL, { params: params })
                .then(function (res) {
                    var data = res.data;
                    document.getElementById('report-results').style.display = 'block';
                    document.getElementById('export-cc-excel-btn').style.display = 'inline-flex';
                    document.getElementById('report-agent-name').textContent = 'تقارير أداء الموظف: ' + data.agent_name;

                    fillKpis(data.kpis, data.daily_breakdown);
                    renderTable(data.orders);
                })
                .catch(function (e) {
                    console.error(e);
                    showError('حدث خطأ أثناء جلب البيانات');
                })
                .finally(function () {
                    btn.disabled = false;
                    spinner.style.display = 'none';
                });
        };

        function fillKpis(kpis, dailyBreakdown) {
            document.getElementById('kpi-total-orders').textContent = kpis.total_orders;
            document.getElementById('kpi-pending').textContent = kpis.pending;
            document.getElementById('kpi-total-received').textContent = kpis.total_received;
            document.getElementById('kpi-cancelled').textContent = kpis.cancelled;
            document.getElementById('kpi-total-delivered-revenue').textContent = kpis.total_delivered_revenue;
            document.getElementById('kpi-debtor').textContent = kpis.debtor;
            document.getElementById('kpi-creditor').textContent = kpis.creditor;
            document.getElementById('kpi-total-fees').textContent = kpis.total_fees;
            document.getElementById('kpi-total-discounts').textContent = kpis.total_discounts;

            var workHoursEl = document.getElementById('kpi-total-work-hours');
            if (workHoursEl) workHoursEl.textContent = kpis.total_work_hours;

            var workDaysEl = document.getElementById('kpi-total-work-days');
            if (workDaysEl) workDaysEl.textContent = kpis.total_work_days;

            var safeBalanceCard = document.getElementById('kpi-period-safe-card');
            var safeBalanceVal = document.getElementById('kpi-period-safe-balance');

            safeBalanceVal.textContent = kpis.period_safe_balance;

            // Remove old classes and colors
            safeBalanceCard.style.borderLeftColor = 'var(--border)';
            safeBalanceVal.style.color = 'inherit';

            if (kpis.raw_period_safe_balance > 0) {
                safeBalanceCard.style.borderLeftColor = 'var(--text)';
                safeBalanceVal.style.color = 'var(--text)';
            } else if (kpis.raw_period_safe_balance < 0) {
                safeBalanceCard.style.borderLeftColor = 'var(--text)';
                safeBalanceVal.style.color = 'var(--text)';
            }

            // الشريحة والأرباح
            var tierEl = document.getElementById('kpi-cc-tier-number');
            if (!kpis.is_single_day) {
                tierEl.textContent = '— حسب كل يوم';
                tierEl.style.color = '#94a3b8';
                tierEl.style.fontSize = '14px';
            } else if (kpis.tier_number > 0) {
                tierEl.textContent = 'الشريحة ' + kpis.tier_number;
                tierEl.style.color = '#a855f7';
                tierEl.style.fontSize = '';
            } else {
                tierEl.textContent = '— لا يوجد';
                tierEl.style.color = '#94a3b8';
                tierEl.style.fontSize = '';
            }
            document.getElementById('kpi-total-cc-profits').textContent = kpis.total_cc_profits;

            // جدول التفصيل اليومي
            var breakdownCard = document.getElementById('cc-daily-breakdown-card');
            var breakdownBody = document.getElementById('cc-daily-breakdown-body');

            if (!kpis.is_single_day && dailyBreakdown && dailyBreakdown.length > 0) {
                breakdownCard.style.display = 'block';
                var grandTotal = 0;
                breakdownBody.innerHTML = dailyBreakdown.map(function (day) {
                    grandTotal += day.profit;
                    var tierBadge = day.tier_number > 0
                        ? `<span style="background:#ede9fe;color:#7c3aed;padding:2px 10px;border-radius:12px;font-weight:700;font-size:12px;">شريحة ${day.tier_number}</span>`
                        : '<span style="color:#94a3b8;">—</span>';
                    return `
                        <tr>
                            <td style="text-align:center;font-weight:700;">${day.date}</td>
                            <td style="text-align:center;">${day.count} طلب</td>
                            <td style="text-align:center;">${tierBadge}</td>
                            <td style="text-align:center;">${Number(day.amount).toFixed(2)} ج</td>
                            <td style="text-align:center;font-weight:800;color:#a855f7;">${Number(day.profit).toFixed(2)} ج</td>
                        </tr>`;
                }).join('');
                document.getElementById('cc-daily-breakdown-total').textContent = grandTotal.toFixed(2) + ' ج';
            } else {
                breakdownCard.style.display = 'none';
                breakdownBody.innerHTML = '';
            }
        }

        function renderTable(payload) {
            var tbody = document.getElementById('datatable-tbody');
            var totalBadge = document.getElementById('datatable-total');
            totalBadge.textContent = payload.total + ' طلب';

            if (!payload.data || payload.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:40px;color:var(--text-muted);">لا توجد طلبات في هذه الفترة</td></tr>';
                document.getElementById('pagination-wrapper').style.display = 'none';
                return;
            }

            var rows = '';
            for (var i = 0; i < payload.data.length; i++) {
                var order = payload.data[i];
                var clientName = order.client ? esc(order.client.name) : '—';
                var deliveryName = order.delivery ? esc(order.delivery.name) : '—';
                var deliveryCell = deliveryName;
                if (order.is_delivery_chosen) {
                    deliveryCell += '<div class="kpi-sub" style="font-size:11px; margin-top:4px; color:var(--red-dark);">تم اختيار المندوب</div>';
                }
                rows += '<tr>'
                    + '<td style="color:var(--yellow);font-weight:700;text-align:center;">' + (order.order_number || ('#' + order.id)) + '</td>'
                    + '<td style="font-size:12px;text-align:center;">' + formatDate(order.created_at) + '</td>'
                    + '<td style="text-align:right;">' + clientName + '</td>'
                    + '<td style="text-align:center;">' + deliveryCell + '</td>'
                    + '<td style="text-align:center;">' + (order.delivery_fee || 0) + ' ج.م</td>'
                    + '<td style="text-align:center;">' + (order.discount || 0) + ' ج.م</td>'
                    + '<td style="font-weight:700;text-align:center;">' + (order.total || 0) + ' ج.م</td>'
                    + '<td style="text-align:center;">' + statusBadge(order.status) + '</td>'
                    + '<td style="text-align:center;"><button class="btn btn-sm btn-info" onclick="viewOrder(' + order.id + ')">عرض</button></td>'
                    + '</tr>';
            }
            tbody.innerHTML = rows;

            renderPagination(payload);
        }

        function renderPagination(payload) {
            var wrap = document.getElementById('pagination-wrapper');
            var pag = document.getElementById('datatable-pagination');

            if (payload.last_page <= 1) {
                wrap.style.display = 'none';
                return;
            }
            wrap.style.display = 'block';

            var html = '';
            if (payload.current_page > 1) {
                html += '<a href="#" onclick="event.preventDefault();loadReport(' + (payload.current_page - 1) + ')">«</a>';
            } else {
                html += '<span class="disabled">«</span>';
            }

            for (var p = 1; p <= payload.last_page; p++) {
                if (p === payload.current_page) {
                    html += '<span class="active">' + p + '</span>';
                } else {
                    html += '<a href="#" onclick="event.preventDefault();loadReport(' + p + ')">' + p + '</a>';
                }
            }

            if (payload.current_page < payload.last_page) {
                html += '<a href="#" onclick="event.preventDefault();loadReport(' + (payload.current_page + 1) + ')">»</a>';
            } else {
                html += '<span class="disabled">»</span>';
            }

            pag.innerHTML = html;
        }

        function esc(str) {
            if (!str) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/'/g, '&#39;')
                .replace(/"/g, '&quot;');
        }

        if (typeof addPolling === 'function') {
            addPolling(setInterval(function () {
                if (document.getElementById('report-results').style.display !== 'none') {
                    var callcenterId = document.getElementById('filter-callcenter-id').value;
                    if (callcenterId) {
                        var params = {
                            callcenter_id: callcenterId,
                            from: document.getElementById('filter-from').value || null,
                            to: document.getElementById('filter-to').value || null,
                            page: currentPage
                        };
                        axios.get(DATA_URL, { params: params })
                            .then(function (res) {
                                fillKpis(res.data.kpis, res.data.daily_breakdown);
                                renderTable(res.data.orders);
                            })
                            .catch(function (e) { console.warn('Polling error', e); });
                    }
                }
            }, 60000));
        }
    })();

    window.exportCCReportExcel = async function () {
        var ccId = document.getElementById('filter-callcenter-id').value;
        if (!ccId) return;
        try {
            var params = {
                callcenter_id: ccId,
                from: document.getElementById('filter-from').value || null,
                to: document.getElementById('filter-to').value || null,
                per_page: 9999,
            };
            const res = await axios.get('{{ route("admin.report-callcenter.data") }}', { params });
            const orders = res.data.orders.data || [];
            const dailyBreakdown = res.data.daily_breakdown || [];

            const statusMap = { pending: 'قيد الانتظار', received: 'مسلم للمندوب', delivered: 'تم التوصيل', cancelled: 'ملغي' };

            const wsData = [];

            // Row 1: Titles
            const titleRow = Array(15).fill("");
            titleRow[0] = "تفاصيل الطلبات";
            titleRow[10] = "تفصيل الشرائح اليومية";
            wsData.push(titleRow);

            // Row 2: Headers
            const headerRow = [
                'رقم الطلب', 'التاريخ', 'العميل', 'المندوب', 'اختيار المندوب', 'رسوم التوصيل', 'الخصم', 'الإجمالي', 'الحالة',
                '', // Space Column J
                'التاريخ', 'عدد الطلبات', 'رقم الشريحة', 'مبلغ الطلب', 'ربح اليوم'
            ];
            wsData.push(headerRow);

            // Data Rows
            const maxRows = Math.max(orders.length, dailyBreakdown.length);
            for (let i = 0; i < maxRows; i++) {
                const row = Array(15).fill("");
                const o = orders[i];
                const d = dailyBreakdown[i];

                if (o) {
                    row[0] = o.order_number || ('#' + o.id);
                    row[1] = o.created_at ? new Date(o.created_at).toLocaleString('en-GB', { hour12: true }).replace('am', 'ص').replace('pm', 'م').replace('AM', 'ص').replace('PM', 'م') : '—';
                    row[2] = o.client ? o.client.name : '—';
                    row[3] = o.delivery ? o.delivery.name : '—';
                    row[4] = o.is_delivery_chosen ? 'نعم' : '—';
                    row[5] = o.delivery_fee;
                    row[6] = o.discount;
                    row[7] = o.total;
                    row[8] = statusMap[o.status] || o.status;
                }

                if (d) {
                    row[10] = d.date;
                    row[11] = d.count + ' طلب';
                    row[12] = d.tier_number > 0 ? ('شريحة ' + d.tier_number) : '— لا شريحة';
                    row[13] = d.amount.toFixed(2);
                    row[14] = d.profit.toFixed(2);
                }
                wsData.push(row);
            }

            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.aoa_to_sheet(wsData);

            ws['!merges'] = [
                { s: { r: 0, c: 0 }, e: { r: 0, c: 8 } }, // Merge A1:I1
                { s: { r: 0, c: 10 }, e: { r: 0, c: 14 } } // Merge K1:O1
            ];

            const colWidths = [18, 15, 20, 20, 16, 12, 10, 12, 15, 5, 15, 12, 15, 12, 12];
            ws['!cols'] = colWidths.map(w => ({ wch: w }));

            XLSX.utils.book_append_sheet(wb, ws, 'تقرير الكول سنتر');
            XLSX.writeFile(wb, 'cc-report-' + res.data.agent_name + '-' + new Date().toISOString().slice(0, 10) + '.xlsx');

            if (typeof showSuccess === 'function') showSuccess('تم التصدير بنجاح ✓');
        } catch (e) {
            if (typeof showError === 'function') showError('حدث خطأ أثناء التصدير');
            console.error(e);
        }
    };
</script>

@include('admin.orders.partials.view_modal')