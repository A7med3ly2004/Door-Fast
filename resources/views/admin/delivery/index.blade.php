@extends('layouts.admin')

@section('page-title', 'إدارة المناديب')

@section('content')
    <div class="section-header">
        <h2>🚴 إدارة المناديب</h2>
        <button class="btn btn-primary" onclick="openModal('modal-add-delivery')">➕ إضافة مندوب</button>
    </div>

    <div class="section-header">
        <h2>🚴 مناديب التوصيل الأساسي</h2>
    </div>

    <div class="card" style="padding:0">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>الاسم</th>
                        <th>الكود</th>
                        <th>اسم المستخدم</th>
                        <th>الهاتف</th>
                        <th>النوع</th>
                        <th>حالة المندوب</th>
                        <th>مُوصَّلة اليوم</th>
                        <th>إيراد اليوم</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($deliveries as $d)
                        <tr id="delivery-row-{{ $d['id'] }}">
                            <td><strong>{{ $d['name'] }}</strong></td>
                            <td><span class="badge badge-gray">{{ $d['code'] ?? '—' }}</span></td>
                            <td><code style="color:var(--yellow)">{{ $d['username'] }}</code></td>
                            <td>{{ $d['phone'] ?? '—' }}</td>
                            <td><span class="badge" style="background:var(--blue-light);color:var(--blue)">أساسي</span></td>
                            <td>
                                <button id="status-btn-{{ $d['id'] }}"
                                    onclick="toggleActive({{ $d['id'] }}, this, {{ json_encode($d) }})"
                                    style="display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:20px;border:none;cursor:pointer;font-family:'Cairo',sans-serif;font-size:12px;font-weight:700;transition:all .2s ease;
                                    {{ $d['is_active'] ? 'background:rgba(34,197,94,.15);color:var(--success);' : 'background:rgba(220,38,38,.12);color:var(--red);' }}"
                                    data-active="{{ $d['is_active'] ? '1' : '0' }}">
                                    {{ $d['is_active'] ? '✓ نشط' : '✗ غير نشط' }}
                                </button>
                            </td>
                            <td><span class="badge badge-green">{{ $d['completed'] }}</span></td>
                            <td>{{ number_format($d['revenue'], 2) }} ج</td>
                            <td>
                                <div style="display:flex;gap:6px">
                                    <button class="btn btn-sm btn-secondary"
                                        onclick="openEditDelivery({{ json_encode($d) }})">تعديل</button>
                                    <button class="btn btn-sm btn-warning"
                                        onclick="openSettlement({{ $d['id'] }}, '{{ addslashes($d['name']) }}')">تسوية</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align:center;color:var(--text-muted);padding:40px">لا مناديب</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="section-header" style="margin-top:30px">
        <h2>🚴 مناديب التوصيل الاحتياطي</h2>
    </div>

    <div class="card" style="padding:0">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>الاسم</th>
                        <th>الكود</th>
                        <th>اسم المستخدم</th>
                        <th>الهاتف</th>
                        <th>النوع</th>
                        <th>حالة المندوب</th>
                        <th>مُوصَّلة اليوم</th>
                        <th>إيراد اليوم</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reserveDeliveries as $d)
                        <tr id="delivery-row-{{ $d['id'] }}">
                            <td><strong>{{ $d['name'] }}</strong></td>
                            <td><span class="badge badge-gray">{{ $d['code'] ?? '—' }}</span></td>
                            <td><code style="color:var(--yellow)">{{ $d['username'] }}</code></td>
                            <td>{{ $d['phone'] ?? '—' }}</td>
                            <td><span class="badge" style="background:var(--red-light);color:var(--red)">احتياطي</span></td>
                            <td>
                                <button id="status-btn-{{ $d['id'] }}"
                                    onclick="toggleActive({{ $d['id'] }}, this, {{ json_encode($d) }})"
                                    style="display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:20px;border:none;cursor:pointer;font-family:'Cairo',sans-serif;font-size:12px;font-weight:700;transition:all .2s ease;
                                    {{ $d['is_active'] ? 'background:rgba(34,197,94,.15);color:var(--success);' : 'background:rgba(220,38,38,.12);color:var(--red);' }}"
                                    data-active="{{ $d['is_active'] ? '1' : '0' }}">
                                    {{ $d['is_active'] ? '✓ نشط' : '✗ غير نشط' }}
                                </button>
                            </td>
                            <td><span class="badge badge-green">{{ $d['completed'] }}</span></td>
                            <td>{{ number_format($d['revenue'], 2) }} ج</td>
                            <td>
                                <div style="display:flex;gap:6px">
                                    <button class="btn btn-sm btn-secondary"
                                        onclick="openEditDelivery({{ json_encode($d) }})">تعديل</button>
                                    <button class="btn btn-sm btn-warning"
                                        onclick="openSettlement({{ $d['id'] }}, '{{ addslashes($d['name']) }}')">تسوية</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="text-align:center;color:var(--text-muted);padding:40px">لا مناديب احتياطيين
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Add Modal --}}
    <div class="modal-overlay" id="modal-add-delivery">
        <div class="modal">
            <div class="modal-header">
                <h3>➕ إضافة مندوب جديد</h3><button class="btn-close" onclick="closeModal('modal-add-delivery')">✕</button>
            </div>
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group"><label class="form-label">الاسم *</label><input id="add-name" type="text"
                            class="form-control"></div>
                    <div class="form-group"><label class="form-label">كود المندوب</label><input id="add-code" type="text"
                            class="form-control" placeholder="تلقائي إذا ترك فارغاً"></div>
                    <div class="form-group"><label class="form-label">اسم المستخدم *</label><input id="add-username"
                            type="text" class="form-control"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label class="form-label">كلمة المرور *</label><input id="add-password"
                            type="password" class="form-control"></div>
                    <div class="form-row">
                        <div class="form-group"><label class="form-label">الهاتف</label><input id="add-phone" type="text"
                                class="form-control"></div>
                        <div class="form-group">
                            <label class="form-label">النوع</label>
                            <select id="add-role" class="form-select">
                                <option value="delivery">مندوب أساسي</option>
                                <option value="reserve_delivery">مندوب احتياطي</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('modal-add-delivery')">إلغاء</button>
                <button class="btn btn-primary" onclick="addDelivery()">حفظ</button>
            </div>
        </div>
    </div>

    {{-- Edit Modal --}}
    <div class="modal-overlay" id="modal-edit-delivery">
        <div class="modal">
            <div class="modal-header">
                <h3>✏️ تعديل المندوب</h3><button class="btn-close" onclick="closeModal('modal-edit-delivery')">✕</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="edit-id">
                <div class="form-row">
                    <div class="form-group"><label class="form-label">الاسم *</label><input id="edit-name" type="text"
                            class="form-control"></div>
                    <div class="form-group"><label class="form-label">كود المندوب</label><input id="edit-code" type="text"
                            class="form-control"></div>
                    <div class="form-group"><label class="form-label">الهاتف</label><input id="edit-phone" type="text"
                            class="form-control"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label class="form-label">كلمة مرور جديدة (اختياري)</label><input
                            id="edit-password" type="password" class="form-control"></div>
                </div>
                <div class="form-group">
                    <label class="form-label">الحالة</label>
                    <select id="edit-active" class="form-select">
                        <option value="1">نشط</option>
                        <option value="0">غير نشط</option>
                    </select>
                </div>
                <div style="margin-top:20px;text-align:center">
                    <button class="btn btn-warning" style="width:100%" onclick="openIncentiveModal()">📊 إعدادات شرائح الحوافز</button>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('modal-edit-delivery')">إلغاء</button>
                <button class="btn btn-primary" onclick="saveDelivery()">حفظ التعديلات</button>
            </div>
        </div>
    </div>

    {{-- Settlement Modal --}}
    <div class="modal-overlay" id="modal-settlement">
        <div class="modal modal-lg">
            <div class="modal-header">
                <h3>💰 تسوية المندوب — <span id="settle-delivery-name"></span></h3>
                <button class="btn-close" onclick="closeModal('modal-settlement')">✕</button>
            </div>
            <div class="modal-body" id="settle-body">
                <div style="text-align:center;padding:40px">
                    <div class="spin" style="margin:auto"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Incentive Slices Modal --}}
    <div class="modal-overlay" id="modal-incentive-slices">
        <div class="modal modal-lg">
            <div class="modal-header">
                <h3>📊 نظام شرائح الحوافز — <span id="inc-delivery-name"></span></h3>
                <button class="btn-close" onclick="closeModal('modal-incentive-slices')">✕</button>
            </div>
            <div class="modal-body">
                <p style="font-size:13px;color:var(--text-muted);margin-bottom:15px">تنبيه: ستبدأ الشريحة الأولى من رقم 1، وكل شريحة تالية تتبع نهاية التي تسبقها.</p>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th style="width:100px">رقم الشريحة</th>
                                <th>من (عدد طلبات)</th>
                                <th>إلى (عدد طلبات)</th>
                                <th>المبلغ (لكل طلب)</th>
                            </tr>
                        </thead>
                        <tbody id="inc-slices-body">
                            <!-- JS will generate 5 rows -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('modal-incentive-slices')">رجوع</button>
                <button class="btn btn-primary" onclick="confirmIncentiveSlices()">موافق</button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        async function addDelivery() {
            try {
                const { data } = await axios.post('{{ route("admin.delivery.store") }}', {
                    name: document.getElementById('add-name').value,
                    username: document.getElementById('add-username').value,
                    password: document.getElementById('add-password').value,
                    phone: document.getElementById('add-phone').value,
                    code: document.getElementById('add-code').value,
                    role: document.getElementById('add-role').value,
                });
                showSuccess(data.message); window.location.reload();
            } catch (e) { showError(e.response?.data?.errors ? Object.values(e.response.data.errors).flat().join(' | ') : 'حدث خطأ'); }
        }

        function openEditDelivery(d) {
            document.getElementById('edit-id').value = d.id;
            document.getElementById('edit-name').value = d.name;
            document.getElementById('edit-code').value = d.code ?? '';
            document.getElementById('edit-phone').value = d.phone ?? '';
            document.getElementById('edit-password').value = '';
            document.getElementById('edit-active').value = d.is_active ? '1' : '0';
            _tempSlices = d.incentive_slices || [];
            document.getElementById('inc-delivery-name').textContent = d.name;
            openModal('modal-edit-delivery');
        }

        let _tempSlices = [];
        window.openIncentiveModal = function() {
            renderSlicesForm();
            openModal('modal-incentive-slices');
        }

        function renderSlicesForm() {
            const body = document.getElementById('inc-slices-body');
            body.innerHTML = '';
            
            // Ensure we have 5 slices structure
            let slices = _tempSlices;
            if (!slices || slices.length === 0) {
                slices = [
                    {from: 1, to: 5, amount: 0},
                    {from: 6, to: 10, amount: 0},
                    {from: 11, to: 15, amount: 0},
                    {from: 16, to: 20, amount: 0},
                    {from: 21, to: 999999, amount: 0}
                ];
            }

            for(let i=1; i<=5; i++) {
                const s = slices[i-1] || {from: 0, to: 0, amount: 0};
                const fromVal = (i === 1) ? 1 : (parseInt(slices[i-2].to) + 1);
                
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>الشريحة ${i}</td>
                    <td><input type="number" class="form-control" value="${fromVal}" readonly disabled></td>
                    <td>
                        ${i < 5 
                            ? `<input type="number" class="form-control slice-to" data-idx="${i-1}" value="${s.to}" oninput="updateSlicesRanges()">`
                            : `<input type="text" class="form-control" value="∞ (إلى ما لا نهاية)" disabled>`
                        }
                    </td>
                    <td><input type="number" class="form-control slice-amount" data-idx="${i-1}" value="${s.amount}" step="0.1"></td>
                `;
                body.appendChild(tr);
            }
        }

        window.updateSlicesRanges = function() {
            const sliceToInputs = document.querySelectorAll('.slice-to');
            const allRangeInputs = document.querySelectorAll('#inc-slices-body input[disabled]');
            
            let lastTo = 0;
            sliceToInputs.forEach((input, index) => {
                const currentIdx = parseInt(input.dataset.idx);
                const toVal = parseInt(input.value) || 0;
                
                // Update the NEXT "From"
                const nextFromInput = document.querySelectorAll('#inc-slices-body tr')[currentIdx + 1]?.querySelectorAll('input')[0];
                if (nextFromInput) {
                    nextFromInput.value = toVal + 1;
                }
                
                if (currentIdx === 3) { // Row 4's end updates Row 5's start
                    const row5From = document.querySelectorAll('#inc-slices-body tr')[4]?.querySelectorAll('input')[0];
                    if (row5From) row5From.value = toVal + 1;
                }
            });
        }

        window.confirmIncentiveSlices = function() {
            const slices = [];
            const rows = document.querySelectorAll('#inc-slices-body tr');
            rows.forEach((row, i) => {
                const inputs = row.querySelectorAll('input');
                const from = parseInt(inputs[0].value);
                const to = (i < 4) ? parseInt(inputs[1].value) : 999999;
                const amount = parseFloat(row.querySelector('.slice-amount').value) || 0;
                slices.push({ from, to, amount });
            });
            _tempSlices = slices;
            closeModal('modal-incentive-slices');
        }

        async function saveDelivery() {
            const id = document.getElementById('edit-id').value;
            try {
                const { data } = await axios.put(`/admin/delivery/${id}`, {
                    name: document.getElementById('edit-name').value,
                    phone: document.getElementById('edit-phone').value,
                    code: document.getElementById('edit-code').value,
                    password: document.getElementById('edit-password').value,
                    is_active: document.getElementById('edit-active').value,
                    incentive_slices: _tempSlices,
                });
                showSuccess(data.message); window.location.reload();
            } catch (e) { showError('حدث خطأ'); }
        }

        async function toggleActive(id, btn, d) {
            const isCurrentlyActive = btn.dataset.active === '1';
            const newState = isCurrentlyActive ? 0 : 1;
            // Optimistic UI update
            applyStatusBtn(btn, newState);
            try {
                const { data } = await axios.put(`/admin/delivery/${id}`, { ...d, is_active: newState });
                showSuccess(data.message);
            } catch (e) {
                // Revert on failure
                applyStatusBtn(btn, isCurrentlyActive ? 1 : 0);
                showError('حدث خطأ');
            }
        }

        function applyStatusBtn(btn, active) {
            btn.dataset.active = active ? '1' : '0';
            if (active) {
                btn.style.background = 'rgba(34,197,94,.15)';
                btn.style.color = 'var(--success)';
                btn.textContent = '✓ نشط';
            } else {
                btn.style.background = 'rgba(220,38,38,.12)';
                btn.style.color = 'var(--red)';
                btn.textContent = '✗ غير نشط';
            }
        }


        // ── Admin Delivery Settlement ───────────────────────────────────
        var _settleId = null;

        window.openSettlement = async function (id, name) {
            _settleId = id;
            document.getElementById('settle-delivery-name').textContent = name;
            document.getElementById('settle-body').innerHTML =
                '<div style="text-align:center;padding:40px"><div class="spin" style="margin:auto"></div></div>';
            openModal('modal-settlement');

            try {
                const { data } = await axios.get(`/admin/delivery/${id}/settlement`);
                const s = data.summary;

                const noOrders = s.count === 0;
                const ordersRows = data.orders.length
                    ? data.orders.map(o => `
                    <tr>
                        <td><strong style="color:var(--yellow)">${o.order_number}</strong></td>
                        <td>${o.client}</td>
                        <td>${o.items_count} صنف</td>
                        <td>${parseFloat(o.delivery_fee).toFixed(2)} ج</td>
                        <td>${parseFloat(o.discount).toFixed(2)} ج</td>
                        <td><strong>${parseFloat(o.total).toFixed(2)} ج</strong></td>
                        <td style="font-size:11px;color:var(--text-muted)">${o.delivered_at ? new Date(o.delivered_at).toLocaleDateString('ar-EG') : '—'}</td>
                    </tr>`).join('')
                    : `<tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:24px">لا طلبات مستحقة للتسوية</td></tr>`;

                document.getElementById('settle-body').innerHTML = `
                <div class="kpi-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:20px">
                    <div class="kpi-card">
                        <div class="kpi-label">📦 عدد الطلبات</div>
                        <div class="kpi-value">${s.count}</div>
                    </div>
                    <div class="kpi-card blue">
                        <div class="kpi-label">رسوم التوصيل</div>
                        <div class="kpi-value">${parseFloat(s.total_fees).toFixed(2)}</div>
                        <div class="kpi-sub">ج</div>
                    </div>
                    <div class="kpi-card" style="border:2px solid var(--yellow)">
                        <div class="kpi-label" style="color:var(--yellow)">💰 إجمالي التسوية</div>
                        <div class="kpi-value" style="font-size:28px">${parseFloat(s.total_amount).toFixed(2)}</div>
                        <div class="kpi-sub">ج.م (يُضاف للخزنة)</div>
                    </div>
                </div>
                <div style="text-align:center;margin-bottom:20px">
                    <button class="btn btn-primary" style="padding:12px 40px;font-size:16px"
                        ${noOrders ? 'disabled' : ''}
                        onclick="doDeliverySettlement()">
                        ✅ تأكيد التسوية وإضافة للخزنة
                    </button>
                    ${noOrders ? '<div style="color:var(--text-muted);font-size:13px;margin-top:8px">لا توجد طلبات مستحقة</div>' : ''}
                </div>
                <div class="card-title" style="margin-bottom:8px">📋 الطلبات المستحقة</div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>رقم الطلب</th><th>العميل</th><th>الأصناف</th><th>رسوم التوصيل</th><th>الخصم</th><th>الإجمالي</th><th>تاريخ التوصيل</th></tr></thead>
                        <tbody>${ordersRows}</tbody>
                    </table>
                </div>`;
            } catch (e) {
                document.getElementById('settle-body').innerHTML =
                    '<div style="color:var(--red);text-align:center;padding:30px">حدث خطأ أثناء تحميل بيانات التسوية</div>';
            }
        };

        window.doDeliverySettlement = async function () {
            if (!_settleId) return;
            if (!confirm('هل أنت متأكد من إتمام التسوية؟ سيُضاف المبلغ للخزنة فوراً.')) return;

            try {
                const { data } = await axios.post(`/admin/delivery/${_settleId}/settlement`);
                showSuccess(data.message);
                closeModal('modal-settlement');
            } catch (e) {
                showError(e.response?.data?.message ?? 'حدث خطأ أثناء التسوية');
            }
        };
    </script>
@endpush