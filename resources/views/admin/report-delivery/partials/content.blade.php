<div class="section-header">
    <h2>تقارير المناديب</h2>
</div>

<div class="card" style="margin-bottom:20px;">
    <div class="filter-bar" style="margin-bottom:0;">
        <div style="flex:1;min-width:200px;">
            <label class="form-label">المندوب <span style="color:var(--red)">*</span></label>
            <select id="filter-delivery-id" class="form-select">
                <option value="">اختر المندوب</option>
                @foreach($deliveries as $d)
                    <option value="{{ $d->id }}">{{ $d->name }}</option>
                @endforeach
            </select>
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
            <span id="report-spinner" class="spin" style="display:none;align-self:center;margin-right:10px;"></span>
        </div>
    </div>
</div>

<div id="report-results" style="display:none;">
    <div class="card" style="margin-bottom:20px;padding:24px;">
        <h3 id="report-delivery-name" style="margin-bottom:20px;font-size:18px;color:var(--info);"></h3>

        <div class="kpi-grid" style="grid-template-columns:repeat(4,1fr);gap:20px;">
            <div class="kpi-card yellow">
                <div class="kpi-label">إجمالي الطلبات</div>
                <div class="kpi-value" id="kpi-total-orders">0</div>
            </div>
            <div class="kpi-card red">
                <div class="kpi-label">الطلبات الملغية</div>
                <div class="kpi-value" id="kpi-cancelled" style="color:var(--red)">0</div>
            </div>
            <div class="kpi-card blue">
                <div class="kpi-label">مدين</div>
                <div class="kpi-value" id="kpi-debtor" style="color:var(--info)">0</div>
                <div class="kpi-sub">ج.م (سلف / مديونيات)</div>
            </div>
            <div class="kpi-card green">
                <div class="kpi-label">إجمالي رسوم التوصيل</div>
                <div class="kpi-value" id="kpi-total-fees" style="color:var(--green)">0</div>
                <div class="kpi-sub">ج.م</div>
            </div>
            <div class="kpi-card cyan">
                <div class="kpi-label">إجمالي الطلبات الموصلة</div>
                <div class="kpi-value" id="kpi-total-revenue" style="color:var(--cyan)">0</div>
                <div class="kpi-sub">ج.م</div>
            </div>
            
            <div class="kpi-card red">
                <div class="kpi-label">إجمالي الخصومات</div>
                <div class="kpi-value" id="kpi-total-discounts" style="color:var(--red)">0</div>
                <div class="kpi-sub">ج.م</div>
            </div>

            <div class="kpi-card blue" style="border-right: 4px solid var(--info)">
                <div class="kpi-label">دائن</div>
                <div class="kpi-value" id="kpi-creditor" style="color:var(--info)">0</div>
                <div class="kpi-sub">ج.م</div>
            </div>

            <div class="kpi-card green" id="kpi-period-safe-card">
                <div class="kpi-label">إجمالي الخزنة في الفترة</div>
                <div class="kpi-value" id="kpi-period-safe-balance" style="color:inherit;">0</div>
                <div class="kpi-sub">ج.م (الرصيد الفعلي للفترة)</div>
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

    <div class="card" style="padding:0;">
        <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
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
                        <th style="text-align:center;">الكول سنتر</th>
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

        var btn     = document.getElementById('search-btn');
        var spinner = document.getElementById('report-spinner');
        btn.disabled = true;
        spinner.style.display = 'inline-block';

        var params = {
            delivery_id: deliveryId,
            from:  document.getElementById('filter-from').value || null,
            to:    document.getElementById('filter-to').value   || null,
            page:  page
        };

        axios.get(DATA_URL, { params: params })
            .then(function (res) {
                var data = res.data;
                document.getElementById('report-results').style.display = 'block';
                document.getElementById('report-delivery-name').textContent = 'تقارير الأداء: ' + data.delivery_name;

                fillKpis(data.kpis);
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
    function fillKpis(kpis) {
        document.getElementById('kpi-total-orders').textContent    = kpis.total_orders;
        document.getElementById('kpi-total-fees').textContent      = kpis.total_fees;
        document.getElementById('kpi-cancelled').textContent       = kpis.cancelled;
        document.getElementById('kpi-total-revenue').textContent   = kpis.total_revenue;
        document.getElementById('kpi-total-discounts').textContent = kpis.total_discounts;
        document.getElementById('kpi-creditor').textContent        = kpis.creditor;
        document.getElementById('kpi-debtor').textContent          = kpis.debtor;
        document.getElementById('kpi-tier-number').textContent     = kpis.tier_number > 0 ? 'الشريحة ' + kpis.tier_number : '— لا يوجد';
        document.getElementById('kpi-total-profits').textContent   = kpis.total_profits;
        document.getElementById('kpi-total-work-hours').textContent = kpis.total_work_hours;
        
        var workDaysEl = document.getElementById('kpi-total-work-days');
        if (workDaysEl) workDaysEl.textContent = kpis.total_work_days;

        var safeBalanceCard = document.getElementById('kpi-period-safe-card');
        if (safeBalanceCard) {
            var safeBalanceVal  = document.getElementById('kpi-period-safe-balance');
            safeBalanceVal.textContent = kpis.period_safe_balance;
            
            // Remove old classes and colors
            safeBalanceCard.style.borderRightColor = 'var(--border)';
            safeBalanceVal.style.color = 'inherit';
            
            if (kpis.raw_period_safe_balance > 0) {
                safeBalanceCard.style.borderRightColor = 'var(--success)';
                safeBalanceVal.style.color = 'var(--success)';
            } else if (kpis.raw_period_safe_balance < 0) {
                safeBalanceCard.style.borderRightColor = 'var(--red)';
                safeBalanceVal.style.color = 'var(--red)';
            }
        }
    }

    // ── Table renderer ──────────────────────────────────────────────
    function renderTable(payload) {
        var tbody      = document.getElementById('datatable-tbody');
        var totalBadge = document.getElementById('datatable-total');
        totalBadge.textContent = payload.total + ' طلب';

        if (!payload.data || payload.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:40px;color:var(--text-muted);">لا توجد طلبات في هذه الفترة</td></tr>';
            document.getElementById('pagination-wrapper').style.display = 'none';
            return;
        }

        var rows = '';
        for (var i = 0; i < payload.data.length; i++) {
            var order          = payload.data[i];
            var clientName     = order.client      ? esc(order.client.name)      : '—';
            var callcenterName = order.callcenter  ? esc(order.callcenter.name)  : '—';
            rows += '<tr>'
                  + '<td style="color:var(--yellow);font-weight:700;text-align:center;">' + (order.order_number || ('#' + order.id)) + '</td>'
                  + '<td style="font-size:14px;text-align:center;">' + formatDate(order.created_at) + '</td>'
                  + '<td style="text-align:right;">' + clientName + '</td>'
                  + '<td style="text-align:center;">' + callcenterName + '</td>'
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

    // ── Pagination ──────────────────────────────────────────────────
    function renderPagination(payload) {
        var wrap = document.getElementById('pagination-wrapper');
        var pag  = document.getElementById('datatable-pagination');

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
                        from:  document.getElementById('filter-from').value || null,
                        to:    document.getElementById('filter-to').value   || null,
                        page:  currentPage
                    };
                    axios.get(DATA_URL, { params: params })
                         .then(function (res) {
                             fillKpis(res.data.kpis);
                             renderTable(res.data.orders);
                         })
                         .catch(function (e) { console.warn('Polling error', e); });
                }
            }
        }, 60000));
    }
})();
</script>

@include('admin.orders.partials.view_modal')
