{{-- Admin Discount Reports SPA Partial --}}
<div class="section-header">
    <h2>تقارير الخصومات</h2>
    <button class="btn btn-success" onclick="exportDiscountsExcel()" style="background:#217346; color:#fff; padding: 10px 15px; white-space:nowrap;">تصدير Excel</button>
</div>

{{-- ─── Filters ────────────────────────────────────────────── --}}
<div class="card" style="margin-bottom:20px">
    <div class="filter-bar" style="display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end; padding:10px 15px;">
        {{-- General Search --}}
        <div style="flex:1.5; min-width:220px; display:flex; flex-direction:column; gap:4px; position:relative">
            <label style="font-size:12px; color:var(--text-muted); font-weight:600; white-space:nowrap;">بحث شامل (كود، عميل، هاتف)</label>
            <div style="position:relative">
                <input type="text" id="dc-search" class="form-control"
                    placeholder="ابحث..." autocomplete="off"
                    style="padding-left:32px; width:100%;" onkeydown="if(event.key === 'Enter') dcLoad()">
            </div>
        </div>

        {{-- Call Center --}}
        <div style="flex:1; min-width:150px; display:flex; flex-direction:column; gap:4px;">
            <label style="font-size:12px; color:var(--text-muted); font-weight:600; white-space:nowrap;">الكول سنتر</label>
            <div class="relative group" style="z-index: 50;">
                <div class="form-control" style="cursor:pointer; display:flex; justify-content:space-between; align-items:center; white-space:nowrap; overflow:hidden;">
                    <span id="label-dc-callcenter" style="text-overflow:ellipsis; overflow:hidden;">كل الكول سنتر</span>
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </div>
                <input type="hidden" id="dc-callcenter" value="">
                <div class="absolute top-full right-0 w-full opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all bg-white shadow-lg rounded-md mt-1 overflow-hidden"
                    style="border:1px solid var(--border); background-color: white; max-height:200px; overflow-y:auto; z-index:100;">
                    <div class="px-3 py-2 cursor-pointer hover:bg-green-50 hover:text-green-700 text-sm transition-colors text-gray-800"
                        onclick="selectDropdown('dc-callcenter', '', 'كل الكول سنتر')">كل الكول سنتر</div>
                    @foreach($callcenters as $cc)
                        <div class="px-3 py-2 cursor-pointer hover:bg-green-50 hover:text-green-700 text-sm transition-colors text-gray-800"
                            onclick="selectDropdown('dc-callcenter', '{{ $cc->id }}', '{{ $cc->name }}')">{{ $cc->name }}
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Admin --}}
        <div style="flex:1; min-width:150px; display:flex; flex-direction:column; gap:4px;">
            <label style="font-size:12px; color:var(--text-muted); font-weight:600; white-space:nowrap;">المدير</label>
            <div class="relative group" style="z-index: 40;">
                <div class="form-control" style="cursor:pointer; display:flex; justify-content:space-between; align-items:center; white-space:nowrap; overflow:hidden;">
                    <span id="label-dc-admin" style="text-overflow:ellipsis; overflow:hidden;">كل المديرين</span>
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </div>
                <input type="hidden" id="dc-admin" value="">
                <div class="absolute top-full right-0 w-full opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all bg-white shadow-lg rounded-md mt-1 overflow-hidden"
                    style="border:1px solid var(--border); background-color: white; max-height:200px; overflow-y:auto; z-index:100;">
                    <div class="px-3 py-2 cursor-pointer hover:bg-green-50 hover:text-green-700 text-sm transition-colors text-gray-800"
                        onclick="selectDropdown('dc-admin', '', 'كل المديرين')">كل المديرين</div>
                    @foreach($admins as $adm)
                        <div class="px-3 py-2 cursor-pointer hover:bg-green-50 hover:text-green-700 text-sm transition-colors text-gray-800"
                            onclick="selectDropdown('dc-admin', '{{ $adm->id }}', '{{ $adm->name }}')">{{ $adm->name }}
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Date From --}}
        <div style="flex:0.8; min-width:130px; display:flex; flex-direction:column; gap:4px;">
            <label style="font-size:12px; color:var(--text-muted); font-weight:600; white-space:nowrap;">من</label>
            <input type="date" id="dc-from" class="form-control" style="width:100%">
        </div>

        {{-- Date To --}}
        <div style="flex:0.8; min-width:130px; display:flex; flex-direction:column; gap:4px;">
            <label style="font-size:12px; color:var(--text-muted); font-weight:600; white-space:nowrap;">إلى</label>
            <input type="date" id="dc-to" class="form-control" style="width:100%">
        </div>

        {{-- Actions --}}
        <div style="display:flex; gap:6px; align-items:flex-end; flex-shrink:0;">
            <button class="btn btn-primary" onclick="dcLoad()" style="padding: 10px 15px; white-space:nowrap;">عرض</button>
            <button class="btn btn-secondary" onclick="dcReset()" style="padding: 10px 15px; white-space:nowrap;">إعادة</button>
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
                    <th style="text-align:center">تم انشاؤه</th>
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
            <div style="text-align:center;padding:40px;color:var(--text-muted)">جاري التحميل...</div>
        </div>
        <div
            style="display:flex;align-items:center;justify-content:flex-end;gap:8px;padding:14px 22px;border-top:1px solid var(--border);background:var(--bg);border-radius:0 0 18px 18px;">
            <button class="btn btn-secondary" onclick="closeModal('modal-dc-detail')">إغلاق</button>
            <a id="dc-modal-pdf-btn" href="#" target="_blank" class="btn"
                style="background:var(--red);color:#fff;gap:6px;text-decoration:none;"
                onclick="if(this.href==='#'){event.preventDefault();}">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                إنشاء PDF
            </a>
        </div>
    </div>
</div>

<script>
    (function () {


        // ─── Filters ──────────────────────────────────────────────
        function getFilters() {
            return {
                from: document.getElementById('dc-from').value,
                to: document.getElementById('dc-to').value,
                search: document.getElementById('dc-search').value,
                callcenter_id: document.getElementById('dc-callcenter').value,
                admin_id: document.getElementById('dc-admin').value,
            };
        }

        window.dcReset = function () {
            document.getElementById('dc-from').value = '';
            document.getElementById('dc-to').value = '';
            document.getElementById('dc-search').value = '';
            document.getElementById('dc-callcenter').value = '';
            document.getElementById('dc-admin').value = '';

            const ccLabel = document.getElementById('label-dc-callcenter');
            if (ccLabel) ccLabel.innerText = 'كل الكول سنتر';

            const admLabel = document.getElementById('label-dc-admin');
            if (admLabel) admLabel.innerText = 'كل المديرين';

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
                document.getElementById('dc-kpi-discounts').textContent = parseFloat(d.kpis.total_discounts).toLocaleString('en-US', { minimumFractionDigits: 2 });
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
                            '<td style="text-align:center">' + escHtml(o.callcenter) + (o.creator_type === 'admin' ? ' <span class="badge badge-blue" style="font-size:9px; padding:1px 4px;">أدمن</span>' : '') + '</td>' +
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
            openModal('modal-dc-detail');
            document.getElementById('dc-modal-body').innerHTML = '<div style="display:flex;flex-direction:column;align-items:center;padding:40px;color:var(--text-muted);"><div class="spin" style="margin-bottom:16px;"></div><div>جاري تحميل التفاصيل...</div></div>';
            document.getElementById('dc-modal-pdf-btn').href = `/admin/orders/${id}/pdf`;

            try {
                var resp = await axios.get('{{ url("/admin/report-discounts") }}/' + id + '/detail');
                var o = resp.data.order;
                document.getElementById('dc-modal-number').textContent = o.order_number;

                let html = `<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:16px; margin-bottom: 20px;">`;
                html += `<div style="background:var(--bg); border-radius:12px; padding:16px; border:1px solid var(--border); box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
                    <div style="font-size:14px; font-weight:700; color:var(--text-muted); margin-bottom:12px; display:flex; align-items:center; gap:8px;">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        بيانات العميل والتوصيل
                    </div>
                    <div style="display:flex; flex-direction:column; gap:10px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; padding-bottom:8px; border-bottom:1px solid var(--border);">
                            <span style="color:var(--text-muted); font-size:13px;">العميل</span>
                            <span style="font-weight:600;">${o.client?.name ?? '—'} <span style="color:var(--text-muted); font-size:12px;">(${o.client?.code ?? ''})</span></span>
                        </div>
                        <div style="display:flex; justify-content:space-between; align-items:center; padding-bottom:8px; border-bottom:1px solid var(--border);">
                            <span style="color:var(--text-muted); font-size:13px;">الهاتف</span>
                            <span style="font-weight:600; direction:ltr;">${o.client?.phone ?? '—'}</span>
                        </div>
                        <div style="display:flex; justify-content:space-between; align-items:center; padding-bottom:8px; border-bottom:1px solid var(--border);">
                            <span style="color:var(--text-muted); font-size:13px;">العنوان</span>
                            <span style="font-weight:600; text-align:left;">${o.client_address ?? '—'}</span>
                        </div>
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <span style="color:var(--text-muted); font-size:13px;">المندوب</span>
                            <span style="font-weight:600; color:var(--yellow);">${o.delivery?.name ?? '—'}</span>
                        </div>
                    </div>
                </div>`;
                html += `<div style="background:var(--bg); border-radius:12px; padding:16px; border:1px solid var(--border); box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
                    <div style="font-size:14px; font-weight:700; color:var(--text-muted); margin-bottom:12px; display:flex; align-items:center; justify-content:space-between;">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            الملخص المالي
                        </div>
                        <div>${statusBadge(o.status)}</div>
                    </div>
                    <div style="display:flex; flex-direction:column; gap:10px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; padding-bottom:8px; border-bottom:1px solid var(--border);">
                            <span style="color:var(--text-muted); font-size:13px;">تم انشاؤه</span>
                            <span style="font-weight:600;">${o.callcenter?.name ?? '—'}</span>
                        </div>
                        <div style="display:flex; justify-content:space-between; align-items:center; padding-bottom:8px; border-bottom:1px solid var(--border);">
                            <span style="color:var(--text-muted); font-size:13px;">رسوم التوصيل</span>
                            <span style="font-weight:600;">${parseFloat(o.delivery_fee).toFixed(2)} ج</span>
                        </div>
                        ${parseFloat(o.discount) > 0 ? `<div style="display:flex; justify-content:space-between; align-items:center; padding-bottom:8px; border-bottom:1px solid var(--border);">
                            <span style="color:var(--text-muted); font-size:13px;">الخصم</span>
                            <span style="font-weight:600; color:var(--red);">${parseFloat(o.discount).toFixed(2)} ${o.discount_type === 'percent' ? '%' : 'ج'}</span>
                        </div>` : ''}
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:4px;">
                            <span style="font-size:14px; font-weight:700;">الإجمالي النهائي</span>
                            <strong style="color:var(--yellow); font-size:18px;">${parseFloat(o.total).toFixed(2)} ج</strong>
                        </div>
                    </div>
                </div></div>`;
                if (o.notes) {
                    html += `<div style="display:flex; align-items:flex-start; gap:12px; background:var(--bg); border:1px solid var(--border); border-radius:10px; padding:12px; margin-bottom:20px;">
                        <div style="color:var(--text-muted); margin-top:2px;">
                            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        </div>
                        <div>
                            <div style="font-size:12px; font-weight:700; color:var(--text-muted); margin-bottom:4px;">ملاحظات الطلب</div>
                            <div style="font-size:14px; line-height:1.5;">${o.notes}</div>
                        </div>
                    </div>`;
                }
                html += `<div style="background:var(--bg); border-radius:12px; border:1px solid var(--border); overflow:hidden; margin-bottom:20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
                    <div style="padding:12px 16px; background:rgba(255,255,255,0.02); border-bottom:1px solid var(--border); font-size:14px; font-weight:700; display:flex; align-items:center; gap:8px;">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                        الأصناف (${o.items.length})
                    </div>
                    <div class="table-wrap" style="margin:0; border:none; border-radius:0;">
                        <table style="margin:0; width:100%; border-collapse:collapse;">
                            <thead style="background:transparent;">
                                <tr style="border-bottom:1px solid var(--border);">
                                    <th style="padding:10px 16px; text-align:right;">الصنف</th>
                                    <th style="padding:10px 16px; text-align:right;">المتجر</th>
                                    <th style="padding:10px 16px; text-align:center;">الكمية</th>
                                    <th style="padding:10px 16px; text-align:center;">السعر</th>
                                    <th style="padding:10px 16px; text-align:left;">الإجمالي</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${o.items.map(i => `<tr style="border-bottom:1px solid var(--border);">
                                    <td style="padding:12px 16px; font-weight:600;">${i.item_name}</td>
                                    <td style="padding:12px 16px; color:var(--text-muted); font-size:13px;">${i.shop}</td>
                                    <td style="padding:12px 16px; text-align:center;">
                                        <span style="background:rgba(255,255,255,0.05); padding:2px 8px; border-radius:12px; font-size:12px; border:1px solid var(--border);">${i.quantity}</span>
                                    </td>
                                    <td style="padding:12px 16px; text-align:center;">${parseFloat(i.unit_price).toFixed(2)} ج</td>
                                    <td style="padding:12px 16px; text-align:left; font-weight:700; color:var(--yellow);">${parseFloat(i.total).toFixed(2)} ج</td>
                                </tr>`).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>`;

                document.getElementById('dc-modal-body').innerHTML = html;
            } catch (e) {
                console.error(e);
                document.getElementById('dc-modal-body').innerHTML =
                    '<div style="color:var(--red);text-align:center;padding:30px">حدث خطأ أثناء تحميل التفاصيل</div>';
            }
        };

        // ─── Helpers ──────────────────────────────────────────────
        function fmtDate(str) {
            if (!str) return '—';
            return new Date(str).toLocaleDateString('en-US', {
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
                received: ['badge', 'مسلم للمندوب'],
                delivered: ['badge badge-green', 'تم التوصيل'],
                cancelled: ['badge badge-red', 'ملغي'],
            };
            var info = map[s] || ['badge', s];
            return '<span class="' + info[0] + '">' + info[1] + '</span>';
        }

        // ─── Boot ─────────────────────────────────────────────────
        dcLoad(1);

        window.exportDiscountsExcel = async function () {
            try {
                var filters = getFilters();
                const resp = await axios.get('{{ route("admin.report-discounts.data") }}', {
                    params: { ...filters, page: 1, per_page: 9999 }
                });
                const columns = [
                    { header: 'رقم الطلب', key: 'order_number', width: 18 },
                    { header: 'التاريخ', key: 'created_at', width: 20 },
                    { header: 'العميل', key: 'client', width: 22 },
                    { header: 'كود العميل', key: 'client_code', width: 12 },
                    { header: 'تم انشاؤه', key: 'callcenter', width: 18 },
                    { header: 'المندوب', key: 'delivery', width: 18 },
                    { header: 'الخصم', key: 'discount', width: 12 },
                    { header: 'نوع الخصم', key: 'discount_type', width: 12 },
                    { header: 'الإجمالي', key: 'total', width: 14 },
                ];
                const rows = resp.data.orders.map(o => ({
                    ...o,
                    created_at: o.created_at ? new Date(o.created_at).toLocaleDateString('ar-EG') : '—',
                    discount_type: o.discount_type === 'percent' ? '%' : 'ج',
                }));
                exportToExcel(rows, columns, 'discounts-' + new Date().toISOString().slice(0, 10), 'الخصومات');
                if (typeof showSuccess === 'function') showSuccess('تم التصدير');
            } catch (e) {
                if (typeof showError === 'function') showError('حدث خطأ');
                console.error(e);
            }
        };

    })();
</script>