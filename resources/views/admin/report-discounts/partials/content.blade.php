{{-- Admin Discount Reports SPA Partial --}}
<div class="section-header">
    <h2>تقارير الخصومات</h2>
</div>

{{-- ─── Filters ────────────────────────────────────────────── --}}
<div class="card" style="margin-bottom:20px">
    <div class="filter-bar" style="flex-wrap:wrap;gap:10px;align-items:flex-end">

        {{-- Date From --}}
        <div style="display:flex;flex-direction:column;gap:4px;min-width:140px">
            <label style="font-size:12px;color:var(--text-muted);font-weight:600">من تاريخ</label>
            <input type="date" id="dc-from" class="form-control">
        </div>

        {{-- Date To --}}
        <div style="display:flex;flex-direction:column;gap:4px;min-width:140px">
            <label style="font-size:12px;color:var(--text-muted);font-weight:600">إلى تاريخ</label>
            <input type="date" id="dc-to" class="form-control">
        </div>

        {{-- Client — searchable dropdown --}}
        <div style="display:flex;flex-direction:column;gap:4px;flex:1;min-width:220px;position:relative">
            <label style="font-size:12px;color:var(--text-muted);font-weight:600">العميل (كود أو اسم)</label>
            <div style="position:relative">
                <input type="text" id="dc-client-search" class="form-control" placeholder="ابحث بالكود أو الاسم..."
                    autocomplete="off" style="padding-left:32px">
            </div>
            <input type="hidden" id="dc-client-id">
            <div id="dc-client-dropdown"
                style="display:none;position:absolute;top:100%;right:0;left:0;z-index:200;background:var(--card-bg);border:1px solid var(--border);border-radius:8px;box-shadow:0 8px 24px rgba(0,0,0,.25);max-height:220px;overflow-y:auto;margin-top:2px">
            </div>
        </div>

        {{-- Call Center --}}
        <div style="display:flex;flex-direction:column;gap:4px;min-width:180px">
            <label style="font-size:12px;color:var(--text-muted);font-weight:600">الكول سنتر</label>
            <select id="dc-callcenter" class="form-select">
                <option value="">كل الكول سنتر</option>
                @foreach($callcenters as $cc)
                    <option value="{{ $cc->id }}">{{ $cc->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Actions --}}
        <div style="display:flex;gap:8px;align-items:flex-end;padding-bottom:0">
            <button class="btn btn-primary" onclick="dcLoad()">عرض</button>
            <button class="btn btn-secondary" onclick="dcReset()">إعادة</button>
        </div>
    </div>
</div>

{{-- ─── KPI Cards ──────────────────────────────────────────── --}}
<div class="kpi-grid" style="margin-bottom:20px;grid-template-columns:repeat(2,1fr)">
    <div class="kpi-card yellow">
        <div class="kpi-label">إجمالي الطلبات التي تم عليها خصم</div>
        <div class="kpi-value" id="dc-kpi-orders">—</div>
    </div>
    <div class="kpi-card red">
        <div class="kpi-label">إجمالي الخصومات</div>
        <div class="kpi-value" id="dc-kpi-discounts">—</div>
        <div class="kpi-sub">ج.م</div>
    </div>
</div>

{{-- ─── Orders Table ────────────────────────────────────────── --}}
<div class="card" style="padding:0">
    <div
        style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center">
        <strong>سجل الطلبات التي طُبِّق عليها خصم</strong>
        <span id="dc-count-label" style="font-size:14px;color:var(--text-muted)"></span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="text-align:center">رقم الطلب</th>
                    <th style="text-align:center">التاريخ</th>
                    <th style="text-align:center">العميل</th>
                    <th style="text-align:center">كول سنتر</th>
                    <th style="text-align:center">المندوب</th>
                    <th style="text-align:center">الأصناف</th>
                    <th style="text-align:center">الخصم</th>
                    <th style="text-align:center">الإجمالي</th>
                    <th style="text-align:center">الحالة</th>
                    <th style="text-align:center"></th>
                </tr>
            </thead>
            <tbody id="dc-orders-body">
                <tr>
                    <td colspan="10" style="text-align:center;padding:40px;color:var(--text-muted)">اضغط "عرض" لتحميل
                        البيانات</td>
                </tr>
            </tbody>
            <tfoot id="dc-totals-foot" style="background:var(--bg);font-weight:700"></tfoot>
        </table>
    </div>
    <div id="dc-pagination" style="padding:16px"></div>
</div>

{{-- ─── Order Detail Modal ────────────────────────────────── --}}
<div class="modal-overlay" id="modal-dc-detail">
    <div class="modal modal-lg">
        <div class="modal-header">
            <h3>تفاصيل الطلب — <span id="dc-modal-number"></span></h3>
            <button class="btn-close" onclick="closeModal('modal-dc-detail')">✕</button>
        </div>
        <div class="modal-body" id="dc-modal-body">
            <div style="text-align:center;padding:40px">
                <div class="spin"></div>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {

        // ─── Client Searchable Dropdown ───────────────────────────
        var clientSearchTimeout = null;
        var selectedClientId = '';

        var searchInput = document.getElementById('dc-client-search');
        var clientIdEl = document.getElementById('dc-client-id');
        var dropdown = document.getElementById('dc-client-dropdown');

        searchInput.addEventListener('input', function () {
            clearTimeout(clientSearchTimeout);
            var q = this.value.trim();
            clientIdEl.value = '';
            selectedClientId = '';
            if (!q) { hideDropdown(); return; }
            clientSearchTimeout = setTimeout(function () { fetchClients(q); }, 280);
        });

        searchInput.addEventListener('blur', function () {
            setTimeout(hideDropdown, 200);
        });

        function fetchClients(q) {
            axios.get('{{ route("admin.report-discounts.clients") }}', { params: { q } })
                .then(function (res) {
                    var items = res.data;
                    if (!items.length) {
                        dropdown.innerHTML = '<div style="padding:12px 16px;color:var(--text-muted);font-size:13px">لا نتائج</div>';
                    } else {
                        dropdown.innerHTML = items.map(function (c) {
                            return '<div class="dc-client-item" data-id="' + c.id + '" style="padding:10px 16px;cursor:pointer;font-size:13px;transition:.15s" onmouseover="this.style.background=\'var(--bg)\'" onmouseout="this.style.background=\'\'">' + escHtml(c.text) + '</div>';
                        }).join('');
                        dropdown.querySelectorAll('.dc-client-item').forEach(function (el) {
                            el.addEventListener('click', function () {
                                clientIdEl.value = this.dataset.id;
                                selectedClientId = this.dataset.id;
                                searchInput.value = this.textContent.trim();
                                hideDropdown();
                            });
                        });
                    }
                    showDropdown();
                })
                .catch(function () { hideDropdown(); });
        }

        function showDropdown() { dropdown.style.display = 'block'; }
        function hideDropdown() { dropdown.style.display = 'none'; }

        // ─── Filters ──────────────────────────────────────────────
        function getFilters() {
            return {
                from: document.getElementById('dc-from').value,
                to: document.getElementById('dc-to').value,
                client_id: document.getElementById('dc-client-id').value,
                callcenter_id: document.getElementById('dc-callcenter').value,
            };
        }

        window.dcReset = function () {
            document.getElementById('dc-from').value = '';
            document.getElementById('dc-to').value = '';
            document.getElementById('dc-client-search').value = '';
            document.getElementById('dc-client-id').value = '';
            document.getElementById('dc-callcenter').value = '';
            dcLoad(1);
        };

        // ─── Load Data ────────────────────────────────────────────
        window.dcLoad = async function (page) {
            page = page || 1;
            var filters = getFilters();
            var ordersBody = document.getElementById('dc-orders-body');
            ordersBody.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:40px"><div class="spin" style="margin:auto"></div></td></tr>';

            try {
                var resp = await axios.get('{{ route("admin.report-discounts.data") }}', { params: { ...filters, page: page } });
                var d = resp.data;

                // KPIs
                document.getElementById('dc-kpi-orders').textContent = d.kpis.total_orders;
                document.getElementById('dc-kpi-discounts').textContent = parseFloat(d.kpis.total_discounts).toLocaleString('ar-EG', { minimumFractionDigits: 2 });
                document.getElementById('dc-count-label').textContent = d.totals.count + ' طلب';

                // Rows
                if (!d.orders.length) {
                    ordersBody.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:40px;color:var(--text-muted)">لا توجد طلبات بخصومات في هذه الفترة</td></tr>';
                } else {
                    ordersBody.innerHTML = d.orders.map(function (o) {
                        return '<tr>' +
                            '<td style="text-align:center"><strong style="color:var(--yellow)">' + o.order_number + '</strong></td>' +
                            '<td style="font-size:12px;color:var(--text-muted); text-align:center">' + fmtDate(o.created_at) + '</td>' +
                            '<td style="text-align:center"><span style="font-size:11px;color:var(--text-muted)">[' + o.client_code + ']</span> ' + escHtml(o.client) + '</td>' +
                            '<td style="text-align:center">' + escHtml(o.callcenter) + '</td>' +
                            '<td style="text-align:center">' + escHtml(o.delivery) + '</td>' +
                            '<td style="text-align:center"><span class="badge" style="background:var(--blue-light);color:var(--blue)">' + o.items_count + '</span></td>' +
                            '<td style="text-align:center"><strong style="color:var(--red)">' + parseFloat(o.discount).toFixed(2) + ' ج' +
                            (o.discount_type === 'percent' ? ' <small style="opacity:.7">(%)</small>' : '') +
                            '</strong></td>' +
                            '<td style="text-align:center"><strong>' + parseFloat(o.total).toFixed(2) + ' ج</strong></td>' +
                            '<td style="text-align:center">' + statusBadge(o.status) + '</td>' +
                            '<td style="text-align:center"><button class="btn btn-sm btn-info" onclick="dcViewOrder(' + o.id + ')">تفاصيل</button></td>' +
                            '</tr>';
                    }).join('');
                }

                // Footer totals
                var t = d.totals;
                document.getElementById('dc-totals-foot').innerHTML =
                    '<tr>' +
                    '<td colspan="6" style="padding:12px 16px">الإجمالي (' + t.count + ' طلب)</td>' +
                    '<td style="padding:12px 16px;color:var(--red)">' + parseFloat(t.total_discounts).toFixed(2) + ' ج</td>' +
                    '<td style="padding:12px 16px;color:var(--yellow)">' + parseFloat(t.total_revenue).toFixed(2) + ' ج</td>' +
                    '<td colspan="2"></td>' +
                    '</tr>';

                // Pagination
                var pag = document.getElementById('dc-pagination');
                if (t.pages > 1) {
                    var html = '<div class="pagination">';
                    for (var i = 1; i <= t.pages; i++) {
                        html += '<a class="' + (i === t.page ? 'active' : '') + '" onclick="dcLoad(' + i + ')">' + i + '</a>';
                    }
                    pag.innerHTML = html + '</div>';
                } else {
                    pag.innerHTML = '';
                }

            } catch (e) {
                console.error(e);
                ordersBody.innerHTML = '<tr><td colspan="10" style="text-align:center;color:var(--red);padding:30px">حدث خطأ أثناء تحميل البيانات</td></tr>';
            }
        };

        // ─── Order Detail Modal ────────────────────────────────────
        window.dcViewOrder = async function (id) {
            document.getElementById('dc-modal-number').textContent = '';
            document.getElementById('dc-modal-body').innerHTML =
                '<div style="text-align:center;padding:40px"><div class="spin" style="margin:auto"></div></div>';
            openModal('modal-dc-detail');

            try {
                var resp = await axios.get('{{ url("/admin/report-discounts") }}/' + id + '/detail');
                var o = resp.data;
                document.getElementById('dc-modal-number').textContent = o.order_number;

                var discountLabel = parseFloat(o.discount).toFixed(2) + ' ج';
                if (o.discount_type === 'percent') discountLabel += ' (' + o.discount + '%)';

                var itemsRows = o.items.map(function (item) {
                    return '<tr>' +
                        '<td>' + escHtml(item.item_name) + '</td>' +
                        '<td style="text-align:center">' + item.quantity + '</td>' +
                        '<td>' + parseFloat(item.unit_price).toFixed(2) + ' ج</td>' +
                        '<td><strong>' + parseFloat(item.total).toFixed(2) + ' ج</strong></td>' +
                        '</tr>';
                }).join('');

                document.getElementById('dc-modal-body').innerHTML = `
                <div class="kpi-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:16px">
                    <div class="kpi-card">
                        <div class="kpi-label">التاريخ</div>
                        <div style="font-size:14px;font-weight:700;margin-top:4px">${fmtDate(o.created_at)}</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-label">العميل</div>
                        <div style="font-size:14px;font-weight:700;margin-top:4px">[${o.client_code}] ${escHtml(o.client)}</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-label">الحالة</div>
                        <div style="margin-top:4px">${statusBadge(o.status)}</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-label">كول سنتر</div>
                        <div style="font-size:14px;font-weight:700;margin-top:4px">${escHtml(o.callcenter)}</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-label">المندوب</div>
                        <div style="font-size:14px;font-weight:700;margin-top:4px">${escHtml(o.delivery)}</div>
                    </div>
                    <div class="kpi-card red">
                        <div class="kpi-label">الخصم</div>
                        <div style="font-size:18px;font-weight:800;margin-top:4px">${discountLabel}</div>
                    </div>
                </div>
                <div style="display:flex;gap:12px;margin-bottom:16px">
                    <div class="kpi-card green" style="flex:1">
                        <div class="kpi-label">رسوم التوصيل</div>
                        <div class="kpi-value">${parseFloat(o.delivery_fee).toFixed(2)} <span class="kpi-sub">ج</span></div>
                    </div>
                    <div class="kpi-card blue" style="flex:1">
                        <div class="kpi-label">الإجمالي بعد الخصم</div>
                        <div class="kpi-value">${parseFloat(o.total).toFixed(2)} <span class="kpi-sub">ج</span></div>
                    </div>
                </div>
                ${o.notes ? '<div class="kpi-card" style="margin-bottom:16px"><div class="kpi-label">ملاحظات</div><div style="margin-top:6px;font-size:14px">' + escHtml(o.notes) + '</div></div>' : ''}
                <div class="card-title" style="margin-bottom:8px">الأصناف</div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>الصنف</th><th>الكمية</th><th>سعر الوحدة</th><th>الإجمالي</th></tr></thead>
                        <tbody>${itemsRows || '<tr><td colspan="4" style="text-align:center;color:var(--text-muted)">لا أصناف</td></tr>'}</tbody>
                    </table>
                </div>`;
            } catch (e) {
                document.getElementById('dc-modal-body').innerHTML =
                    '<div style="color:var(--red);text-align:center;padding:30px">حدث خطأ أثناء تحميل التفاصيل</div>';
            }
        };

        // ─── Helpers ──────────────────────────────────────────────
        function fmtDate(str) {
            if (!str) return '—';
            return new Date(str).toLocaleDateString('ar-EG', {
                year: 'numeric', month: 'short', day: 'numeric',
                hour: '2-digit', minute: '2-digit'
            });
        }
        function escHtml(str) {
            if (str == null) return '—';
            return String(str)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;')
                .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }
        function statusBadge(s) {
            var map = {
                pending: ['badge badge-yellow', 'معلق'],
                received: ['badge', 'مستلم'],
                delivered: ['badge badge-green', 'تم التوصيل'],
                cancelled: ['badge badge-red', 'ملغي'],
            };
            var info = map[s] || ['badge', s];
            return '<span class="' + info[0] + '">' + info[1] + '</span>';
        }

        // ─── Boot ─────────────────────────────────────────────────
        dcLoad(1);

    })();
</script>