
<div class="section-header">
    <h2>كشف الحساب الخاص</h2>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <button class="btn" style="background:#0891b2;color:#fff;" onclick="openModal('modal-pay')">أصال دفع</button>
        <button class="btn" style="background:#059669;color:#fff;" onclick="openModal('modal-receive')">أصال استلام</button>
        <button class="btn btn-danger" onclick="openModal('modal-expense')">صرف مصروف</button>
        <a href="{{ route('admin.admin-ledger.export') }}" id="export-link" class="btn" style="background:#217346;color:#fff;">تصدير Excel</a>
    </div>
</div>

{{-- KPI Cards --}}
<div class="kpi-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:24px;">
    <div class="kpi-card red">
        <div class="kpi-label">إجمالي المدين</div>
        <div class="kpi-value" id="kpi-debit">{{ $initialData['total_debit'] }}</div>
        <div class="kpi-sub">ج.م</div>
    </div>
    <div class="kpi-card green">
        <div class="kpi-label">إجمالي الدائن</div>
        <div class="kpi-value" id="kpi-credit">{{ $initialData['total_credit'] }}</div>
        <div class="kpi-sub">ج.م</div>
    </div>
    <div class="kpi-card blue">
        <div class="kpi-label">الرصيد الحالي</div>
        <div class="kpi-value" id="kpi-balance">{{ $initialData['balance'] }}</div>
        <div class="kpi-sub">ج.م</div>
    </div>
</div>

{{-- Filters --}}
<div class="card" style="margin-bottom:20px;">
    <div class="filter-bar" style="margin-bottom:0;">
        <div>
            <div class="form-label" style="margin-bottom:4px;">من تاريخ</div>
            <input type="date" id="filter-from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
        </div>
        <div>
            <div class="form-label" style="margin-bottom:4px;">إلى تاريخ</div>
            <input type="date" id="filter-to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
        </div>
        <div>
            <div class="form-label" style="margin-bottom:4px;">كول سنتر</div>
            <select id="filter-cc" class="form-select" style="min-width:160px;">
                <option value="">الكل</option>
                @foreach($callcenters as $cc)
                    <option value="{{ $cc->id }}" {{ ($filters['callcenter_id'] ?? '') == $cc->id ? 'selected' : '' }}>{{ $cc->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <div class="form-label" style="margin-bottom:4px;">مندوب</div>
            <select id="filter-delivery" class="form-select" style="min-width:160px;">
                <option value="">الكل</option>
                @foreach($deliveries as $d)
                    <option value="{{ $d->id }}" {{ ($filters['delivery_id'] ?? '') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                @endforeach
            </select>
        </div>
        <div style="display:flex;gap:8px;align-self:flex-end;">
            <button class="btn btn-primary" onclick="applyFilters()">بحث</button>
            <button class="btn btn-secondary" onclick="resetFilters()">إعادة ضبط</button>
        </div>
    </div>
</div>

{{-- Table --}}
<div class="card" style="padding:0;">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--border);">
        <span style="font-size:15px;font-weight:700;">سجل العمليات</span>
        <span class="badge badge-gray" id="total-badge">{{ count($initialData['rows']) }} عملية</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="text-align:center;">#</th>
                    <th style="text-align:center;">التاريخ</th>
                    <th>التعريف / الملاحظة</th>
                    <th style="text-align:center;">مدين</th>
                    <th style="text-align:center;">دائن</th>
                    <th style="text-align:center;">الرصيد</th>
                    <th style="text-align:center;">إجراءات</th>
                </tr>
            </thead>
            <tbody id="ledger-tbody">
                @forelse($initialData['rows'] as $row)
                <tr>
                    <td style="text-align:center;color:var(--text-muted);font-size:12px;">{{ $row['id'] }}</td>
                    <td style="text-align:center;">{{ $row['date'] }}</td>
                    <td>
                        <div style="font-size:13px;">{{ Str::limit($row['description'], 50) }}</div>
                        @if($row['related_user'])
                            <div style="font-size:11px;color:var(--text-muted);">{{ $row['related_user'] }}</div>
                        @endif
                    </td>
                    <td style="text-align:center;color:var(--red);font-weight:700;">
                        {{ $row['debit'] !== '—' ? $row['debit'] . ' ج' : '—' }}
                    </td>
                    <td style="text-align:center;color:var(--success);font-weight:700;">
                        {{ $row['credit'] !== '—' ? $row['credit'] . ' ج' : '—' }}
                    </td>
                    <td style="text-align:center;font-weight:700;">{{ $row['running_balance'] }} ج</td>
                    <td style="text-align:center;">
                        <button class="btn btn-sm btn-info" onclick="showDetail({{ $row['id'] }})">عرض</button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" style="text-align:center;padding:60px;color:var(--text-muted);">لا توجد عمليات</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Modal: أصال دفع --}}
<div class="modal-overlay" id="modal-pay">
    <div class="modal">
        <div class="modal-header" style="background:rgba(8,145,178,.08);border-bottom:0;">
            <h3 style="color:#0891b2;">أصال دفع</h3>
            <button class="btn-close" onclick="closeModal('modal-pay')">✕</button>
        </div>
        <div class="modal-body">
            <div id="pay-error" style="display:none;background:var(--red-light);color:var(--red-dark);padding:10px;border-radius:8px;margin-bottom:12px;"></div>
            <div class="form-group">
                <label class="form-label">الموظف <span style="color:var(--red)">*</span></label>
                <select id="pay-employee" class="form-select">
                    <option value="">اختر موظف...</option>
                    <optgroup label="كول سنتر">
                        @foreach($callcenters as $cc)
                            <option value="{{ $cc->id }}">{{ $cc->name }}</option>
                        @endforeach
                    </optgroup>
                    <optgroup label="مناديب">
                        @foreach($deliveries as $d)
                            <option value="{{ $d->id }}">{{ $d->name }}</option>
                        @endforeach
                    </optgroup>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">المبلغ <span style="color:var(--red)">*</span></label>
                <input type="number" id="pay-amount" class="form-control" placeholder="0.00" min="0.01" step="0.01">
            </div>
            <div class="form-group">
                <label class="form-label">التاريخ</label>
                <input type="date" id="pay-date" class="form-control" max="{{ now()->toDateString() }}">
            </div>
            <div class="form-group">
                <label class="form-label">ملاحظة</label>
                <textarea id="pay-note" class="form-control" rows="2" maxlength="500"></textarea>
            </div>
        </div>
        <div class="modal-footer" style="border-top:0;">
            <button class="btn btn-secondary" onclick="closeModal('modal-pay')">إلغاء</button>
            <button class="btn" style="background:#0891b2;color:#fff;" id="pay-btn" onclick="submitPay()">
                <span id="pay-spin" class="spin" style="display:none;width:14px;height:14px;border-width:2px;"></span>
                تأكيد الدفع
            </button>
        </div>
    </div>
</div>

{{-- Modal: أصال استلام --}}
<div class="modal-overlay" id="modal-receive">
    <div class="modal">
        <div class="modal-header" style="background:rgba(5,150,105,.08);border-bottom:0;">
            <h3 style="color:#059669;">أصال استلام</h3>
            <button class="btn-close" onclick="closeModal('modal-receive')">✕</button>
        </div>
        <div class="modal-body">
            <div id="receive-error" style="display:none;background:var(--red-light);color:var(--red-dark);padding:10px;border-radius:8px;margin-bottom:12px;"></div>
            <div class="form-group">
                <label class="form-label">الموظف <span style="color:var(--red)">*</span></label>
                <select id="receive-employee" class="form-select">
                    <option value="">اختر موظف...</option>
                    <optgroup label="كول سنتر">
                        @foreach($callcenters as $cc)
                            <option value="{{ $cc->id }}" data-balance="{{ $cc->wallet->balance ?? 0 }}">{{ $cc->name }} ({{ number_format($cc->wallet->balance ?? 0, 2) }} ج)</option>
                        @endforeach
                    </optgroup>
                    <optgroup label="مناديب">
                        @foreach($deliveries as $d)
                            <option value="{{ $d->id }}" data-balance="{{ $d->wallet->balance ?? 0 }}">{{ $d->name }} ({{ number_format($d->wallet->balance ?? 0, 2) }} ج)</option>
                        @endforeach
                    </optgroup>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">المبلغ <span style="color:var(--red)">*</span></label>
                <input type="number" id="receive-amount" class="form-control" placeholder="0.00" min="0.01" step="0.01">
            </div>
            <div class="form-group">
                <label class="form-label">التاريخ</label>
                <input type="date" id="receive-date" class="form-control" max="{{ now()->toDateString() }}">
            </div>
            <div class="form-group">
                <label class="form-label">ملاحظة</label>
                <textarea id="receive-note" class="form-control" rows="2" maxlength="500"></textarea>
            </div>
        </div>
        <div class="modal-footer" style="border-top:0;">
            <button class="btn btn-secondary" onclick="closeModal('modal-receive')">إلغاء</button>
            <button class="btn" style="background:#059669;color:#fff;" id="receive-btn" onclick="submitReceive()">
                <span id="receive-spin" class="spin" style="display:none;width:14px;height:14px;border-width:2px;"></span>
                تأكيد الاستلام
            </button>
        </div>
    </div>
</div>

{{-- Modal: صرف مصروف --}}
<div class="modal-overlay" id="modal-expense">
    <div class="modal">
        <div class="modal-header" style="background:rgba(220,38,38,.08);border-bottom:0;">
            <h3 style="color:var(--red);">صرف مصروف</h3>
            <button class="btn-close" onclick="closeModal('modal-expense')">✕</button>
        </div>
        <div class="modal-body">
            <div id="expense-error" style="display:none;background:var(--red-light);color:var(--red-dark);padding:10px;border-radius:8px;margin-bottom:12px;"></div>
            <div class="form-group">
                <label class="form-label">التعريف <span style="color:var(--red)">*</span></label>
                <input type="text" id="expense-desc" class="form-control" placeholder="وصف المصروف" maxlength="200">
            </div>
            <div class="form-group">
                <label class="form-label">المبلغ <span style="color:var(--red)">*</span></label>
                <input type="number" id="expense-amount" class="form-control" placeholder="0.00" min="0.01" step="0.01">
            </div>
            <div class="form-group">
                <label class="form-label">التاريخ</label>
                <input type="date" id="expense-date" class="form-control" max="{{ now()->toDateString() }}">
            </div>
            <div class="form-group">
                <label class="form-label">ملاحظة</label>
                <textarea id="expense-note" class="form-control" rows="2" maxlength="500"></textarea>
            </div>
        </div>
        <div class="modal-footer" style="border-top:0;">
            <button class="btn btn-secondary" onclick="closeModal('modal-expense')">إلغاء</button>
            <button class="btn btn-danger" id="expense-btn" onclick="submitExpense()">
                <span id="expense-spin" class="spin" style="display:none;width:14px;height:14px;border-width:2px;border-color:rgba(255,255,255,.3);border-top-color:#fff;"></span>
                حفظ المصروف
            </button>
        </div>
    </div>
</div>

{{-- Modal: تفاصيل العملية --}}
<div class="modal-overlay" id="modal-detail">
    <div class="modal">
        <div class="modal-header">
            <h3>تفاصيل العملية</h3>
            <button class="btn-close" onclick="closeModal('modal-detail')">✕</button>
        </div>
        <div class="modal-body" id="detail-body">
            <div style="text-align:center;padding:40px;color:var(--text-muted);">
                <div class="spin" style="width:30px;height:30px;border-width:3px;margin:0 auto 12px;"></div>
                جاري التحميل...
            </div>
        </div>
    </div>
</div>

{{-- Modal: تعديل عملية --}}
<div class="modal-overlay" id="modal-edit">
    <div class="modal">
        <div class="modal-header" style="background:rgba(245,158,11,.08);border-bottom:0;">
            <h3 style="color:var(--yellow);">تعديل العملية</h3>
            <button class="btn-close" onclick="closeModal('modal-edit')">✕</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="edit-id">
            <div id="edit-error" style="display:none;background:var(--red-light);color:var(--red-dark);padding:10px;border-radius:8px;margin-bottom:12px;"></div>
            <div class="form-group">
                <label class="form-label">نوع العملية</label>
                <div id="edit-type-label" style="padding:8px 12px;background:var(--input-bg);border:1px solid var(--border);border-radius:8px;font-size:13px;">...</div>
            </div>
            <div class="form-group">
                <label class="form-label">التعريف</label>
                <input type="text" id="edit-desc" class="form-control" maxlength="200">
            </div>
            <div class="form-group">
                <label class="form-label">المبلغ <span style="color:var(--red)">*</span></label>
                <input type="number" id="edit-amount" class="form-control" min="0.01" step="0.01">
            </div>
            <div class="form-group">
                <label class="form-label">التاريخ</label>
                <input type="date" id="edit-date" class="form-control" max="{{ now()->toDateString() }}">
            </div>
            <div class="form-group">
                <label class="form-label">ملاحظة</label>
                <textarea id="edit-note" class="form-control" rows="2" maxlength="500"></textarea>
            </div>
        </div>
        <div class="modal-footer" style="border-top:0;">
            <button class="btn btn-secondary" onclick="closeModal('modal-edit')">إلغاء</button>
            <button class="btn btn-primary" id="edit-btn" onclick="submitEdit()">
                <span id="edit-spin" class="spin" style="display:none;width:14px;height:14px;border-width:2px;border-color:rgba(0,0,0,.3);border-top-color:#000;"></span>
                حفظ التعديل
            </button>
        </div>
    </div>
</div>

<script>
(function(){
    'use strict';

    function getFilters(){
        const p = {};
        const from = document.getElementById('filter-from').value;
        const to   = document.getElementById('filter-to').value;
        const cc   = document.getElementById('filter-cc').value;
        const del  = document.getElementById('filter-delivery').value;
        if(from) p.date_from = from;
        if(to)   p.date_to   = to;
        if(cc)   p.callcenter_id = cc;
        if(del)  p.delivery_id   = del;
        return p;
    }

    window.applyFilters = async function(){
        const params = getFilters();
        // update export link
        const qs = new URLSearchParams(params).toString();
        document.getElementById('export-link').href = '{{ route("admin.admin-ledger.export") }}' + (qs ? '?' + qs : '');
        try {
            const res = await axios.get('{{ route("admin.admin-ledger.statement") }}', { params });
            renderTable(res.data);
            document.getElementById('kpi-debit').textContent   = res.data.total_debit;
            document.getElementById('kpi-credit').textContent  = res.data.total_credit;
            document.getElementById('kpi-balance').textContent = res.data.balance;
        } catch(e) { console.warn(e); }
    };

    window.resetFilters = function(){
        document.getElementById('filter-from').value     = '';
        document.getElementById('filter-to').value       = '';
        document.getElementById('filter-cc').value       = '';
        document.getElementById('filter-delivery').value = '';
        applyFilters();
    };

    function renderTable(data){
        const tbody = document.getElementById('ledger-tbody');
        document.getElementById('total-badge').textContent = data.rows.length + ' عملية';
        if(!data.rows.length){
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:60px;color:var(--text-muted);">لا توجد عمليات</td></tr>';
            return;
        }
        tbody.innerHTML = data.rows.map(r => `
            <tr>
                <td style="text-align:center;color:var(--text-muted);font-size:12px;">${r.id}</td>
                <td style="text-align:center;">${r.date}</td>
                <td>
                    <div style="font-size:13px;">${r.description}</div>
                    ${r.related_user ? `<div style="font-size:11px;color:var(--text-muted);">${r.related_user}</div>` : ''}
                </td>
                <td style="text-align:center;color:var(--red);font-weight:700;">${r.debit !== '—' ? r.debit + ' ج' : '—'}</td>
                <td style="text-align:center;color:var(--success);font-weight:700;">${r.credit !== '—' ? r.credit + ' ج' : '—'}</td>
                <td style="text-align:center;font-weight:700;">${r.running_balance} ج</td>
                <td style="text-align:center;">
                    <button class="btn btn-sm btn-info" onclick="showDetail(${r.id})">عرض</button>
                </td>
            </tr>
        `).join('');
    }

    async function post(url, data, errorId, btnId, spinId){
        const err = document.getElementById(errorId);
        const btn = document.getElementById(btnId);
        const spn = document.getElementById(spinId);
        err.style.display='none';
        btn.disabled=true;
        spn.style.display='inline-block';
        try {
            await axios.post(url, data);
            showSuccess('تمت العملية بنجاح');
            document.querySelectorAll('.modal-overlay.open').forEach(m=>m.classList.remove('open'));
            applyFilters();
        } catch(e){
            const msg = e.response?.data?.message || 'حدث خطأ';
            err.textContent = msg;
            err.style.display = 'block';
        } finally {
            btn.disabled=false;
            spn.style.display='none';
        }
    }

    window.submitPay = function(){
        post('{{ route("admin.admin-ledger.pay") }}', {
            employee_id: document.getElementById('pay-employee').value,
            amount:      document.getElementById('pay-amount').value,
            date:        document.getElementById('pay-date').value || null,
            note:        document.getElementById('pay-note').value || null,
        }, 'pay-error', 'pay-btn', 'pay-spin');
    };

    window.submitReceive = function(){
        post('{{ route("admin.admin-ledger.receive") }}', {
            employee_id: document.getElementById('receive-employee').value,
            amount:      document.getElementById('receive-amount').value,
            date:        document.getElementById('receive-date').value || null,
            note:        document.getElementById('receive-note').value || null,
        }, 'receive-error', 'receive-btn', 'receive-spin');
    };

    window.submitExpense = function(){
        post('{{ route("admin.admin-ledger.expense") }}', {
            description: document.getElementById('expense-desc').value,
            amount:      document.getElementById('expense-amount').value,
            date:        document.getElementById('expense-date').value || null,
            note:        document.getElementById('expense-note').value || null,
        }, 'expense-error', 'expense-btn', 'expense-spin');
    };

    window.showDetail = async function(id){
        openModal('modal-detail');
        document.getElementById('detail-body').innerHTML = '<div style="text-align:center;padding:40px;color:var(--text-muted);"><div class="spin" style="width:30px;height:30px;border-width:3px;margin:0 auto 12px;"></div>جاري التحميل...</div>';
        try {
            const res = await axios.get(`/admin/admin-ledger/${id}`);
            const d = res.data;
            const typeColors = { admin_pay:'#f59e0b', admin_receive:'#22c55e', admin_expense:'#dc2626' };
            document.getElementById('detail-body').innerHTML = `
                <div style="margin-bottom:16px;">
                    <span style="background:${typeColors[d.type]||'#94a3b8'};color:#fff;padding:4px 12px;border-radius:12px;font-size:12px;font-weight:700;">${d.type_label}</span>
                </div>
                <div style="display:grid;gap:12px;">
                    ${row('رقم العملية','#'+d.id)}
                    ${row('التاريخ',d.date)}
                    ${row('المبلغ',d.amount+' ج')}
                    ${row('الوصف',d.description)}
                    ${d.related_user && d.related_user!=='—' ? row('الموظف المرتبط',d.related_user) : ''}
                    ${row('تاريخ التسجيل',d.created_at)}
                </div>
                <div style="display:flex;gap:8px;margin-top:20px;justify-content:flex-end;">
                    <a href="/admin/admin-ledger/${d.id}/pdf" target="_blank" class="btn btn-danger btn-sm">طباعة PDF</a>
                    ${d.can_edit ? `<button class="btn btn-sm" style="background:var(--yellow);color:#000;" onclick="openEdit(${d.id},'${d.type_label}',${d.amount},'${d.date}','${(d.description||'').replace(/'/g,"\\'")}')">تعديل</button>` : ''}
                </div>
            `;
        } catch(e){ document.getElementById('detail-body').innerHTML='<div style="padding:20px;color:var(--red);">حدث خطأ في تحميل البيانات</div>'; }
    };

    function row(label, val){
        return `<div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border);font-size:13px;">
            <span style="color:var(--text-muted);font-weight:600;">${label}</span>
            <span style="font-weight:700;">${val||'—'}</span>
        </div>`;
    }

    window.openEdit = function(id, typeLabel, amount, date, desc){
        closeModal('modal-detail');
        document.getElementById('edit-id').value = id;
        document.getElementById('edit-type-label').textContent = typeLabel;
        document.getElementById('edit-amount').value = amount;
        document.getElementById('edit-date').value = date ? date.split('/').reverse().join('-') : '';
        document.getElementById('edit-desc').value = desc || '';
        document.getElementById('edit-note').value = '';
        openModal('modal-edit');
    };

    window.submitEdit = async function(){
        const id = document.getElementById('edit-id').value;
        const err = document.getElementById('edit-error');
        const btn = document.getElementById('edit-btn');
        const spn = document.getElementById('edit-spin');
        err.style.display='none'; btn.disabled=true; spn.style.display='inline-block';
        try {
            await axios.put(`/admin/admin-ledger/${id}`, {
                description: document.getElementById('edit-desc').value || null,
                amount:      document.getElementById('edit-amount').value,
                date:        document.getElementById('edit-date').value || null,
                note:        document.getElementById('edit-note').value || null,
                _method:     'PUT',
            });
            showSuccess('تم التعديل بنجاح');
            closeModal('modal-edit');
            applyFilters();
        } catch(e){
            err.textContent = e.response?.data?.message || 'حدث خطأ';
            err.style.display='block';
        } finally { btn.disabled=false; spn.style.display='none'; }
    };

})();
</script>
