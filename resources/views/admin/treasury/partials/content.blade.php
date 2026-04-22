{{--
resources/views/admin/treasury/partials/content.blade.php
──────────────────────────────────────────────────────────
Uses the project's own CSS (layouts/admin.blade.php) — no Bootstrap.

Variables injected by TreasuryController@index:
$initialStats → ['total_income', 'total_expense', 'balance']
$initialTransactions → LengthAwarePaginator (first page of rows)
$filters → ['from' => ?string, 'to' => ?string, 'type' => ?string]
--}}

{{-- ── Page header ──────────────────────────────────────────────── --}}
<div class="section-header">
    <h2>ادارة الخزينة</h2>
    <style>
        :root {
            --indigo: #4f46e5;
            --indigo-light: #e0e7ff;
            --indigo-dark: #3730a3;
        }

        .badge-indigo {
            background: var(--indigo-light);
            color: var(--indigo-dark);
        }

        .badge-cyan {
            background: #cffafe;
            color: #0e7490;
        }

        .badge-teal {
            background: #ccfbf1;
            color: #0f766e;
        }

        .pagination a {
            cursor: pointer;
        }
    </style>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <button class="btn btn-danger" onclick="openModal('modal-expense')">
            صرف مصروف
        </button>
        <button class="btn btn-danger" onclick="openDiscountModal()" style="background:var(--red);color:#fff;">
            خصم
        </button>
        <button class="btn" onclick="openPayToUserModal()" style="background:#0891b2;color:#fff;">
           ايصال دفع نقدي   
        </button>
        <button class="btn" onclick="openReceiveFromUserModal()" style="background:#059669;color:#fff;">
            ايصال استلام نقدي
        </button>
    </div>
</div>

{{-- ── KPI Cards ─────────────────────────────────────────────────── --}}
<div class="kpi-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:24px;">
    <div class="kpi-card red">
        <div class="kpi-label">إجمالي الدفع لموظف</div>
        <div class="kpi-value" id="kpi-payment-receipts" style="color: #0891b2">
            {{ $initialStats['payment_receipts'] }}
        </div>
        <div class="kpi-sub">ج.م</div>
    </div>
    <div class="kpi-card green">
        <div class="kpi-label">إجمالي الاستلام من موظف</div>
        <div class="kpi-value" id="kpi-receiving-receipts" style="color:var(--success)">
            {{ $initialStats['receiving_receipts'] }}
        </div>
        <div class="kpi-sub">ج.م</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">إجمالي المصروفات</div>
        <div class="kpi-value" id="kpi-expenses" style="color:var(--yellow)">{{ $initialStats['total_expenses'] }}</div>
        <div class="kpi-sub">ج.م</div>
    </div>
    <div class="kpi-card" style="border-left:4px solid #0891b2;">
        <div class="kpi-label">الرصيد الحالي للخزينة</div>
        <div class="kpi-value" id="kpi-balance" style="color:#0891b2">{{ $initialStats['balance'] }}</div>
        <div class="kpi-sub">ج.م</div>
    </div>
</div>

{{-- ── Filter Bar ────────────────────────────────────────────────── --}}
<div class="card" style="margin-bottom:20px;">
    <div class="filter-bar" style="margin-bottom:0;">
        <div>
            <div class="form-label" style="margin-bottom:4px;">من تاريخ</div>
            <input type="date" id="filter-from" class="form-control" value="{{ $filters['from'] ?? '' }}">
        </div>
        <div>
            <div class="form-label" style="margin-bottom:4px;">إلى تاريخ</div>
            <input type="date" id="filter-to" class="form-control" value="{{ $filters['to'] ?? '' }}">
        </div>
        <div>
            <div class="form-label" style="margin-bottom:4px;">نوع المعاملة</div>
            <select id="filter-type" class="form-select">
                <option value="">الكل</option>
                <option value="income" {{ ($filters['type'] ?? '') === 'income' ? 'selected' : '' }}>إيراد</option>
                <option value="expense" {{ ($filters['type'] ?? '') === 'expense' ? 'selected' : '' }}>مصروف</option>
                <option value="settlement" {{ ($filters['type'] ?? '') === 'settlement' ? 'selected' : '' }}>تسوية
                </option>
                <option value="dain" {{ ($filters['type'] ?? '') === 'dain' ? 'selected' : '' }}>صرف مديونية</option>
                <option value="discount" {{ ($filters['type'] ?? '') === 'discount' ? 'selected' : '' }}>خصم</option>
                <option value="pay_to_user" {{ ($filters['type'] ?? '') === 'pay_to_user' ? 'selected' : '' }}>دفع لموظف
                </option>
                <option value="receive_from_user" {{ ($filters['type'] ?? '') === 'receive_from_user' ? 'selected' : '' }}>استلام من موظف</option>
            </select>
        </div>
        <div style="display:flex;gap:8px;align-self:flex-end;">
            <button class="btn btn-primary" onclick="applyFilters()">🔍 تصفية</button>
            <button class="btn btn-secondary" onclick="resetFilters()" title="إعادة ضبط">↺</button>
        </div>
    </div>
</div>

{{-- ── Ledger Table ──────────────────────────────────────────────── --}}
<div class="card" style="padding:0;position:relative;">
    <div
        style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--border);">
        <span style="font-size:15px;font-weight:700;">سجل المعاملات المالية</span>
        <span class="badge badge-gray" id="ledger-total-badge">{{ $initialTransactions->total() }} معاملة</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>رقم العملية</th>
                    <th>التاريخ</th>
                    <th>النوع</th>
                    <th>المبلغ</th>
                    <th>بواسطة</th>
                    <th>ملاحظة</th>
                    <th style="text-align:center;">الإجراءات</th>
                </tr>
            </thead>
            <tbody id="ledger-tbody">
                @forelse($initialTransactions as $tx)
                    <tr>
                        <td style="color:var(--text-muted);font-size:12px;">{{ $tx->id }}</td>
                        <td>{{ $tx->transaction_date->format('d/m/Y') }}</td>
                        <td>
                            @if($tx->type === 'income')
                                <span class="badge badge-green">إيراد</span>
                            @elseif($tx->type === 'expense')
                                <span class="badge badge-red">مصروف</span>
                            @elseif($tx->type === 'settlement')
                                <span class="badge badge-yellow">تسوية</span>
                            @elseif($tx->type === 'discount')
                                <span class="badge badge-red">خصم</span>
                            @elseif($tx->type === 'pay_to_user')
                                <span class="badge badge-cyan">دفع لموظف</span>
                            @elseif($tx->type === 'receive_from_user')
                                <span class="badge badge-teal">استلام من موظف</span>
                            @else
                                <span class="badge badge-indigo">صرف مديونية</span>
                            @endif
                        </td>
                        <td style="font-weight:700;">{{ number_format((float) $tx->amount, 2) }}</td>
                        <td>{{ $tx->by_whom }}</td>
                        <td style="color:var(--text-muted);font-size:12px;">{{ Str::limit($tx->note ?? '—', 40) }}</td>
                        <td style="text-align:center;">
                            <div style="display:flex;gap:6px;justify-content:center;">
                                <button class="btn btn-sm btn-info" onclick="showDetail({{ $tx->id }})"
                                    title="عرض التفاصيل">👁</button>
                                <button class="btn btn-sm" style="background:var(--yellow);color:#000;"
                                    onclick="editTransaction({{ $tx->id }})" title="تعديل">✏️ تعديل</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center;color:var(--text-muted);padding:60px;">لا توجد معاملات
                            مالية</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($initialTransactions->lastPage() > 1)
        <div style="padding:16px;border-top:1px solid var(--border);" id="ledger-pagination-wrapper">
            <div id="ledger-pagination" class="pagination"></div>
        </div>
    @else
        <div style="display:none;" id="ledger-pagination-wrapper">
            <div id="ledger-pagination" class="pagination"></div>
        </div>
    @endif
</div>


{{-- ── Income Modal ──────────────────────────────────────────────── --}}
<div class="modal-overlay" id="modal-income">
    <div class="modal">
        <div class="modal-header" style="background:rgba(34,197,94,.08);border-bottom:0;">
            <h3 style="color:var(--success);">➕ إضافة إيراد</h3>
            <button class="btn-close" onclick="closeModal('modal-income')">✕</button>
        </div>
        <div class="modal-body">
            <div class="error-text" id="income-global-error"
                style="background:var(--red-light);color:var(--red-dark);padding:10px;border-radius:8px;margin-bottom:12px;display:none;">
            </div>

            <div class="form-group">
                <label for="income-by-whom" class="form-label">بواسطة <span style="color:var(--red)">*</span></label>
                <input type="text" id="income-by-whom" class="form-control" placeholder="اسم الشخص أو الجهة"
                    maxlength="100" autocomplete="off">
                <div class="error-text" id="income-by-whom-error"></div>
            </div>

            <div class="form-group">
                <label for="income-amount" class="form-label">المبلغ <span style="color:var(--red)">*</span></label>
                <div style="display:flex;">
                    <input type="number" id="income-amount" class="form-control" placeholder="0.00" min="0.01"
                        step="0.01" max="9999999.99" style="border-radius:0 8px 8px 0;flex:1;">
                    <span
                        style="background:var(--input-bg);padding:9px 12px;border-radius:8px 0 0 8px;font-size:13px;color:var(--text-muted);border:1px solid var(--border);border-right:none;">ج.م</span>
                </div>
                <div class="error-text" id="income-amount-error"></div>
            </div>

            <div class="form-group">
                <label for="income-date" class="form-label">التاريخ <span
                        style="color:var(--text-muted);font-weight:400;font-size:11px;">(اختياري — الافتراضي
                        اليوم)</span></label>
                <input type="date" id="income-date" class="form-control" max="{{ now()->toDateString() }}">
                <div class="error-text" id="income-date-error"></div>
            </div>

            <div class="form-group">
                <label for="income-note" class="form-label">ملاحظة <span
                        style="color:var(--text-muted);font-weight:400;font-size:11px;">(اختياري)</span></label>
                <textarea id="income-note" class="form-control" rows="2" maxlength="500"
                    placeholder="وصف مختصر للإيراد..."></textarea>
                <div class="error-text" id="income-note-error"></div>
            </div>
        </div>
        <div class="modal-footer" style="border-top:0;">
            <button class="btn btn-secondary" onclick="closeModal('modal-income')">إلغاء</button>
            <button class="btn btn-success" id="income-submit-btn" onclick="submitIncome()">
                <span id="income-submit-spinner" class="spin"
                    style="display:none;width:14px;height:14px;margin-left:6px;border-width:2px;border-color:rgba(0,0,0,.3);border-top-color:#fff;"></span>
                حفظ الإيراد
            </button>
        </div>
    </div>
</div>

{{-- ── Expense Modal ─────────────────────────────────────────────── --}}
<div class="modal-overlay" id="modal-expense">
    <div class="modal">
        <div class="modal-header" style="background:rgba(220,38,38,.08);border-bottom:0;">
            <h3 style="color:var(--red);">إضافة مصروف</h3>
            <button class="btn-close" onclick="closeModal('modal-expense')">✕</button>
        </div>
        <div class="modal-body">
            <div class="error-text" id="expense-global-error"
                style="background:var(--red-light);color:var(--red-dark);padding:10px;border-radius:8px;margin-bottom:12px;display:none;">
            </div>

            <div class="form-group">
                <label for="expense-by-whom" class="form-label">بواسطة <span style="color:var(--red)">*</span></label>
                <input type="text" id="expense-by-whom" class="form-control" placeholder="اسم الشخص أو الجهة"
                    maxlength="100" autocomplete="off">
                <div class="error-text" id="expense-by-whom-error"></div>
            </div>

            <div class="form-group">
                <label for="expense-amount" class="form-label">المبلغ <span style="color:var(--red)">*</span></label>
                <div style="display:flex;">
                    <input type="number" id="expense-amount" class="form-control" placeholder="0.00" min="0.01"
                        step="0.01" max="9999999.99" style="border-radius:0 8px 8px 0;flex:1;">
                    <span
                        style="background:var(--input-bg);padding:9px 12px;border-radius:8px 0 0 8px;font-size:13px;color:var(--text-muted);border:1px solid var(--border);border-right:none;">ج.م</span>
                </div>
                <div class="error-text" id="expense-amount-error"></div>
            </div>

            <div class="form-group">
                <label for="expense-date" class="form-label">التاريخ <span
                        style="color:var(--text-muted);font-weight:400;font-size:11px;">(اختياري — الافتراضي
                        اليوم)</span></label>
                <input type="date" id="expense-date" class="form-control" max="{{ now()->toDateString() }}">
                <div class="error-text" id="expense-date-error"></div>
            </div>

            <div class="form-group">
                <label for="expense-note" class="form-label">ملاحظة <span
                        style="color:var(--text-muted);font-weight:400;font-size:11px;">(اختياري)</span></label>
                <textarea id="expense-note" class="form-control" rows="2" maxlength="500"
                    placeholder="وصف مختصر للمصروف..."></textarea>
                <div class="error-text" id="expense-note-error"></div>
            </div>
        </div>
        <div class="modal-footer" style="border-top:0;">
            <button class="btn btn-secondary" onclick="closeModal('modal-expense')">إلغاء</button>
            <button class="btn btn-danger" id="expense-submit-btn" onclick="submitExpense()">
                <span id="expense-submit-spinner" class="spin"
                    style="display:none;width:14px;height:14px;margin-left:6px;border-width:2px;border-color:rgba(255,255,255,.3);border-top-color:#fff;"></span>
                حفظ المصروف
            </button>
        </div>
    </div>
</div>

{{-- ── Dain Modal ────────────────────────────────────────────────── --}}
<div class="modal-overlay" id="modal-dain">
    <div class="modal">
        <div class="modal-header" style="background:rgba(79,70,229,.08);border-bottom:0;">
            <h3 style="color:var(--indigo);">⚖️ إضافة صرف مديونية</h3>
            <button class="btn-close" onclick="closeModal('modal-dain')">✕</button>
        </div>
        <div class="modal-body">
            <div class="error-text" id="dain-global-error"
                style="background:var(--red-light);color:var(--red-dark);padding:10px;border-radius:8px;margin-bottom:12px;display:none;">
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px;">
                <div class="form-group" style="margin-bottom:0;">
                    <label for="dain-callcenter-id" class="form-label">كول سينتر</label>
                    <select id="dain-callcenter-id" class="form-select" onchange="onDainSelectChange('cc')">
                        <option value="">اختر كول سينتر...</option>
                        @foreach($callcenters as $cc)
                            <option value="{{ $cc->id }}">{{ $cc->name }}</option>
                        @endforeach
                    </select>
                    <div class="error-text" id="dain-callcenter-id-error"></div>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label for="dain-delivery-id" class="form-label">مندوب</label>
                    <select id="dain-delivery-id" class="form-select" onchange="onDainSelectChange('delivery')">
                        <option value="">اختر مندوب...</option>
                        @foreach($deliveries as $d)
                            <option value="{{ $d->id }}">{{ $d->name }}</option>
                        @endforeach
                    </select>
                    <div class="error-text" id="dain-delivery-id-error"></div>
                </div>
            </div>

            <div class="form-group">
                <label for="dain-amount" class="form-label">المبلغ <span style="color:var(--red)">*</span></label>
                <div style="display:flex;">
                    <input type="number" id="dain-amount" class="form-control" placeholder="0.00" min="0.01" step="0.01"
                        max="9999999.99" style="border-radius:0 8px 8px 0;flex:1;">
                    <span
                        style="background:var(--input-bg);padding:9px 12px;border-radius:8px 0 0 8px;font-size:13px;color:var(--text-muted);border:1px solid var(--border);border-right:none;">ج.م</span>
                </div>
                <div class="error-text" id="dain-amount-error"></div>
            </div>

            <div class="form-group">
                <label for="dain-date" class="form-label">التاريخ <span
                        style="color:var(--text-muted);font-weight:400;font-size:11px;">(اختياري — اليوم)</span></label>
                <input type="date" id="dain-date" class="form-control" max="{{ now()->toDateString() }}">
                <div class="error-text" id="dain-date-error"></div>
            </div>

            <div class="form-group">
                <label for="dain-note" class="form-label">ملاحظة <span
                        style="color:var(--text-muted);font-weight:400;font-size:11px;">(اختياري)</span></label>
                <textarea id="dain-note" class="form-control" rows="2" maxlength="500"
                    placeholder="وصف مختصر..."></textarea>
                <div class="error-text" id="dain-note-error"></div>
            </div>
        </div>
        <div class="modal-footer" style="border-top:0;">
            <button class="btn btn-secondary" onclick="closeModal('modal-dain')">إلغاء</button>
            <button class="btn btn-indigo" id="dain-submit-btn" onclick="submitDain()"
                style="background:var(--indigo);color:#fff;">
                <span id="dain-submit-spinner" class="spin"
                    style="display:none;width:14px;height:14px;margin-left:6px;border-width:2px;border-color:rgba(255,255,255,.3);border-top-color:#fff;"></span>
                حفظ العملية
            </button>
        </div>
    </div>
</div>

{{-- ── Discount Modal ────────────────────────────────────────────── --}}
<div class="modal-overlay" id="modal-discount">
    <div class="modal">
        <div class="modal-header" style="background:rgba(220,38,38,.08);border-bottom:0;">
            <h3 style="color:var(--red);">✂️ إضافة خصم</h3>
            <button class="btn-close" onclick="closeModal('modal-discount')">✕</button>
        </div>
        <div class="modal-body">
            <div class="error-text" id="discount-global-error"
                style="background:var(--red-light);color:var(--red-dark);padding:10px;border-radius:8px;margin-bottom:12px;display:none;">
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px;">
                <div class="form-group" style="margin-bottom:0;">
                    <label for="discount-callcenter-id" class="form-label">كول سينتر</label>
                    <select id="discount-callcenter-id" class="form-select" onchange="onDiscountSelectChange('cc')">
                        <option value="">اختر كول سينتر...</option>
                        @foreach($callcenters as $cc)
                            <option value="{{ $cc->id }}">{{ $cc->name }}</option>
                        @endforeach
                    </select>
                    <div class="error-text" id="discount-callcenter-id-error"></div>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label for="discount-delivery-id" class="form-label">مندوب</label>
                    <select id="discount-delivery-id" class="form-select" onchange="onDiscountSelectChange('delivery')">
                        <option value="">اختر مندوب...</option>
                        @foreach($deliveries as $d)
                            <option value="{{ $d->id }}">{{ $d->name }}</option>
                        @endforeach
                    </select>
                    <div class="error-text" id="discount-delivery-id-error"></div>
                </div>
            </div>

            <div class="form-group">
                <label for="discount-amount" class="form-label">المبلغ <span style="color:var(--red)">*</span></label>
                <div style="display:flex;">
                    <input type="number" id="discount-amount" class="form-control" placeholder="0.00" min="0.01"
                        step="0.01" max="9999999.99" style="border-radius:0 8px 8px 0;flex:1;">
                    <span
                        style="background:var(--input-bg);padding:9px 12px;border-radius:8px 0 0 8px;font-size:13px;color:var(--text-muted);border:1px solid var(--border);border-right:none;">ج.م</span>
                </div>
                <div class="error-text" id="discount-amount-error"></div>
            </div>

            <div class="form-group">
                <label for="discount-date" class="form-label">التاريخ <span
                        style="color:var(--text-muted);font-weight:400;font-size:11px;">(اختياري — اليوم)</span></label>
                <input type="date" id="discount-date" class="form-control" max="{{ now()->toDateString() }}">
                <div class="error-text" id="discount-date-error"></div>
            </div>

            <div class="form-group">
                <label for="discount-note" class="form-label">ملاحظة <span
                        style="color:var(--text-muted);font-weight:400;font-size:11px;">(اختياري)</span></label>
                <textarea id="discount-note" class="form-control" rows="2" maxlength="500"
                    placeholder="وصف مختصر..."></textarea>
                <div class="error-text" id="discount-note-error"></div>
            </div>
        </div>
        <div class="modal-footer" style="border-top:0;">
            <button class="btn btn-secondary" onclick="closeModal('modal-discount')">إلغاء</button>
            <button class="btn btn-danger" id="discount-submit-btn" onclick="submitDiscount()"
                style="background:var(--red);color:#fff;">
                <span id="discount-submit-spinner" class="spin"
                    style="display:none;width:14px;height:14px;margin-left:6px;border-width:2px;border-color:rgba(255,255,255,.3);border-top-color:#fff;"></span>
                حفظ الخصم
            </button>
        </div>
    </div>
</div>

{{-- ── Pay To User Modal ──────────────────────────────────────────── --}}
<div class="modal-overlay" id="modal-pay-to-user">
    <div class="modal">
        <div class="modal-header" style="background:rgba(8,145,178,.08);border-bottom:0;">
            <h3 style="color:#0891b2;">💵 دفع نقدي لموظف</h3>
            <button class="btn-close" onclick="closeModal('modal-pay-to-user')">✕</button>
        </div>
        <div class="modal-body">
            <div class="error-text" id="pay-global-error"
                style="background:var(--red-light);color:var(--red-dark);padding:10px;border-radius:8px;margin-bottom:12px;display:none;">
            </div>

            <div class="form-group">
                <label for="pay-user-id" class="form-label">الموظف <span style="color:var(--red)">*</span></label>
                <select id="pay-user-id" class="form-select">
                    <option value="">اختر موظف...</option>
                    <optgroup label="كول سينتر">
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
                <div class="error-text" id="pay-user-id-error"></div>
            </div>

            <div class="form-group">
                <label for="pay-amount" class="form-label">المبلغ <span style="color:var(--red)">*</span></label>
                <div style="display:flex;">
                    <input type="number" id="pay-amount" class="form-control" placeholder="0.00" min="0.01" step="0.01"
                        max="9999999.99" style="border-radius:0 8px 8px 0;flex:1;">
                    <span
                        style="background:var(--input-bg);padding:9px 12px;border-radius:8px 0 0 8px;font-size:13px;color:var(--text-muted);border:1px solid var(--border);border-right:none;">ج.م</span>
                </div>
                <div class="error-text" id="pay-amount-error"></div>
            </div>

            <div class="form-group">
                <label for="pay-date" class="form-label">التاريخ <span
                        style="color:var(--text-muted);font-weight:400;font-size:11px;">(اختياري — اليوم)</span></label>
                <input type="date" id="pay-date" class="form-control" max="{{ now()->toDateString() }}">
                <div class="error-text" id="pay-date-error"></div>
            </div>

            <div class="form-group">
                <label for="pay-description" class="form-label">ملاحظة <span
                        style="color:var(--text-muted);font-weight:400;font-size:11px;">(اختياري)</span></label>
                <textarea id="pay-description" class="form-control" rows="2" maxlength="500"
                    placeholder="وصف مختصر..."></textarea>
                <div class="error-text" id="pay-description-error"></div>
            </div>
        </div>
        <div class="modal-footer" style="border-top:0;">
            <button class="btn btn-secondary" onclick="closeModal('modal-pay-to-user')">إلغاء</button>
            <button class="btn" id="pay-submit-btn" onclick="submitPayToUser()" style="background:#0891b2;color:#fff;">
                <span id="pay-submit-spinner" class="spin"
                    style="display:none;width:14px;height:14px;margin-left:6px;border-width:2px;border-color:rgba(255,255,255,.3);border-top-color:#fff;"></span>
                تأكيد الدفع
            </button>
        </div>
    </div>
</div>

{{-- ── Receive From User Modal ───────────────────────────────────── --}}
<div class="modal-overlay" id="modal-receive-from-user">
    <div class="modal">
        <div class="modal-header" style="background:rgba(5,150,105,.08);border-bottom:0;">
            <h3 style="color:#059669;">💵 استلام نقدي من موظف</h3>
            <button class="btn-close" onclick="closeModal('modal-receive-from-user')">✕</button>
        </div>
        <div class="modal-body">
            <div class="error-text" id="receive-global-error"
                style="background:var(--red-light);color:var(--red-dark);padding:10px;border-radius:8px;margin-bottom:12px;display:none;">
            </div>

            <div class="form-group">
                <label for="receive-user-id" class="form-label">الموظف <span style="color:var(--red)">*</span></label>
                <select id="receive-user-id" class="form-select">
                    <option value="">اختر موظف...</option>
                    <optgroup label="كول سينتر">
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
                <div class="error-text" id="receive-user-id-error"></div>
            </div>

            <div class="form-group">
                <label for="receive-amount" class="form-label">المبلغ <span style="color:var(--red)">*</span></label>
                <div style="display:flex;">
                    <input type="number" id="receive-amount" class="form-control" placeholder="0.00" min="0.01"
                        step="0.01" max="9999999.99" style="border-radius:0 8px 8px 0;flex:1;">
                    <span
                        style="background:var(--input-bg);padding:9px 12px;border-radius:8px 0 0 8px;font-size:13px;color:var(--text-muted);border:1px solid var(--border);border-right:none;">ج.م</span>
                </div>
                <div class="error-text" id="receive-amount-error"></div>
            </div>

            <div class="form-group">
                <label for="receive-date" class="form-label">التاريخ <span
                        style="color:var(--text-muted);font-weight:400;font-size:11px;">(اختياري — اليوم)</span></label>
                <input type="date" id="receive-date" class="form-control" max="{{ now()->toDateString() }}">
                <div class="error-text" id="receive-date-error"></div>
            </div>

            <div class="form-group">
                <label for="receive-description" class="form-label">ملاحظة <span
                        style="color:var(--text-muted);font-weight:400;font-size:11px;">(اختياري)</span></label>
                <textarea id="receive-description" class="form-control" rows="2" maxlength="500"
                    placeholder="وصف مختصر..."></textarea>
                <div class="error-text" id="receive-description-error"></div>
            </div>
        </div>
        <div class="modal-footer" style="border-top:0;">
            <button class="btn btn-secondary" onclick="closeModal('modal-receive-from-user')">إلغاء</button>
            <button class="btn" id="receive-submit-btn" onclick="submitReceiveFromUser()"
                style="background:#059669;color:#fff;">
                <span id="receive-submit-spinner" class="spin"
                    style="display:none;width:14px;height:14px;margin-left:6px;border-width:2px;border-color:rgba(255,255,255,.3);border-top-color:#fff;"></span>
                تأكيد الاستلام
            </button>
        </div>
    </div>
</div>

{{-- ── Detail Modal ──────────────────────────────────────────────── --}}
<div class="modal-overlay" id="modal-detail">
    <div class="modal">
        <div class="modal-header">
            <h3>تفاصيل المعاملة</h3>
            <button class="btn-close" onclick="closeModal('modal-detail')">✕</button>
        </div>
        <div class="modal-body" id="detail-modal-body">
            <div style="text-align:center;padding:40px;color:var(--text-muted);">
                <div class="spin" style="width:30px;height:30px;border-width:3px;margin:0 auto 12px;"></div>
                جاري التحميل...
            </div>
        </div>
    </div>
</div>

{{-- ── Edit Transaction Modal ─────────────────────────────────── --}}
<div class="modal-overlay" id="modal-edit-transaction">
    <div class="modal">
        <div class="modal-header" style="background:rgba(245,158,11,.08);border-bottom:0;">
            <h3 style="color:var(--yellow);">✏️ تعديل معاملة مالية</h3>
            <button class="btn-close" onclick="closeModal('modal-edit-transaction')">✕</button>
        </div>
        <div class="modal-body" id="edit-tx-body">
            <input type="hidden" id="edit-tx-id" value="">
            <div class="error-text" id="edit-tx-global-error"
                style="background:var(--red-light);color:var(--red-dark);padding:10px;border-radius:8px;margin-bottom:12px;display:none;">
            </div>

            <div class="form-group">
                <label class="form-label">نوع المعاملة</label>
                <div style="padding:8px 12px;background:var(--input-bg);border:1px solid var(--border);border-radius:8px;font-size:13px;"
                    id="edit-tx-type-label">...</div>
            </div>

            <div class="form-group">
                <label for="edit-tx-by-whom" class="form-label">بواسطة <span style="color:var(--red)">*</span></label>
                <input type="text" id="edit-tx-by-whom" class="form-control" placeholder="اسم الشخص أو الجهة"
                    maxlength="100" autocomplete="off">
            </div>

            <div class="form-group">
                <label for="edit-tx-amount" class="form-label">المبلغ <span style="color:var(--red)">*</span></label>
                <div style="display:flex;">
                    <input type="number" id="edit-tx-amount" class="form-control" placeholder="0.00" min="0.01"
                        step="0.01" max="9999999.99" style="border-radius:0 8px 8px 0;flex:1;">
                    <span
                        style="background:var(--input-bg);padding:9px 12px;border-radius:8px 0 0 8px;font-size:13px;color:var(--text-muted);border:1px solid var(--border);border-right:none;">ج.م</span>
                </div>
            </div>

            <div class="form-group">
                <label for="edit-tx-date" class="form-label">التاريخ <span
                        style="color:var(--text-muted);font-weight:400;font-size:11px;">(اختياري)</span></label>
                <input type="date" id="edit-tx-date" class="form-control" max="{{ now()->toDateString() }}">
            </div>

            <div class="form-group">
                <label for="edit-tx-note" class="form-label">ملاحظة <span
                        style="color:var(--text-muted);font-weight:400;font-size:11px;">(اختياري)</span></label>
                <textarea id="edit-tx-note" class="form-control" rows="2" maxlength="500"
                    placeholder="وصف مختصر..."></textarea>
            </div>
        </div>
        <div class="modal-footer" style="border-top:0;">
            <button class="btn btn-secondary" onclick="closeModal('modal-edit-transaction')">إلغاء</button>
            <button class="btn btn-primary" id="edit-tx-submit-btn" onclick="submitEditTransaction()">
                <span id="edit-tx-submit-spinner" class="spin"
                    style="display:none;width:14px;height:14px;margin-left:6px;border-width:2px;border-color:rgba(255,255,255,.3);border-top-color:#fff;"></span>
                حفظ التعديل
            </button>
        </div>
    </div>
</div>

<script>
    (function () {
        'use strict';

        // ── State ────────────────────────────────────────────────────
        let currentPage = {{ $initialTransactions->currentPage() }};

        // ── Collect active filter values ─────────────────────────────
        function getFilters() {
            return {
                from: document.getElementById('filter-from').value || null,
                to: document.getElementById('filter-to').value || null,
                type: document.getElementById('filter-type').value || null,
            };
        }

        function buildParams(extra = {}) {
            const f = getFilters();
            const p = {};
            if (f.from) p.from = f.from;
            if (f.to) p.to = f.to;
            if (f.type) p.type = f.type;
            return Object.assign(p, extra);
        }

        // ── KPI fetch ────────────────────────────────────────────────
        async function fetchStats() {
            try {
                var res = await axios.get('/admin/treasury/stats', { params: buildParams() });
                document.getElementById('kpi-payment-receipts').textContent = res.data.payment_receipts;
                document.getElementById('kpi-receiving-receipts').textContent = res.data.receiving_receipts;
                document.getElementById('kpi-expenses').textContent = res.data.total_expenses;
                document.getElementById('kpi-balance').textContent = res.data.balance;
            } catch (e) {
                console.warn('Treasury stats fetch failed', e);
            }
        }

        // ── Ledger fetch ─────────────────────────────────────────────
        async function fetchLedger(page = 1) {
            currentPage = page;
            try {
                const res = await axios.get('/admin/treasury/data', { params: buildParams({ page }) });
                renderTable(res.data);
            } catch (e) {
                console.warn('Treasury ledger fetch failed', e);
            }
        }

        // ── Refresh both after a write ────────────────────────────────
        async function refreshAll() {
            await Promise.all([fetchStats(), fetchLedger(1)]);
        }

        // ── Table renderer ───────────────────────────────────────────
        function typeBadge(type) {
            if (type === 'income') return '<span class="badge badge-green">إيراد</span>';
            if (type === 'expense') return '<span class="badge badge-red">مصروف</span>';
            if (type === 'settlement') return '<span class="badge badge-yellow">تسوية</span>';
            if (type === 'discount') return '<span class="badge badge-red">خصم</span>';
            if (type === 'pay_to_user') return '<span class="badge badge-cyan">دفع لموظف</span>';
            if (type === 'receive_from_user') return '<span class="badge badge-teal">استلام من موظف</span>';
            return '<span class="badge badge-indigo">صرف مديونية</span>';
        }

        function renderTable(payload) {
            const tbody = document.getElementById('ledger-tbody');
            const badge = document.getElementById('ledger-total-badge');
            badge.textContent = payload.total + ' معاملة';

            if (!payload.data || payload.data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:60px;">لا توجد معاملات مالية</td></tr>`;
                renderPagination(payload);
                return;
            }

            tbody.innerHTML = payload.data.map(tx => `
            <tr>
                <td style="color:var(--text-muted);font-size:12px;">${tx.id}</td>
                <td>${formatDate(tx.transaction_date)}</td>
                <td>${typeBadge(tx.type)}</td>
                <td style="font-weight:700;">${tx.amount}</td>
                <td>${escHtml(tx.by_whom)}</td>
                <td style="color:var(--text-muted);font-size:12px;">${truncate(escHtml(tx.note), 40)}</td>
                <td style="text-align:center;">
                    <div style="display:flex;gap:6px;justify-content:center;">
                        <button class="btn btn-sm btn-info" onclick="showDetail(${tx.id})" title="عرض التفاصيل">👁</button>
                        <button class="btn btn-sm" style="background:var(--yellow);color:#000;" onclick="editTransaction(${tx.id})" title="تعديل">✏️ تعديل</button>
                    </div>
                </td>
            </tr>
        `).join('');

            renderPagination(payload);
        }

        // ── Pagination renderer ──────────────────────────────────────
        function renderPagination(payload) {
            const wrapper = document.getElementById('ledger-pagination-wrapper');
            const container = document.getElementById('ledger-pagination');
            if (!wrapper || !container) return;

            if (payload.last_page <= 1) { wrapper.style.display = 'none'; return; }
            wrapper.style.display = '';

            let html = '';
            const prev_disabled = payload.current_page === 1 ? 'disabled' : '';
            const next_disabled = payload.current_page === payload.last_page ? 'disabled' : '';

            html += `<a class="${prev_disabled}" onclick="goToPage(${payload.current_page - 1})">‹</a>`;

            const start = Math.max(1, payload.current_page - 2);
            const end = Math.min(payload.last_page, payload.current_page + 2);
            if (start > 1) { html += `<a onclick="goToPage(1)">1</a>`; if (start > 2) html += '<span>…</span>'; }
            for (let i = start; i <= end; i++) {
                html += `<a class="${i === payload.current_page ? 'active' : ''}" onclick="goToPage(${i})">${i}</a>`;
            }
            if (end < payload.last_page) {
                if (end < payload.last_page - 1) html += '<span>…</span>';
                html += `<a onclick="goToPage(${payload.last_page})">${payload.last_page}</a>`;
            }
            html += `<a class="${next_disabled}" onclick="goToPage(${payload.current_page + 1})">›</a>`;

            container.innerHTML = html;
        }

        // ── Modal error helpers ───────────────────────────────────────
        function clearModalErrors(prefix) {
            ['by-whom', 'amount', 'date', 'note'].forEach(field => {
                const input = document.getElementById(`${prefix}-${field}`);
                const error = document.getElementById(`${prefix}-${field}-error`);
                if (input) input.style.borderColor = 'var(--border)';
                if (error) error.textContent = '';
            });
            const globalErr = document.getElementById(`${prefix}-global-error`);
            if (globalErr) { globalErr.textContent = ''; globalErr.style.display = 'none'; }
        }

        function setModalErrors(prefix, errors) {
            const fieldMap = { by_whom: 'by-whom', amount: 'amount', note: 'note', date: 'date' };
            Object.entries(errors).forEach(([field, messages]) => {
                const idSuffix = fieldMap[field] ?? field;
                const input = document.getElementById(`${prefix}-${idSuffix}`);
                const errorEl = document.getElementById(`${prefix}-${idSuffix}-error`);
                if (input) input.style.borderColor = 'var(--red)';
                if (errorEl) errorEl.textContent = messages[0];
            });
        }

        function setGlobalModalError(prefix, message) {
            const el = document.getElementById(`${prefix}-global-error`);
            if (!el) return;
            el.textContent = message;
            el.style.display = 'block';
        }

        function setModalLoading(prefix, loading) {
            const btn = document.getElementById(`${prefix}-submit-btn`);
            const spinner = document.getElementById(`${prefix}-submit-spinner`);
            if (btn) btn.disabled = loading;
            if (spinner) spinner.style.display = loading ? 'inline-block' : 'none';
        }

        function resetModalFields(prefix) {
            ['by-whom', 'amount', 'date', 'note'].forEach(field => {
                const el = document.getElementById(`${prefix}-${field}`);
                if (el) el.value = '';
            });
            clearModalErrors(prefix);
        }

        // ── Generic form submitter ───────────────────────────────────
        async function submitTreasuryForm(prefix, endpoint, modalId, successMsg) {
            clearModalErrors(prefix);
            setModalLoading(prefix, true);

            const payload = {
                by_whom: document.getElementById(`${prefix}-by-whom`).value.trim(),
                amount: document.getElementById(`${prefix}-amount`).value,
                note: document.getElementById(`${prefix}-note`).value.trim() || null,
                date: document.getElementById(`${prefix}-date`).value || null,
            };

            try {
                await axios.post(endpoint, payload);
                resetModalFields(prefix);
                closeModal(modalId);
                if (typeof showSuccess === 'function') showSuccess(successMsg);
                else if (typeof showToast === 'function') showToast(successMsg, 'success');
                await refreshAll();
            } catch (error) {
                if (error.response?.status === 422) {
                    setModalErrors(prefix, error.response.data.errors);
                } else {
                    setGlobalModalError(prefix, 'حدث خطأ غير متوقع. يرجى المحاولة مرة أخرى.');
                    console.error(`${prefix} submit error`, error);
                }
            } finally {
                setModalLoading(prefix, false);
            }
        }

        // ── Public: open modals ───────────────────────────────────────
        window.openIncomeModal = function () {
            resetModalFields('income');
            openModal('modal-income');
            setTimeout(() => { const el = document.getElementById('income-by-whom'); if (el) el.focus(); }, 250);
        };

        window.openExpenseModal = function () {
            resetModalFields('expense');
            openModal('modal-expense');
            setTimeout(() => { const el = document.getElementById('expense-by-whom'); if (el) el.focus(); }, 250);
        };

        // ── Public: submit handlers ───────────────────────────────────
        window.submitIncome = function () {
            submitTreasuryForm('income', '/admin/treasury/income', 'modal-income', 'تم إضافة الإيراد بنجاح ✓');
        };

        window.submitExpense = function () {
            submitTreasuryForm('expense', '/admin/treasury/expense', 'modal-expense', 'تم إضافة المصروف بنجاح ✓');
        };

        // ── Public: Dain handlers ──────────────────────────────────────
        window.openDainModal = function () {
            resetDainFields();
            openModal('modal-dain');
        };

        window.onDainSelectChange = function (source) {
            if (source === 'cc') {
                document.getElementById('dain-delivery-id').value = '';
            } else {
                document.getElementById('dain-callcenter-id').value = '';
            }
        };

        function resetDainFields() {
            document.getElementById('dain-callcenter-id').value = '';
            document.getElementById('dain-delivery-id').value = '';
            document.getElementById('dain-amount').value = '';
            document.getElementById('dain-date').value = '';
            document.getElementById('dain-note').value = '';
            clearModalErrors('dain');
        }

        window.submitDain = async function () {
            const prefix = 'dain';
            clearModalErrors(prefix);
            setModalLoading(prefix, true);

            const payload = {
                callcenter_id: document.getElementById('dain-callcenter-id').value || null,
                delivery_id: document.getElementById('dain-delivery-id').value || null,
                amount: document.getElementById('dain-amount').value,
                date: document.getElementById('dain-date').value || null,
                note: document.getElementById('dain-note').value.trim() || null,
            };

            try {
                await axios.post('/admin/treasury/dain', payload);
                resetDainFields();
                closeModal('modal-dain');
                if (typeof showSuccess === 'function') showSuccess('تم إضافة العملية بنجاح ✓');
                else if (typeof showToast === 'function') showToast('تم إضافة العملية بنجاح ✓', 'success');
                await refreshAll();
            } catch (error) {
                if (error.response?.status === 422) {
                    setModalErrors(prefix, error.response.data.errors);
                } else {
                    setGlobalModalError(prefix, 'حدث خطأ غير متوقع. يرجى المحاولة مرة أخرى.');
                    console.error('Dain submit error', error);
                }
            } finally {
                setModalLoading(prefix, false);
            }
        };

        // ── Public: Discount handlers ───────────────────────────────────
        window.openDiscountModal = function () {
            resetDiscountFields();
            openModal('modal-discount');
        };

        window.onDiscountSelectChange = function (source) {
            if (source === 'cc') {
                document.getElementById('discount-delivery-id').value = '';
            } else {
                document.getElementById('discount-callcenter-id').value = '';
            }
        };

        function resetDiscountFields() {
            document.getElementById('discount-callcenter-id').value = '';
            document.getElementById('discount-delivery-id').value = '';
            document.getElementById('discount-amount').value = '';
            document.getElementById('discount-date').value = '';
            document.getElementById('discount-note').value = '';
            clearModalErrors('discount');
        }

        window.submitDiscount = async function () {
            const prefix = 'discount';
            clearModalErrors(prefix);
            setModalLoading(prefix, true);

            const payload = {
                callcenter_id: document.getElementById('discount-callcenter-id').value || null,
                delivery_id: document.getElementById('discount-delivery-id').value || null,
                amount: document.getElementById('discount-amount').value,
                date: document.getElementById('discount-date').value || null,
                note: document.getElementById('discount-note').value.trim() || null,
            };

            try {
                await axios.post('/admin/treasury/discount', payload);
                resetDiscountFields();
                closeModal('modal-discount');
                if (typeof showSuccess === 'function') showSuccess('تم إضافة الخصم بنجاح ✓');
                else if (typeof showToast === 'function') showToast('تم إضافة الخصم بنجاح ✓', 'success');
                await refreshAll();
            } catch (error) {
                if (error.response?.status === 422) {
                    setModalErrors(prefix, error.response.data.errors);
                } else {
                    setGlobalModalError(prefix, 'حدث خطأ غير متوقع. يرجى المحاولة مرة أخرى.');
                    console.error('Discount submit error', error);
                }
            } finally {
                setModalLoading(prefix, false);
            }
        };

        // ── Public: pagination ────────────────────────────────────────
        window.goToPage = function (page) { fetchLedger(page); };

        // ── Public: filter actions ────────────────────────────────────
        window.applyFilters = function () { fetchStats(); fetchLedger(1); };
        window.resetFilters = function () {
            document.getElementById('filter-from').value = '';
            document.getElementById('filter-to').value = '';
            document.getElementById('filter-type').value = '';
            applyFilters();
        };

        // ── Public: detail modal ──────────────────────────────────────
        window.showDetail = async function (id) {
            const body = document.getElementById('detail-modal-body');
            body.innerHTML = `<div style="text-align:center;padding:40px;color:var(--text-muted);"><div class="spin" style="width:30px;height:30px;border-width:3px;margin:0 auto 12px;"></div>جاري التحميل...</div>`;
            openModal('modal-detail');

            try {
                const res = await axios.get(`/admin/treasury/${id}`);
                body.innerHTML = buildDetailHtml(res.data);
            } catch (e) {
                body.innerHTML = `<div style="color:var(--red);text-align:center;padding:20px;">حدث خطأ أثناء تحميل التفاصيل.</div>`;
            }
        };

        function buildDetailHtml(tx) {
            const badgeHtml = tx.type === 'income'
                ? '<span class="badge badge-green">إيراد</span>'
                : tx.type === 'expense'
                    ? '<span class="badge badge-red">مصروف</span>'
                    : tx.type === 'settlement'
                        ? '<span class="badge badge-yellow">تسوية</span>'
                        : tx.type === 'discount'
                            ? '<span class="badge badge-red">خصم</span>'
                            : '<span class="badge badge-indigo">دائن</span>';

            let settlementSection = '';
            if (tx.is_settlement && tx.settlement) {
                const s = tx.settlement;
                settlementSection = `
                <div class="divider"></div>
                <div class="card-title" style="font-size:13px;margin-bottom:10px;">معلومات التسوية</div>
                <div class="info-row"><span class="info-label">المحصل:</span><span>${escHtml(s.agent_name)}</span></div>
                <div class="info-row"><span class="info-label">هاتف:</span><span>${escHtml(s.agent_phone)}</span></div>
                <div class="info-row"><span class="info-label">بواسطة:</span><span>${escHtml(s.settled_by)}</span></div>
                <div class="info-row"><span class="info-label">وقت التسوية:</span><span>${escHtml(s.settled_at)}</span></div>
                <div class="info-row"><span class="info-label">ملاحظة التسوية:</span><span>${escHtml(s.note)}</span></div>`;
            }

            return `
            <div class="info-row"><span class="info-label">رقم المعاملة : </span><strong>#${tx.id}</strong></div>
            <div class="info-row"><span class="info-label">التاريخ: </span><span>${escHtml(tx.transaction_date)}</span></div>
            <div class="info-row"><span class="info-label">النوع: </span>${badgeHtml}</div>
            <div class="info-row"><span class="info-label">المبلغ: </span><strong style="font-size:16px;color:var(--yellow)">${tx.amount} ج.م</strong></div>
            <div class="info-row"><span class="info-label">بواسطة: </span><span>${escHtml(tx.by_whom)}</span></div>
            <div class="info-row"><span class="info-label">سُجِّل بواسطة: </span><span>${escHtml(tx.recorded_by)}</span></div>
            <div class="info-row"><span class="info-label">ملاحظة: </span><span>${escHtml(tx.note)}</span></div>
            <div class="info-row" style="font-size:11px;color:var(--text-muted);"><span class="info-label">أُنشئ في: </span><span>${escHtml(tx.created_at)}</span></div>
            ${settlementSection}
            <div style="margin-top:14px;">
                <button class="btn btn-danger btn-sm" onclick="exportTransactionPdf(${tx.id})">📄 تصدير PDF</button>
            </div>`;
        }

        // ── Edit Transaction ──────────────────────────────────────────
        window.editTransaction = async function (id) {
            var modal = document.getElementById('modal-edit-transaction');
            var body = document.getElementById('edit-tx-body');
            // Reset fields
            ['edit-tx-by-whom', 'edit-tx-amount', 'edit-tx-note', 'edit-tx-date'].forEach(function (f) {
                var el = document.getElementById(f); if (el) el.value = '';
            });
            document.getElementById('edit-tx-type-label').textContent = '...';
            document.getElementById('edit-tx-id').value = id;
            document.getElementById('edit-tx-global-error').style.display = 'none';
            openModal('modal-edit-transaction');
            try {
                var res = await axios.get('/admin/treasury/' + id);
                var tx = res.data;
                document.getElementById('edit-tx-by-whom').value = tx.by_whom !== '—' ? tx.by_whom : '';
                document.getElementById('edit-tx-amount').value = parseFloat(tx.amount.replace(/,/g, ''));
                document.getElementById('edit-tx-note').value = tx.note !== '—' ? tx.note : '';
                // transaction_date from show is formatted d/m/Y, convert to Y-m-d for input
                var parts = tx.transaction_date.split('/');
                if (parts.length === 3) document.getElementById('edit-tx-date').value = parts[2] + '-' + parts[1] + '-' + parts[0];
                document.getElementById('edit-tx-type-label').textContent = tx.type_label;
            } catch (e) {
                document.getElementById('edit-tx-global-error').textContent = 'فشل تحميل البيانات.';
                document.getElementById('edit-tx-global-error').style.display = 'block';
            }
        };

        window.submitEditTransaction = async function () {
            var id = document.getElementById('edit-tx-id').value;
            var btn = document.getElementById('edit-tx-submit-btn');
            var spinner = document.getElementById('edit-tx-submit-spinner');
            var errEl = document.getElementById('edit-tx-global-error');
            errEl.style.display = 'none';
            btn.disabled = true; spinner.style.display = 'inline-block';
            var payload = {
                by_whom: document.getElementById('edit-tx-by-whom').value.trim(),
                amount: document.getElementById('edit-tx-amount').value,
                note: document.getElementById('edit-tx-note').value.trim() || null,
                date: document.getElementById('edit-tx-date').value || null,
            };
            try {
                await axios.patch('/admin/treasury/' + id, payload);
                closeModal('modal-edit-transaction');
                if (typeof showSuccess === 'function') showSuccess('تم التعديل بنجاح ✓');
                await refreshAll();
            } catch (error) {
                if (error.response?.status === 422) {
                    var msgs = Object.values(error.response.data.errors || {}).flat().join(' | ');
                    errEl.textContent = msgs; errEl.style.display = 'block';
                } else {
                    errEl.textContent = 'حدث خطأ غير متوقع.'; errEl.style.display = 'block';
                }
            } finally {
                btn.disabled = false; spinner.style.display = 'none';
            }
        };

        // ── PDF Export ────────────────────────────────────────────────
        window.exportTransactionPdf = function (id) {
            window.open('/admin/treasury/' + id + '/pdf', '_blank');
        };

        // ── Utility helpers ───────────────────────────────────────────
        function escHtml(str) {
            if (str == null) return '—';
            return String(str)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;')
                .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }
        function truncate(str, len) {
            return str.length > len ? str.substring(0, len) + '…' : str;
        }
        function formatDate(ymd) {
            if (!ymd) return '—';
            const [y, m, d] = ymd.split('-');
            return `${d}/${m}/${y}`;
        }

        // ── Pay To User handlers ──────────────────────────────────────
        window.openPayToUserModal = function () {
            resetWalletModalFields('pay');
            openModal('modal-pay-to-user');
            setTimeout(() => { const el = document.getElementById('pay-user-id'); if (el) el.focus(); }, 250);
        };

        window.submitPayToUser = async function () {
            await submitWalletTransfer('pay', '/admin/treasury/pay-to-user', 'modal-pay-to-user', 'تم الدفع بنجاح ✓');
        };

        // ── Receive From User handlers ────────────────────────────────
        window.openReceiveFromUserModal = function () {
            resetWalletModalFields('receive');
            openModal('modal-receive-from-user');
            setTimeout(() => { const el = document.getElementById('receive-user-id'); if (el) el.focus(); }, 250);
        };

        window.submitReceiveFromUser = async function () {
            await submitWalletTransfer('receive', '/admin/treasury/receive-from-user', 'modal-receive-from-user', 'تم الاستلام بنجاح ✓');
        };

        // ── Wallet modal shared helpers ───────────────────────────────
        function resetWalletModalFields(prefix) {
            ['user-id', 'amount', 'date', 'description'].forEach(field => {
                const el = document.getElementById(`${prefix}-${field}`);
                if (el) el.value = '';
            });
            clearWalletModalErrors(prefix);
        }

        function clearWalletModalErrors(prefix) {
            ['user-id', 'amount', 'date', 'description'].forEach(field => {
                const input = document.getElementById(`${prefix}-${field}`);
                const error = document.getElementById(`${prefix}-${field}-error`);
                if (input) input.style.borderColor = 'var(--border)';
                if (error) error.textContent = '';
            });
            const globalErr = document.getElementById(`${prefix}-global-error`);
            if (globalErr) { globalErr.textContent = ''; globalErr.style.display = 'none'; }
        }

        async function submitWalletTransfer(prefix, endpoint, modalId, successMsg) {
            clearWalletModalErrors(prefix);
            const btn = document.getElementById(`${prefix}-submit-btn`);
            const spinner = document.getElementById(`${prefix}-submit-spinner`);
            if (btn) btn.disabled = true;
            if (spinner) spinner.style.display = 'inline-block';

            const payload = {
                user_id: document.getElementById(`${prefix}-user-id`).value || null,
                amount: document.getElementById(`${prefix}-amount`).value,
                description: document.getElementById(`${prefix}-description`).value.trim() || null,
                date: document.getElementById(`${prefix}-date`).value || null,
            };

            try {
                const res = await axios.post(endpoint, payload);
                resetWalletModalFields(prefix);
                closeModal(modalId);
                if (typeof showSuccess === 'function') showSuccess(res.data.message || successMsg);
                else if (typeof showToast === 'function') showToast(res.data.message || successMsg, 'success');
                await refreshAll();
            } catch (error) {
                if (error.response?.status === 422) {
                    const errors = error.response.data.errors || {};
                    const fieldMap = { user_id: 'user-id', amount: 'amount', description: 'description', date: 'date' };
                    Object.entries(errors).forEach(([field, messages]) => {
                        const idSuffix = fieldMap[field] ?? field;
                        const input = document.getElementById(`${prefix}-${idSuffix}`);
                        const errorEl = document.getElementById(`${prefix}-${idSuffix}-error`);
                        if (input) input.style.borderColor = 'var(--red)';
                        if (errorEl) errorEl.textContent = messages[0];
                    });
                } else {
                    const globalErr = document.getElementById(`${prefix}-global-error`);
                    if (globalErr) {
                        globalErr.textContent = error.response?.data?.message || 'حدث خطأ غير متوقع.';
                        globalErr.style.display = 'block';
                    }
                }
            } finally {
                if (btn) btn.disabled = false;
                if (spinner) spinner.style.display = 'none';
            }
        }

        // ── Boot: register polling ────────────────────────────────────
        if (typeof addPolling === 'function') {
            addPolling(setInterval(fetchStats, 30000));
            addPolling(setInterval(() => fetchLedger(currentPage), 30000));
        }

    })();
</script>