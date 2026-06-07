<div class="section-header">
    <h2>تقارير المناديب</h2>
</div>

<div class="card" style="margin-bottom:20px;">
    <div class="filter-bar" style="margin-bottom:0;">
        <div style="flex:1;min-width:200px;">
            <label class="form-label">المندوب <span style="color:var(--red)">*</span></label>
            <div class="relative group" style="z-index: 50;">
                <div class="form-control"
                    style="cursor:pointer; display:flex; justify-content:space-between; align-items:center;">
                    <span id="label-filter-delivery-id">اختر المندوب</span>
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </div>
                <input type="hidden" id="filter-delivery-id" value="">
                <div class="absolute top-full right-0 w-full opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all bg-white/80 backdrop-blur shadow-lg rounded-md mt-1 overflow-hidden"
                    style="border:1px solid var(--border); background-color: rgba(255, 255, 255, 0.9); max-height:200px; overflow-y:auto;">
                    <div class="px-3 py-2 cursor-pointer hover:bg-green-50 hover:text-green-700 text-sm transition-colors text-gray-800"
                        onclick="selectDropdown('filter-delivery-id', '', 'اختر المندوب')">اختر المندوب</div>
                    @foreach($deliveries as $d)
                        <div class="px-3 py-2 cursor-pointer hover:bg-green-50 hover:text-green-700 text-sm transition-colors text-gray-800"
                            onclick="selectDropdown('filter-delivery-id', '{{ $d->id }}', '{{ $d->name }}')">{{ $d->name }}
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
            <button class="btn btn-success" id="export-delivery-excel-btn" onclick="exportDeliveryReportExcel()"
                style="background:#217346;color:#fff;display:none;">تصدير Excel</button>
            <span id="report-spinner" class="spin" style="display:none;align-self:center;margin-right:10px;"></span>
        </div>
    </div>
</div>

<div id="report-results" style="display:none;">
    <div class="card" style="margin-bottom:20px;padding:24px;">
        <h3 id="report-delivery-name" style="margin-bottom:20px;font-size:18px;color:var(--info);"></h3>

        <div class="kpi-grid" style="grid-template-columns:repeat(5,1fr);gap:20px;">
            <div class="kpi-card yellow">
                <div class="kpi-label">إجمالي الطلبات</div>
                <div class="kpi-value" id="kpi-total-orders">0</div>
            </div>
            <div class="kpi-card green">
                <div class="kpi-label">الطلبات الموصلة</div>
                <div class="kpi-value" id="kpi-delivered-orders">0</div>
            </div>
            <div class="kpi-card yellow">
                <div class="kpi-label">الطلبات المعلقة</div>
                <div class="kpi-value" id="kpi-pending-orders">0</div>
                <div class="kpi-sub">تم استلامها ولم توصل</div>
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
                <div class="kpi-label">الرصيد الفعلي (العهدة)</div>
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
                <div class="kpi-label">إجمالي الطلبات الموصله</div>
                <div class="kpi-value" id="kpi-total-revenue">0</div>
                <div class="kpi-sub">ج.م</div>
            </div>
            <div class="kpi-card green">
                <div class="kpi-label">إجمالي رسوم التوصيل</div>
                <div class="kpi-value" id="kpi-total-fees">0</div>
                <div class="kpi-sub">ج.م</div>
            </div>
            <div class="kpi-card" style="border-right:4px solid #a855f7;">
                <div class="kpi-label">الشريحة المحققة</div>
                <div class="kpi-value" id="kpi-tier-number" style="color:#a855f7">—</div>
            </div>
            <div class="kpi-card" style="border-right:4px solid #a855f7;">
                <div class="kpi-label">إجمالي الأرباح</div>
                <div class="kpi-value" id="kpi-total-profits" style="color:#a855f7">0</div>
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

    <!-- Daily Tier Breakdown — يظهر فقط إذا كانت الفترة أكثر من يوم -->
    <div class="card" id="daily-breakdown-card" style="margin-bottom:20px; display:none;">
        <div
            style="padding:16px 20px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
            <span style="font-size:15px; font-weight:700;">تفصيل الشرائح اليومية</span>
            <span style="font-size:13px; color:var(--text-muted);">كل يوم يُحسب بشريحته المستقلة</span>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th style="text-align:center;">التاريخ</th>
                        <th style="text-align:center;">عدد الطلبات</th>
                        <th style="text-align:center;">رقم الشريحة</th>
                        <th style="text-align:center;">مبلغ الطلب</th>
                        <th style="text-align:center;">ربح اليوم</th>
                    </tr>
                </thead>
                <tbody id="daily-breakdown-body"></tbody>
                <tfoot>
                    <tr style="background:#f8fafc; font-weight:800;">
                        <td colspan="4" style="text-align:center; padding:12px; color:#475569;">إجمالي الأرباح</td>
                        <td style="text-align:center; padding:12px; color:#a855f7; font-size:16px;"
                            id="daily-breakdown-total">0 ج</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div style="padding:16px; border-top:1px solid var(--border); display:none;"
            id="daily-breakdown-pagination-wrapper">
            <div id="daily-breakdown-pagination" class="pagination"></div>
        </div>
    </div>

    <div class="card" style="padding:0;">
        <div
            style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
            <span style="font-size:15px;font-weight:700;">تفاصيل الطلبات</span>
            <span class="badge badge-gray" id="datatable-total">0 طلب</span>
        </div>:
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th style="text-align:center;">كود الطلب</th>
                        <th style="text-align:center;">التاريخ</th>
                        <th style="text-align:right;">العميل</th>
                        <th style="text-align:center;">تم انشاؤه</th>
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

        var DATA_URL = '{{ route('admin.report-delivery.data') }}';
        var currentPage = 1;

        // ── Expose globally so onclick="loadReport()" works ──────────────
        window.loadReport = function (page) {
            page = page || 1;
            currentPage = page;

            var deliveryId = document.getElementById('filter-delivery-id').value;
            if (!deliveryId) {
                showError('الرجاء اختيار المندوب أولاً');
                return;
            }

            var btn = document.getElementById('search-btn');
            var spinner = document.getElementById('report-spinner');
            btn.disabled = true;
            spinner.style.display = 'inline-block';

            var params = {
                delivery_id: deliveryId,
                from: document.getElementById('filter-from').value || null,
                to: document.getElementById('filter-to').value || null,
                page: page
            };

            axios.get(DATA_URL, { params: params })
                .then(function (res) {
                    var data = res.data;
                    document.getElementById('report-results').style.display = 'block';
                    document.getElementById('export-delivery-excel-btn').style.display = 'inline-flex';
                    document.getElementById('report-delivery-name').textContent = 'تقارير الأداء: ' + data.delivery_name;

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

        // ── Fill KPI cards ──────────────────────────────────────────────
        function fillKpis(kpis, dailyBreakdown) {
            document.getElementById('kpi-total-orders').textContent = kpis.total_orders;
            document.getElementById('kpi-delivered-orders').textContent = kpis.delivered_orders || 0;
            document.getElementById('kpi-pending-orders').textContent = kpis.pending_orders || 0;
            document.getElementById('kpi-total-fees').textContent = kpis.total_fees;
            document.getElementById('kpi-cancelled').textContent = kpis.cancelled;
            document.getElementById('kpi-total-revenue').textContent = kpis.total_revenue;
            document.getElementById('kpi-total-discounts').textContent = kpis.total_discounts;
            document.getElementById('kpi-creditor').textContent = kpis.creditor;
            document.getElementById('kpi-debtor').textContent = kpis.debtor;
            // رقم الشريحة — يُظهر فقط إذا كانت الفترة يوم واحد
            var tierEl = document.getElementById('kpi-tier-number');
            if (!kpis.is_single_day) {
                tierEl.textContent = '— حسب كل يوم';
                tierEl.style.fontSize = '14px';
                tierEl.style.color = '#94a3b8';
            } else if (kpis.tier_number > 0) {
                tierEl.textContent = 'الشريحة ' + kpis.tier_number;
                tierEl.style.fontSize = '';
                tierEl.style.color = '#a855f7';
            } else {
                tierEl.textContent = '— لا يوجد';
                tierEl.style.fontSize = '';
                tierEl.style.color = '#94a3b8';
            }

            // تفصيل الشرائح اليومية
            var breakdownCard = document.getElementById('daily-breakdown-card');

            if (!kpis.is_single_day && dailyBreakdown && dailyBreakdown.length > 0) {
                breakdownCard.style.display = 'block';
                renderDailyBreakdown(dailyBreakdown, 1);
            } else {
                breakdownCard.style.display = 'none';
                document.getElementById('daily-breakdown-body').innerHTML = '';
                document.getElementById('daily-breakdown-pagination-wrapper').style.display = 'none';
            }
            document.getElementById('kpi-total-profits').textContent = kpis.total_profits;
            document.getElementById('kpi-total-work-hours').textContent = kpis.total_work_hours;

            var workDaysEl = document.getElementById('kpi-total-work-days');
            if (workDaysEl) workDaysEl.textContent = kpis.total_work_days;

            var safeBalanceCard = document.getElementById('kpi-period-safe-card');
            if (safeBalanceCard) {
                var safeBalanceVal = document.getElementById('kpi-period-safe-balance');
                safeBalanceVal.textContent = kpis.period_safe_balance;

                // Remove old classes and colors
                safeBalanceCard.style.borderRightColor = 'var(--border)';
                safeBalanceVal.style.color = '#fff';

                if (kpis.raw_period_safe_balance > 0) {
                    safeBalanceCard.style.borderRightColor = 'var(--success)';
                } else if (kpis.raw_period_safe_balance < 0) {
                    safeBalanceCard.style.borderRightColor = 'var(--red)';
                }
            }
        }

        // ── Table renderer ──────────────────────────────────────────────
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
                var creatorName = order.callcenter ? esc(order.callcenter.name) : (order.admin ? esc(order.admin.name) : '—');
                var creatorBadge = (order.admin && !order.callcenter) ? ' <span class="badge badge-blue" style="font-size:9px; padding:1px 4px;">أدمن</span>' : '';
                rows += '<tr>'
                    + '<td style="color:var(--yellow);font-weight:700;text-align:center;">' + (order.order_number || ('#' + order.id)) + '</td>'
                    + '<td style="font-size:14px;text-align:center;">' + formatDate(order.created_at) + '</td>'
                    + '<td style="text-align:right;">' + clientName + '</td>'
                    + '<td style="text-align:center;">' + creatorName + creatorBadge + '</td>'
                    + '<td style="text-align:center;">' + order.delivery_fee + ' ج.م</td>'
                    + '<td style="text-align:center;">' + order.discount + ' ج.م</td>'
                    + '<td style="font-weight:700;text-align:center;">' + order.total + ' ج.م</td>'
                    + '<td style="text-align:center;">' + statusBadge(order.status) + '</td>'
                    + '<td style="text-align:center;"><button class="btn btn-sm btn-info" onclick="viewOrder(' + order.id + ')">عرض</button></td>'
                    + '</tr>';
            }
            tbody.innerHTML = rows;

            renderPagination(payload);
        }

        // ── Daily Breakdown Pagination ───────────────────────────────────
        var _dailyBreakdownData = [];
        var DAILY_PER_PAGE = 10;

        window.loadDailyBreakdownPage = function (page) {
            renderDailyBreakdown(_dailyBreakdownData, page);
        };

        function renderDailyBreakdown(data, page) {
            _dailyBreakdownData = data;
            var perPage = DAILY_PER_PAGE;
            var totalPages = Math.ceil(data.length / perPage);
            page = Math.max(1, Math.min(page, totalPages));

            var start = (page - 1) * perPage;
            var slice = data.slice(start, start + perPage);

            var breakdownBody = document.getElementById('daily-breakdown-body');
            breakdownBody.innerHTML = slice.map(function (day) {
                var tierLabel = day.tier_number > 0
                    ? '<span style="color:#7c3aed;padding:2px 8px;border-radius:12px;font-weight:700;">شريحة ' + day.tier_number + '</span>'
                    : '— لا شريحة';
                return '<tr>'
                    + '<td style="text-align:center; font-weight:700;">' + day.date + '</td>'
                    + '<td style="text-align:center;">' + day.count + ' طلب</td>'
                    + '<td style="text-align:center;">' + tierLabel + '</td>'
                    + '<td style="text-align:center;">' + day.amount.toFixed(2) + ' ج</td>'
                    + '<td style="text-align:center; font-weight:800; color:#a855f7;">' + day.profit.toFixed(2) + ' ج</td>'
                    + '</tr>';
            }).join('');

            var grandTotal = data.reduce(function (sum, day) { return sum + day.profit; }, 0);
            var totalEl = document.getElementById('daily-breakdown-total');
            if (totalEl) totalEl.textContent = grandTotal.toFixed(2) + ' ج';

            // Pagination bar
            var wrap = document.getElementById('daily-breakdown-pagination-wrapper');
            var pag = document.getElementById('daily-breakdown-pagination');

            if (totalPages <= 1) {
                wrap.style.display = 'none';
                return;
            }
            wrap.style.display = 'block';

            var html = '';
            html += page > 1
                ? '<a href="#" onclick="event.preventDefault();loadDailyBreakdownPage(' + (page - 1) + ')">«</a>'
                : '<span class="disabled">«</span>';

            for (var p = 1; p <= totalPages; p++) {
                html += p === page
                    ? '<span class="active">' + p + '</span>'
                    : '<a href="#" onclick="event.preventDefault();loadDailyBreakdownPage(' + p + ')">' + p + '</a>';
            }

            html += page < totalPages
                ? '<a href="#" onclick="event.preventDefault();loadDailyBreakdownPage(' + (page + 1) + ')">»</a>'
                : '<span class="disabled">»</span>';

            pag.innerHTML = html;
        }

        // ── Orders Pagination ───────────────────────────────────────────────
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

        // ── HTML escape ─────────────────────────────────────────────────
        function esc(str) {
            if (!str) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/'/g, '&#39;')
                .replace(/"/g, '&quot;');
        }

        // ── SPA Polling (60s) ───────────────────────────────────────────
        if (typeof addPolling === 'function') {
            addPolling(setInterval(function () {
                if (document.getElementById('report-results').style.display !== 'none') {
                    var deliveryId = document.getElementById('filter-delivery-id').value;
                    if (deliveryId) {
                        var params = {
                            delivery_id: deliveryId,
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

    window.exportDeliveryReportExcel = async function () {
        var deliveryId = document.getElementById('filter-delivery-id').value;
        if (!deliveryId) return;
        try {
            var params = {
                delivery_id: deliveryId,
                from: document.getElementById('filter-from').value || null,
                to: document.getElementById('filter-to').value || null,
                per_page: 9999,
            };
            const res = await axios.get('{{ route("admin.report-delivery.data") }}', { params });
            const orders = res.data.orders.data || [];
            const dailyBreakdown = res.data.daily_breakdown || [];

            const statusMap = { pending: 'قيد الانتظار', received: 'مسلم للمندوب', delivered: 'تم التوصيل', cancelled: 'ملغي' };

            // Constructing AOAs (Array of Arrays) for XLSX
            const wsData = [];

            // Row 1: Titles
            const titleRow = Array(14).fill("");
            titleRow[0] = "تفاصيل الطلبات";
            titleRow[9] = "تفصيل الشرائح اليومية";
            wsData.push(titleRow);

            // Row 2: Headers
            const headerRow = [
                'رقم الطلب', 'التاريخ', 'العميل', 'تم انشاؤه', 'رسوم التوصيل', 'الخصم', 'الإجمالي', 'الحالة',
                '', // Space Column I
                'التاريخ', 'عدد الطلبات', 'رقم الشريحة', 'مبلغ الطلب', 'ربح اليوم'
            ];
            wsData.push(headerRow);

            // Data Rows
            const maxRows = Math.max(orders.length, dailyBreakdown.length);
            for (let i = 0; i < maxRows; i++) {
                const row = Array(14).fill("");
                const o = orders[i];
                const d = dailyBreakdown[i];

                if (o) {
                    row[0] = o.order_number || ('#' + o.id);
                    row[1] = o.created_at ? new Date(o.created_at).toLocaleString('en-GB', { hour12: true }).replace('am', 'ص').replace('pm', 'م').replace('AM', 'ص').replace('PM', 'م') : '—';
                    row[2] = o.client ? o.client.name : '—';
                    row[3] = o.callcenter ? o.callcenter.name : (o.admin ? o.admin.name : '—');
                    row[4] = o.delivery_fee;
                    row[5] = o.discount;
                    row[6] = o.total;
                    row[7] = statusMap[o.status] || o.status;
                }

                if (d) {
                    row[9] = d.date; // d.date is usually Y-m-d string from backend
                    row[10] = d.count + ' طلب';
                    row[11] = d.tier_number > 0 ? ('شريحة ' + d.tier_number) : '— لا شريحة';
                    row[12] = d.amount.toFixed(2);
                    row[13] = d.profit.toFixed(2);
                }
                wsData.push(row);
            }

            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.aoa_to_sheet(wsData);

            // Apply Merges for Row 1 titles
            ws['!merges'] = [
                { s: { r: 0, c: 0 }, e: { r: 0, c: 7 } }, // Merge A1:H1
                { s: { r: 0, c: 9 }, e: { r: 0, c: 13 } } // Merge J1:N1
            ];

            // Adjust Column Widths
            const colWidths = [18, 15, 20, 15, 12, 10, 12, 15, 5, 15, 12, 15, 12, 12];
            ws['!cols'] = colWidths.map(w => ({ wch: w }));

            XLSX.utils.book_append_sheet(wb, ws, 'تقرير المندوب');
            XLSX.writeFile(wb, 'delivery-report-' + res.data.delivery_name + '-' + new Date().toISOString().slice(0, 10) + '.xlsx');

            if (typeof showSuccess === 'function') showSuccess('تم التصدير بنجاح ✓');
        } catch (e) {
            if (typeof showError === 'function') showError('حدث خطأ أثناء التصدير');
            console.error(e);
        }
    };
</script>

@include('admin.orders.partials.view_modal')