@extends('layouts.admin')

@section('page-title', 'الطلبات')

@section('content')
    <div class="section-header">
        <h2> إدارة الطلبات</h2>
        <div style="display:flex;gap:10px;align-items:center;">
            <a href="{{ route('admin.orders.export-pdf') }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}"
                id="export-pdf-btn" class="btn btn-danger" target="_blank">
                تصدير PDF
            </a>
            <button class="btn btn-success" onclick="exportOrdersExcel()" style="background:#217346;color:#fff;">
                تصدير Excel
            </button>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card" style="margin-bottom:20px">
        <div class="filter-bar" style="display: flex; flex-wrap: nowrap; align-items: flex-end; gap: 8px; width: 100%;">
            <div style="width: 15%;">
                <label class="form-label" style="font-size: 12px; margin-bottom: 2px;">بحث</label>
                <input type="text" id="filter-search" class="form-control" placeholder="بحث بالكود / الهاتف / كود العميل"
                    style="width: 100%; min-width: unset;">
            </div>
            <div style="width: 10%;">
                <label class="form-label" style="font-size: 12px; margin-bottom: 2px;">الدور</label>
                <div class="relative group" style="width: 100%; z-index: 55;">
                    <div class="form-control"
                        style="cursor:pointer; display:flex; justify-content:space-between; align-items:center; width: 100%; min-width: unset;">
                        <span id="label-filter-role" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">الكل</span>
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </div>
                    <input type="hidden" id="filter-role" value="">
                    <div class="absolute top-full right-0 w-full opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all bg-white/80 backdrop-blur shadow-lg rounded-md mt-1 overflow-hidden"
                        style="border:1px solid var(--border); background-color: rgba(255, 255, 255, 0.9);">
                        <div class="px-3 py-2 cursor-pointer hover:bg-green-50 hover:text-green-700 text-sm transition-colors text-gray-800"
                            onclick="selectDropdown('filter-role', '', 'الكل')">الكل</div>
                        <div class="px-3 py-2 cursor-pointer hover:bg-green-50 hover:text-green-700 text-sm transition-colors text-gray-800"
                            onclick="selectDropdown('filter-role', 'primary', 'عميل اساسي')">عميل اساسي</div>
                        <div class="px-3 py-2 cursor-pointer hover:bg-green-50 hover:text-green-700 text-sm transition-colors text-gray-800"
                            onclick="selectDropdown('filter-role', 'recipient', 'مستلم')">مستلم</div>
                    </div>
                </div>
            </div>
            <div style="width: 10%;">
                <label class="form-label" style="font-size: 12px; margin-bottom: 2px;">الحالة</label>
                <div class="relative group" style="width: 100%; z-index: 50;">
                    <div class="form-control"
                        style="cursor:pointer; display:flex; justify-content:space-between; align-items:center; width: 100%; min-width: unset;">
                        <span id="label-filter-status" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">الكل</span>
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </div>
                    <input type="hidden" id="filter-status" value="">
                    <div class="absolute top-full right-0 w-full opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all bg-white/80 backdrop-blur shadow-lg rounded-md mt-1 overflow-hidden"
                        style="border:1px solid var(--border); background-color: rgba(255, 255, 255, 0.9);">
                        <div class="px-3 py-2 cursor-pointer hover:bg-green-50 hover:text-green-700 text-sm transition-colors text-gray-800"
                            onclick="selectDropdown('filter-status', '', 'الكل')">الكل</div>
                        <div class="px-3 py-2 cursor-pointer hover:bg-green-50 hover:text-green-700 text-sm transition-colors text-gray-800"
                            onclick="selectDropdown('filter-status', 'pending', 'قيد الانتظار')">قيد الانتظار</div>
                        <div class="px-3 py-2 cursor-pointer hover:bg-green-50 hover:text-green-700 text-sm transition-colors text-gray-800"
                            onclick="selectDropdown('filter-status', 'received', 'مسلم للمندوب')">مسلم للمندوب</div>
                        <div class="px-3 py-2 cursor-pointer hover:bg-green-50 hover:text-green-700 text-sm transition-colors text-gray-800"
                            onclick="selectDropdown('filter-status', 'delivered', 'تم التوصيل')">تم التوصيل</div>
                        <div class="px-3 py-2 cursor-pointer hover:bg-green-50 hover:text-green-700 text-sm transition-colors text-gray-800"
                            onclick="selectDropdown('filter-status', 'cancelled', 'ملغي')">ملغي</div>
                    </div>
                </div>
            </div>
            <div style="width: 10%;">
                <label class="form-label" style="font-size: 12px; margin-bottom: 2px;">كول سنتر</label>
                <div class="relative group" style="width: 100%; z-index: 40;">
                    <div class="form-control"
                        style="cursor:pointer; display:flex; justify-content:space-between; align-items:center; width: 100%; min-width: unset;">
                        <span id="label-filter-callcenter" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">الكل</span>
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </div>
                    <input type="hidden" id="filter-callcenter" value="">
                    <div class="absolute top-full right-0 w-full opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all bg-white/80 backdrop-blur shadow-lg rounded-md mt-1 overflow-hidden"
                        style="border:1px solid var(--border); background-color: rgba(255, 255, 255, 0.9); max-height:200px; overflow-y:auto;">
                        <div class="px-3 py-2 cursor-pointer hover:bg-green-50 hover:text-green-700 text-sm transition-colors text-gray-800"
                            onclick="selectDropdown('filter-callcenter', '', 'الكل')">الكل</div>
                        @foreach($callcenters as $cc)
                            <div class="px-3 py-2 cursor-pointer hover:bg-green-50 hover:text-green-700 text-sm transition-colors text-gray-800"
                                onclick="selectDropdown('filter-callcenter', '{{ $cc->id }}', '{{ $cc->name }}')">
                                {{ $cc->name }}
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div style="width: 10%;">
                <label class="form-label" style="font-size: 12px; margin-bottom: 2px;">الأدمن</label>
                <div class="relative group" style="width: 100%; z-index: 35;">
                    <div class="form-control"
                        style="cursor:pointer; display:flex; justify-content:space-between; align-items:center; width: 100%; min-width: unset;">
                        <span id="label-filter-admin" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">الكل</span>
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </div>
                    <input type="hidden" id="filter-admin" value="">
                    <div class="absolute top-full right-0 w-full opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all bg-white/80 backdrop-blur shadow-lg rounded-md mt-1 overflow-hidden"
                        style="border:1px solid var(--border); background-color: rgba(255, 255, 255, 0.9); max-height:200px; overflow-y:auto;">
                        <div class="px-3 py-2 cursor-pointer hover:bg-green-50 hover:text-green-700 text-sm transition-colors text-gray-800"
                            onclick="selectDropdown('filter-admin', '', 'الكل')">الكل</div>
                        @foreach($admins as $adm)
                            <div class="px-3 py-2 cursor-pointer hover:bg-green-50 hover:text-green-700 text-sm transition-colors text-gray-800"
                                onclick="selectDropdown('filter-admin', '{{ $adm->id }}', '{{ $adm->name }}')">
                                {{ $adm->name }}
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div style="width: 10%;">
                <label class="form-label" style="font-size: 12px; margin-bottom: 2px;">المندوب</label>
                <div class="relative group" style="width: 100%; z-index: 30;">
                    <div class="form-control"
                        style="cursor:pointer; display:flex; justify-content:space-between; align-items:center; width: 100%; min-width: unset;">
                        <span id="label-filter-delivery" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">الكل</span>
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </div>
                    <input type="hidden" id="filter-delivery" value="">
                    <div class="absolute top-full right-0 w-full opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all bg-white/80 backdrop-blur shadow-lg rounded-md mt-1 overflow-hidden"
                        style="border:1px solid var(--border); background-color: rgba(255, 255, 255, 0.9); max-height:200px; overflow-y:auto;">
                        <div class="px-3 py-2 cursor-pointer hover:bg-green-50 hover:text-green-700 text-sm transition-colors text-gray-800"
                            onclick="selectDropdown('filter-delivery', '', 'الكل')">الكل</div>
                        @foreach($deliveries as $d)
                            <div class="px-3 py-2 cursor-pointer hover:bg-green-50 hover:text-green-700 text-sm transition-colors text-gray-800"
                                onclick="selectDropdown('filter-delivery', '{{ $d->id }}', '{{ $d->name }}')">{{ $d->name }}
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div style="width: 10%;">
                <label class="form-label" style="font-size: 12px; margin-bottom: 2px;">تاريخ من</label>
                <input type="date" id="filter-from" class="form-control" placeholder="من" style="width: 100%; min-width: unset;">
            </div>
            <div style="width: 10%;">
                <label class="form-label" style="font-size: 12px; margin-bottom: 2px;">تاريخ إلى</label>
                <input type="date" id="filter-to" class="form-control" placeholder="إلى" style="width: 100%; min-width: unset;">
            </div>
            <div style="display: flex; gap: 5px; margin-bottom: 2px; width: 15%;">
                <button class="btn btn-primary" style="flex: 1; padding: 8px 10px; min-width: unset; justify-content: center;" onclick="loadOrders(1)">بحث</button>
                <button class="btn btn-secondary" style="flex: 1; padding: 8px 10px; min-width: unset; justify-content: center;" onclick="resetFilters()">إعادة</button>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="card" style="padding:0;position:relative">
        <div class="loading-overlay" id="table-loading">
            <div class="spin" style="width:30px;height:30px;border-width:3px"></div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th style="text-align: center;">رقم الطلب</th>
                        <th style="text-align: center;">التاريخ</th>
                        <th style="text-align: center;">العميل</th>
                        <th style="text-align: center;">تم انشاؤه</th>
                        <th style="text-align: center;">المندوب</th>
                        <th style="text-align: center;">عدد الأصناف</th>
                        <th style="text-align: center;">توصيل</th>
                        <th style="text-align: center;">خصم</th>
                        <th style="text-align: center;">الإجمالي</th>
                        <th style="text-align: center;">الحالة</th>
                        <th style="text-align: center;">إجراءات</th>
                    </tr>
                </thead>
                <tbody id="orders-body">
                    <tr>
                        <td colspan="11" style="text-align:center;color:var(--text-muted);padding:40px">جاري التحميل...</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div id="pagination-wrap" style="padding:16px; background: var(--bg);"></div>
    </div>

    @include('admin.orders.partials.view_modal')

    {{-- Cancel Order Modal --}}
    <div class="modal-overlay" id="modal-cancel-order">
        <div class="modal" style="max-width:480px">
            <div class="modal-header" style="background:rgba(239,68,68,.08);border-bottom:1px solid rgba(239,68,68,.2)">
                <h3 style="color:var(--red);display:flex;align-items:center;gap:8px">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    إلغاء الطلب
                </h3>
                <button class="btn-close" onclick="closeModal('modal-cancel-order')" id="cancel-modal-close-btn">✕</button>
            </div>
            <div class="modal-body">
                <p style="color:var(--text-muted);font-size:13px;margin-bottom:16px">
                    سيتم إلغاء الطلب نهائياً ولا يمكن التراجع. يرجى كتابة سبب الإلغاء.
                </p>
                <input type="hidden" id="cancel-order-id">
                <div class="form-group">
                    <label class="form-label" style="font-weight:700">سبب الإلغاء <span
                            style="color:var(--red)">*</span></label>
                    <textarea id="cancel-reason-input" class="form-control" rows="3" maxlength="500"
                        placeholder="اكتب سبب الإلغاء هنا..." style="resize:vertical"></textarea>
                    <div style="font-size:11px;color:var(--text-muted);text-align:left;margin-top:4px"><span
                            id="cancel-reason-count">0</span>/500</div>
                </div>
            </div>
            <div class="modal-footer"
                style="display:flex;gap:8px;justify-content:flex-end;padding:16px 20px;border-top:1px solid var(--border)">
                <button class="btn btn-secondary" onclick="closeModal('modal-cancel-order')"
                    id="cancel-modal-back-btn">تراجع</button>
                <button class="btn btn-danger" id="cancel-confirm-btn" onclick="submitCancelOrder()">تأكيد الإلغاء</button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        var currentPage = 1;
        var currentFilters = {};

        function getFilters() {
            return {
                search: document.getElementById('filter-search').value,
                role: document.getElementById('filter-role').value,
                status: document.getElementById('filter-status').value,
                callcenter_id: document.getElementById('filter-callcenter').value,
                admin_id: document.getElementById('filter-admin').value,
                delivery_id: document.getElementById('filter-delivery').value,
                from: document.getElementById('filter-from').value,
                to: document.getElementById('filter-to').value,
            };
        }

        function resetFilters() {
            document.getElementById('filter-search').value = '';
            document.getElementById('filter-role').value = '';
            document.getElementById('filter-status').value = '';
            document.getElementById('filter-callcenter').value = '';
            document.getElementById('filter-admin').value = '';
            document.getElementById('filter-delivery').value = '';
            document.getElementById('filter-from').value = '';
            document.getElementById('filter-to').value = '';

            document.getElementById('label-filter-role').innerText = 'الكل';
            document.getElementById('label-filter-status').innerText = 'الكل';
            document.getElementById('label-filter-callcenter').innerText = 'الكل';
            document.getElementById('label-filter-admin').innerText = 'الكل';
            document.getElementById('label-filter-delivery').innerText = 'الكل';

            loadOrders(1);
        }

        async function loadOrders(page = 1) {
            currentPage = page;
            currentFilters = getFilters();
            document.getElementById('table-loading').classList.add('show');

            // Update export PDF link
            const params = new URLSearchParams({ ...currentFilters, page });
            Object.keys(currentFilters).forEach(k => { if (!currentFilters[k]) params.delete(k); });
            const exportBtn = document.getElementById('export-pdf-btn');
            const baseUrl = '{{ route("admin.orders.export-pdf") }}';
            exportBtn.href = baseUrl + (params.toString() ? '?' + params.toString() : '');

            try {
                const { data } = await axios.get('{{ route("admin.orders.index") }}', {
                    params: { ...currentFilters, page },
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });

                const body = document.getElementById('orders-body');
                if (!data.data.length) {
                    body.innerHTML = '<tr><td colspan="11" style="text-align:center;color:var(--text-muted);padding:40px">لا توجد طلبات</td></tr>';
                    document.getElementById('pagination-wrap').innerHTML = '';
                    return;
                }

                body.innerHTML = data.data.map(o => {
                    const itemsSummary = o.items ? o.items.map(i => `${i.item_name}×${i.quantity}`).join('، ').substring(0, 60) + '...' : '—';
                    return `<tr>
                            <td><strong style="color:var(--yellow) text-align: center;">${o.order_number}</strong></td>
                            <td style="font-size:12px;color:var(--text-muted) text-align: center;">${formatDate(o.created_at)}</td>
                            <td style="text-align: center;">${o.client?.name ?? '—'}</td>
                            <td style="text-align: center;">${o.callcenter?.name ?? o.admin?.name ?? '—'}
                                ${!o.callcenter && o.admin ? '<div class="kpi-sub" style="font-size:11px;margin-top:3px;color:var(--info);">\u0623\u062f\u0645\u0646</div>' : ''}
                            </td>
                            <td style="text-align: center;">
                                ${o.delivery?.name ?? '—'}
                                ${o.is_delivery_chosen ? '<div class="kpi-sub" style="font-size:11px; margin-top:4px; color:var(--red-dark); text-shadow: 0px 0px 0px #ff8d8d;">تم اختيار المندوب</div>' : ''}
                            </td>
                            <td style="text-align: center;">${o.items_count}</td>
                            <td style="text-align: center;">${parseFloat(o.delivery_fee || 0).toFixed(2)} ج</td>
                            <td style="text-align: center;">${parseFloat(o.discount || 0).toFixed(2)} ج</td>
                            <td style="text-align: center;"><strong>${parseFloat(o.total || 0).toFixed(2)} ج</strong></td>
                            <td style="text-align: center;">${statusBadge(o.status)}</td>
                            <td style="text-align: center;">
                                <div style="display:flex;gap:6px;justify-content: center;">
                                    <button class="btn btn-sm btn-info" onclick="viewOrder(${o.id})">عـرض</button>
                                    ${o.status !== 'cancelled' && o.status !== 'delivered' ? `<button class="btn btn-sm btn-danger" onclick="cancelOrder(${o.id})">إلغاء</button>` : ''}
                                </div>
                            </td>
                        </tr>`;
                }).join('');

                renderPagination(data.last_page, data.current_page);
            } catch (e) {
                console.error(e);
                document.getElementById('orders-body').innerHTML = '<tr><td colspan="11" style="text-align:center;color:var(--red)">حدث خطأ</td></tr>';
            } finally {
                document.getElementById('table-loading').classList.remove('show');
            }
        }

        function renderPagination(lastPage, current) {
            if (lastPage <= 1) { document.getElementById('pagination-wrap').innerHTML = ''; return; }
            let html = '<div class="pagination">';
            html += `<a class="${current === 1 ? 'disabled' : ''}" onclick="loadOrders(${current - 1})">‹</a>`;
            for (let i = 1; i <= lastPage; i++) {
                if (i === 1 || i === lastPage || Math.abs(i - current) <= 2) {
                    html += `<a class="${i === current ? 'active' : ''}" onclick="loadOrders(${i})">${i}</a>`;
                } else if (Math.abs(i - current) === 3) {
                    html += '<span>…</span>';
                }
            }
            html += `<a class="${current === lastPage ? 'disabled' : ''}" onclick="loadOrders(${current + 1})">›</a>`;
            html += '</div>';
            document.getElementById('pagination-wrap').innerHTML = html;
        }



        function cancelOrder(id) {
            document.getElementById('cancel-order-id').value = id;
            document.getElementById('cancel-reason-input').value = '';
            document.getElementById('cancel-reason-count').textContent = '0';
            openModal('modal-cancel-order');
            setTimeout(() => document.getElementById('cancel-reason-input').focus(), 200);
        }

        async function submitCancelOrder() {
            const id = document.getElementById('cancel-order-id').value;
            const reason = document.getElementById('cancel-reason-input').value.trim();

            if (!reason) {
                document.getElementById('cancel-reason-input').style.borderColor = 'var(--red)';
                document.getElementById('cancel-reason-input').focus();
                if (typeof showError === 'function') showError('يجب كتابة سبب الإلغاء');
                return;
            }
            document.getElementById('cancel-reason-input').style.borderColor = '';

            const btn = document.getElementById('cancel-confirm-btn');
            const closeBtn = document.getElementById('cancel-modal-close-btn');
            const backBtn = document.getElementById('cancel-modal-back-btn');
            btn.disabled = closeBtn.disabled = backBtn.disabled = true;
            btn.textContent = 'جاري الإلغاء...';

            try {
                const { data } = await axios.patch(`/admin/orders/${id}/cancel`, { reason });
                closeModal('modal-cancel-order');
                if (typeof showSuccess === 'function') showSuccess(data.message);
                loadOrders(currentPage);
            } catch (e) {
                if (typeof showError === 'function') showError(e.response?.data?.message ?? e.response?.data?.errors?.reason?.[0] ?? 'حدث خطأ');
            } finally {
                btn.disabled = closeBtn.disabled = backBtn.disabled = false;
                btn.textContent = 'تأكيد الإلغاء';
            }
        }

        // Character counter for cancel reason
        document.getElementById('cancel-reason-input').addEventListener('input', function () {
            document.getElementById('cancel-reason-count').textContent = this.value.length;
            if (this.value.trim()) this.style.borderColor = '';
        });

        // Close cancel modal on overlay click
        document.getElementById('modal-cancel-order').addEventListener('click', function (e) {
            if (e.target === this) closeModal('modal-cancel-order');
        });

        loadOrders(1);

        async function exportOrdersExcel() {
            try {
                // جلب كل البيانات مع نفس الفلاتر الحالية بدون pagination
                const filters = {
                    search: document.getElementById('filter-search').value,
                    role: document.getElementById('filter-role').value,
                    status: document.getElementById('filter-status').value,
                    callcenter_id: document.getElementById('filter-callcenter').value,
                    admin_id: document.getElementById('filter-admin').value,
                    delivery_id: document.getElementById('filter-delivery').value,
                    from: document.getElementById('filter-from').value,
                    to: document.getElementById('filter-to').value,
                    per_page: 9999
                };

                const { data } = await axios.get('{{ route("admin.orders.index") }}', {
                    params: filters,
                    headers: { 'Accept': 'application/json' }
                });

                const columns = [
                    { header: 'رقم الطلب', key: 'order_number', width: 18 },
                    { header: 'التاريخ', key: 'created_at', width: 20 },
                    { header: 'العميل', key: 'client.name', width: 22 },
                    { header: 'هاتف العميل', key: 'client.phone', width: 16 },
                    { header: 'تم انشاؤه', key: 'creator_name', width: 20 },
                    { header: 'المندوب', key: 'delivery.name', width: 18 },
                    { header: 'اختيار المندوب', key: 'delivery_chosen_label', width: 16 },
                    { header: 'عدد الأصناف', key: 'items_count', width: 14 },
                    { header: 'رسوم التوصيل', key: 'delivery_fee', width: 16 },
                    { header: 'الخصم', key: 'discount', width: 12 },
                    { header: 'الإجمالي', key: 'total', width: 14 },
                    { header: 'الحالة', key: 'status', width: 14 },
                ];

                // تحويل التواريخ والحالات
                const statusMap = { pending: 'قيد الانتظار', received: 'مسلم للمندوب', delivered: 'تم التوصيل', cancelled: 'ملغي' };
                const rows = data.data.map(o => ({
                    ...o,
                    created_at: o.created_at ? new Date(o.created_at).toLocaleDateString('ar-EG') : '—',
                    status: statusMap[o.status] || o.status,
                    creator_name: o.callcenter?.name ?? o.admin?.name ?? '—',
                    delivery_chosen_label: o.is_delivery_chosen ? 'نعم' : '—',
                }));

                exportToExcel(rows, columns, 'orders-' + new Date().toISOString().slice(0, 10), 'الطلبات');
                if (typeof showSuccess === 'function') showSuccess('تم تصدير الطلبات بنجاح');
            } catch (e) {
                if (typeof showError === 'function') showError('حدث خطأ أثناء التصدير');
                console.error(e);
            }
        }
    </script>
@endpush