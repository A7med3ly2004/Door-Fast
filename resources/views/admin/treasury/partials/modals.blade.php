{{--
resources/views/admin/treasury/partials/modals.blade.php
─────────────────────────────────────────────────────────
Contains TWO modals:
1. #income-modal — Add Income (إضافة إيراد)
2. #expense-modal — Add Expense (إضافة مصروف)

Both share identical field structure.
The JS in content.blade.php drives them; the modals
themselves are pure HTML with no inline scripts.

Included at the BOTTOM of content.blade.php via:
@include('admin.treasury.partials.modals')
--}}


{{-- ── 1. INCOME MODAL ───────────────────────────────────────────── --}}
<div class="modal fade" id="income-modal" tabindex="-1" aria-labelledby="income-modal-label" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header bg-success bg-opacity-10 border-bottom-0 pb-0">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-success bg-opacity-20 p-2 d-flex">
                        <i class="fas fa-arrow-down text-success"></i>
                    </div>
                    <h5 class="modal-title fw-bold mb-0" id="income-modal-label">إضافة إيراد</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"
                    id="income-modal-close-x"></button>
            </div>

            <div class="modal-body pt-3">

                {{-- Global error alert (shown on unexpected server errors) --}}
                <div class="alert alert-danger d-none" id="income-global-error" role="alert"></div>

                {{-- Form fields --}}
                <div class="mb-3">
                    <label for="income-by-whom" class="form-label fw-semibold">
                        بواسطة <span class="text-danger">*</span>
                    </label>
                    <input type="text" id="income-by-whom" class="form-control" placeholder="اسم الشخص أو الجهة"
                        maxlength="100" autocomplete="off">
                    <div class="invalid-feedback" id="income-by-whom-error"></div>
                </div>

                <div class="mb-3">
                    <label for="income-amount" class="form-label fw-semibold">
                        المبلغ <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <input type="number" id="income-amount" class="form-control" placeholder="0.00" min="0.01"
                            step="0.01" max="9999999.99">
                        <span class="input-group-text">ج.م</span>
                    </div>
                    <div class="invalid-feedback d-block" id="income-amount-error"></div>
                </div>

                <div class="mb-3">
                    <label for="income-date" class="form-label fw-semibold">
                        التاريخ
                        <span class="text-muted fw-normal small">(اختياري — الافتراضي اليوم)</span>
                    </label>
                    <input type="date" id="income-date" class="form-control" max="{{ now()->toDateString() }}">
                    <div class="invalid-feedback" id="income-date-error"></div>
                </div>

                <div class="mb-1">
                    <label for="income-note" class="form-label fw-semibold">
                        ملاحظة
                        <span class="text-muted fw-normal small">(اختياري)</span>
                    </label>
                    <textarea id="income-note" class="form-control" rows="2" maxlength="500"
                        placeholder="وصف مختصر للإيراد..."></textarea>
                    <div class="invalid-feedback" id="income-note-error"></div>
                </div>

            </div>{{-- .modal-body --}}

            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-outline-secondary" id="income-modal-cancel"
                    data-bs-dismiss="modal">
                    إلغاء
                </button>
                <button type="button" class="btn btn-success px-4" id="income-submit-btn" onclick="submitIncome()">
                    <span id="income-submit-spinner" class="spinner-border spinner-border-sm me-1 d-none"
                        role="status"></span>
                    حفظ الإيراد
                </button>
            </div>

        </div>
    </div>
</div>


{{-- ── 2. EXPENSE MODAL ──────────────────────────────────────────── --}}
<div class="modal fade" id="expense-modal" tabindex="-1" aria-labelledby="expense-modal-label" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header bg-danger bg-opacity-10 border-bottom-0 pb-0">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-danger bg-opacity-20 p-2 d-flex">
                        <i class="fas fa-arrow-up text-danger"></i>
                    </div>
                    <h5 class="modal-title fw-bold mb-0" id="expense-modal-label">إضافة مصروف</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"
                    id="expense-modal-close-x"></button>
            </div>

            <div class="modal-body pt-3">

                <div class="alert alert-danger d-none" id="expense-global-error" role="alert"></div>

                <div class="mb-3">
                    <label for="expense-by-whom" class="form-label fw-semibold">
                        بواسطة <span class="text-danger">*</span>
                    </label>
                    <input type="text" id="expense-by-whom" class="form-control" placeholder="اسم الشخص أو الجهة"
                        maxlength="100" autocomplete="off">
                    <div class="invalid-feedback" id="expense-by-whom-error"></div>
                </div>

                <div class="mb-3">
                    <label for="expense-amount" class="form-label fw-semibold">
                        المبلغ <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <input type="number" id="expense-amount" class="form-control" placeholder="0.00" min="0.01"
                            step="0.01" max="9999999.99">
                        <span class="input-group-text">ج.م</span>
                    </div>
                    <div class="invalid-feedback d-block" id="expense-amount-error"></div>
                </div>

                <div class="mb-3">
                    <label for="expense-date" class="form-label fw-semibold">
                        التاريخ
                        <span class="text-muted fw-normal small">(اختياري — الافتراضي اليوم)</span>
                    </label>
                    <input type="date" id="expense-date" class="form-control" max="{{ now()->toDateString() }}">
                    <div class="invalid-feedback" id="expense-date-error"></div>
                </div>

                <div class="mb-1">
                    <label for="expense-note" class="form-label fw-semibold">
                        ملاحظة
                        <span class="text-muted fw-normal small">(اختياري)</span>
                    </label>
                    <textarea id="expense-note" class="form-control" rows="2" maxlength="500"
                        placeholder="وصف مختصر للمصروف..."></textarea>
                    <div class="invalid-feedback" id="expense-note-error"></div>
                </div>

            </div>{{-- .modal-body --}}

            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-outline-secondary" id="expense-modal-cancel"
                    data-bs-dismiss="modal">
                    إلغاء
                </button>
                <button type="button" class="btn btn-danger px-4" id="expense-submit-btn" onclick="submitExpense()">
                    <span id="expense-submit-spinner" class="spinner-border spinner-border-sm me-1 d-none"
                        role="status"></span>
                    حفظ المصروف
                </button>
            </div>

        </div>
    </div>
</div>