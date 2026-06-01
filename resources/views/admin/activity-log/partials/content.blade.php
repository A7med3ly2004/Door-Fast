{{--
resources/views/admin/activity-log/partials/content.blade.php
──────────────────────────────────────────────────────────────
SPA partial — injected into #page-content on navigation.
On direct page load it is @included from activity-log/index.blade.php.
--}}

{{-- ── Page KPI strip ── --}}
<div class="section-header">
    <h2 style="display:flex;align-items:center;gap:10px;">
        <span style="font-size:22px;">سجل العمليات</span>
        <span class="badge badge-blue" id="al-total-badge"
            style="font-size:20px;padding:2px 26px;border-radius:8px;">{{ $initialLogs->total() }}</span>
    </h2>
    <div style="display:flex;gap:8px;">
        <button class="btn btn-secondary btn-sm" onclick="alRefresh()">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                style="width:14px;height:14px">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            تحديث
        </button>
    </div>
</div>

{{-- ── Filter Bar ── --}}
<div class="card" style="margin-bottom:20px;">
    <div style="display:flex; gap:12px; align-items:end; flex-wrap: nowrap;">

        {{-- Log ID search --}}
        <div style="flex: 0.8; min-width: 0;">
            <label class="form-label">رقم العملية</label>
            <input type="number" class="form-control" id="al-log-id" placeholder="#ID…"
                value="{{ $filters['log_id'] ?? '' }}" oninput="alDebouncedSearch()">
        </div>

        {{-- Client search --}}
        <div style="flex: 1.5; min-width: 0;">
            <label class="form-label">بحث (كود العميل - رقم الهاتف)</label>
            <input type="text" class="form-control" id="al-client-search" placeholder="رقم الهاتف أو كود العميل…"
                value="{{ $filters['client_search'] ?? '' }}" oninput="alDebouncedSearch()">
        </div>

        {{-- Order number --}}
        <div style="flex: 1; min-width: 0;">
            <label class="form-label">رقم الطلب</label>
            <input type="text" class="form-control" id="al-order-number" placeholder="DF-XXXX…"
                value="{{ $filters['order_number'] ?? '' }}" oninput="alDebouncedSearch()">
        </div>

        {{-- Delivery agent --}}
        <div style="flex: 1; min-width: 130px;">
            <label class="form-label">المندوب</label>
            <div class="relative group" style="z-index: 50;">
                <div class="form-control"
                    style="cursor:pointer; display:flex; justify-content:space-between; align-items:center;">
                    <span id="label-al-delivery-id">الكل</span>
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </div>
                <input type="hidden" id="al-delivery-id" value="{{ $filters['delivery_id'] ?? '' }}"
                    onchange="alApplyFilters()">
                <div class="absolute top-full right-0 w-full opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all bg-white/80 backdrop-blur shadow-lg rounded-md mt-1 overflow-hidden"
                    style="border:1px solid var(--border); background-color: rgba(255, 255, 255, 0.9); max-height:200px; overflow-y:auto;">
                    <div class="px-3 py-2 cursor-pointer hover:bg-green-50 hover:text-green-700 text-sm transition-colors text-gray-800"
                        onclick="selectDropdown('al-delivery-id', '', 'الكل')">الكل</div>
                    @foreach($deliveryUsers as $u)
                        <div class="px-3 py-2 cursor-pointer hover:bg-green-50 hover:text-green-700 text-sm transition-colors text-gray-800"
                            onclick="selectDropdown('al-delivery-id', '{{ $u->id }}', '{{ $u->name }}')">{{ $u->name }}
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Callcenter --}}
        <div style="flex: 1; min-width: 130px;">
            <label class="form-label">الكول سنتر</label>
            <div class="relative group" style="z-index: 40;">
                <div class="form-control"
                    style="cursor:pointer; display:flex; justify-content:space-between; align-items:center;">
                    <span id="label-al-callcenter-id">الكل</span>
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </div>
                <input type="hidden" id="al-callcenter-id" value="{{ $filters['callcenter_id'] ?? '' }}"
                    onchange="alApplyFilters()">
                <div class="absolute top-full right-0 w-full opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all bg-white/80 backdrop-blur shadow-lg rounded-md mt-1 overflow-hidden"
                    style="border:1px solid var(--border); background-color: rgba(255, 255, 255, 0.9); max-height:200px; overflow-y:auto;">
                    <div class="px-3 py-2 cursor-pointer hover:bg-green-50 hover:text-green-700 text-sm transition-colors text-gray-800"
                        onclick="selectDropdown('al-callcenter-id', '', 'الكل')">الكل</div>
                    @foreach($callcenterUsers as $u)
                        <div class="px-3 py-2 cursor-pointer hover:bg-green-50 hover:text-green-700 text-sm transition-colors text-gray-800"
                            onclick="selectDropdown('al-callcenter-id', '{{ $u->id }}', '{{ $u->name }}')">{{ $u->name }}
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Admin --}}
        <div style="flex: 1; min-width: 130px;">
            <label class="form-label">المديرين</label>
            <div class="relative group" style="z-index: 35;">
                <div class="form-control"
                    style="cursor:pointer; display:flex; justify-content:space-between; align-items:center;">
                    <span id="label-al-admin-id">الكل</span>
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </div>
                <input type="hidden" id="al-admin-id" value="{{ $filters['admin_id'] ?? '' }}"
                    onchange="alApplyFilters()">
                <div class="absolute top-full right-0 w-full opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all bg-white/80 backdrop-blur shadow-lg rounded-md mt-1 overflow-hidden"
                    style="border:1px solid var(--border); background-color: rgba(255, 255, 255, 0.9); max-height:200px; overflow-y:auto;">
                    <div class="px-3 py-2 cursor-pointer hover:bg-green-50 hover:text-green-700 text-sm transition-colors text-gray-800"
                        onclick="selectDropdown('al-admin-id', '', 'الكل')">الكل</div>
                    @foreach($adminUsers as $u)
                        <div class="px-3 py-2 cursor-pointer hover:bg-green-50 hover:text-green-700 text-sm transition-colors text-gray-800"
                            onclick="selectDropdown('al-admin-id', '{{ $u->id }}', '{{ $u->name }}')">{{ $u->name }}</div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Reset --}}
        <div style="flex: 0.8; min-width: 100px;">
            <button class="btn btn-secondary" style="width:100%" onclick="alResetFilters()">
                مسح الفلاتر
            </button>
        </div>
    </div>
</div>

{{-- ── Log Table ── --}}
<div class="card" style="padding:0;overflow:hidden;position:relative;border: 1px solid var(--border);">
    <div id="al-loading"
        style="display:none;position:absolute;inset:0;background:rgba(15,23,42,0.6);z-index:10;align-items:center;justify-content:center;border-radius:16px;">
        <div style="text-align:center;color:#f1f5f9">
            <div class="spin" style="width:32px;height:32px;border-width:3px;margin:0 auto 8px;"></div>
            <div style="font-size:13px;">جارٍ التحميل…</div>
        </div>
    </div>

    <div class="table-wrap" style="border:none;border-radius:0;">
        <table id="al-table">
            <thead>
                <tr>
                    <th style="text-align: center; width:90px;">رقم العملية</th>
                    <th>النشاط</th>
                    <th style="text-align: center;">البيان</th>
                    <th style="text-align: center;">المستخدم</th>
                    <th style="text-align: center;">الدور</th>
                    <th style="text-align: center;">التاريخ والوقت</th>
                </tr>
            </thead>
            <tbody id="al-tbody">
                @forelse($initialLogs as $log)
                    @include('admin.activity-log.partials.row', ['log' => $log])
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center;padding:40px;color:var(--text-muted)">
                            لا توجد عمليات مسجّلة بعد
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div id="al-pagination"
        style="padding:16px 20px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
        <span id="al-page-info" class="text-muted" style="font-size:13px;">
            عرض {{ $initialLogs->firstItem() ?? 0 }}–{{ $initialLogs->lastItem() ?? 0 }}
            من {{ $initialLogs->total() }} نشاط
        </span>
        <div id="al-page-controls" style="display:flex;gap:6px;align-items:center;">
            @if($initialLogs->onFirstPage())
                <span class="btn btn-secondary btn-sm" style="opacity:.4;cursor:default;">السابق</span>
            @else
                <button class="btn btn-secondary btn-sm"
                    onclick="alGoPage({{ $initialLogs->currentPage() - 1 }})">السابق</button>
            @endif

            <span class="text-muted" style="font-size:13px;padding:0 8px;">
                صفحة {{ $initialLogs->currentPage() }} / {{ $initialLogs->lastPage() }}
            </span>

            @if($initialLogs->hasMorePages())
                <button class="btn btn-secondary btn-sm"
                    onclick="alGoPage({{ $initialLogs->currentPage() + 1 }})">التالي</button>
            @else
                <span class="btn btn-secondary btn-sm" style="opacity:.4;cursor:default;">التالي</span>
            @endif
        </div>
    </div>
</div>

{{-- Live indicator --}}
<div style="display:flex;align-items:center;gap:6px;margin-top:12px;font-size:12px;color:var(--text-muted);">
    <span id="al-live-dot"
        style="width:8px;height:8px;border-radius:50%;background:var(--success);animation:pulse 2s infinite;display:inline-block;"></span>
    مباشر — يتحدث كل 15 ثانية
    <span id="al-last-updated" style="margin-right:auto;"></span>
</div>

<style>
    @keyframes pulse {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: 0.3;
        }
    }


    .al-event-icon {
        font-size: 18px;
        line-height: 1;
    }

    .al-desc {
        font-size: 14px;
        color: var(--text);
        font-weight: 500;
    }

    .al-sub {
        font-size: 14px;
        color: var(--text-muted);
        margin-top: 2px;
        text-align: center;
    }

    .al-time-main {
        font-size: 14px;
        color: var(--text);
        font-weight: 600;
        direction: ltr;
        text-align: center;
    }

    .al-time-ago {
        font-size: 12px;
        color: var(--text-muted);
        margin-top: 2px;
        text-align: center;
    }
</style>

<script>
    (function () {
        let _alPage = {{ $initialLogs->currentPage() }};
        let _alFilters = @json($filters);
        let _alTimer = null;
        let _alDebounce = null;

        // ── Build params ──────────────────────────────────────────────
        function alParams(page = 1) {
            return {
                page,
                log_id: document.getElementById('al-log-id')?.value || '',
                client_search: document.getElementById('al-client-search')?.value || '',
                order_number: document.getElementById('al-order-number')?.value || '',
                delivery_id: document.getElementById('al-delivery-id')?.value || '',
                callcenter_id: document.getElementById('al-callcenter-id')?.value || '',
                admin_id: document.getElementById('al-admin-id')?.value || '',
            };
        }

        // ── Fetch ─────────────────────────────────────────────────────
        async function alFetch(page = 1, showLoader = true) {
            const loader = document.getElementById('al-loading');
            if (showLoader && loader) loader.style.display = 'flex';

            try {
                const res = await axios.get('{{ route("admin.activity-log.data") }}', { params: alParams(page) });
                const d = res.data;
                _alPage = page;

                renderRows(d.data);
                renderPagination(d.current_page, d.last_page, d.total, d.data.length);

                const badge = document.getElementById('al-total-badge');
                if (badge) badge.textContent = d.total;

                const lu = document.getElementById('al-last-updated');
                if (lu) lu.textContent = 'آخر تحديث: ' + new Date().toLocaleString('en-GB', { hour: '2-digit', minute: '2-digit', hour12: false });
            } catch (e) {
                console.error('ActivityLog fetch error:', e);
            } finally {
                if (loader) loader.style.display = 'none';
            }
        }

        // ── Render rows ───────────────────────────────────────────────
        function alRowClass(event) {
            if (event.startsWith('order.')) return 'al-row-order';
            if (event.startsWith('client.')) return 'al-row-client';
            if (event.startsWith('user.')) return 'al-row-user';
            if (event.startsWith('shop.')) return 'al-row-shop';
            if (event.startsWith('treasury.')) return 'al-row-treasury';
            if (event.startsWith('shift.')) return 'al-row-shift';
            if (event.startsWith('settlement.')) return 'al-row-settlement';
            return '';
        }

        function renderRows(rows) {
            const tbody = document.getElementById('al-tbody');
            if (!tbody) return;

            if (!rows.length) {
                tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text-muted)">لا توجد عمليات مسجّلة لهذه الفلاتر</td></tr>`;
                return;
            }

            tbody.innerHTML = rows.map(r => `
            <tr class="${alRowClass(r.event)}">
                <td style="text-align:center;">
                    <span style="font-size:12px;font-weight:700;color:var(--text-muted);font-family:monospace;">${r.id}</span>
                </td>
                <td style="font-size:14px;">
                    <div class="al-desc">${r.description}</div>
                </td>
                <td style="font-size:14px; text-align: center;">
                    ${r.subject_label ? `<div class="al-sub" style="font-size:14px;">${r.subject_label}</div>` : '—'}
                </td>
                <td style="font-size:14px;font-weight:600; text-align: center;">${r.causer_name}</td>
                <td style="text-align: center;"><span class="badge ${r.causer_role_badge}">${r.causer_role_label}</span></td>
                <td style="font-size:14px;">
                    <div class="al-time-main">${r.created_at}</div>
                    <div class="al-time-ago">${r.created_at_human}</div>
                </td>
            </tr>
        `).join('');
        }

        function subjectTypeLabel(t) {
            const m = {
                order: 'طلب', client: 'عميل', user: 'مستخدم',
                shop: 'متجر', treasury: 'خزينة', shift: 'وردية',
                settlement: 'تسوية',
            };
            return m[t] || t;
        }

        // ── Pagination ────────────────────────────────────────────────
        function renderPagination(current, last, total, count) {
            const info = document.getElementById('al-page-info');
            const ctrl = document.getElementById('al-page-controls');
            if (info) {
                const from = (current - 1) * 25 + 1;
                const to = (current - 1) * 25 + count;
                info.textContent = `عرض ${from}–${to} من ${total} نشاط`;
            }
            if (!ctrl) return;
            const prevDis = current <= 1;
            const nextDis = current >= last;
            ctrl.innerHTML = `
            <button class="btn btn-secondary btn-sm" onclick="alGoPage(${current - 1})" ${prevDis ? 'disabled style="opacity:.4"' : ''}>السابق</button>
            <span class="text-muted" style="font-size:13px;padding:0 8px;">صفحة ${current} / ${last}</span>
            <button class="btn btn-secondary btn-sm" onclick="alGoPage(${current + 1})" ${nextDis ? 'disabled style="opacity:.4"' : ''}>التالي</button>
        `;
        }

        // ── Public API ────────────────────────────────────────────────
        window.alGoPage = (p) => alFetch(p);
        window.alRefresh = () => alFetch(_alPage, true);

        window.alApplyFilters = () => alFetch(1);

        window.alDebouncedSearch = () => {
            clearTimeout(_alDebounce);
            _alDebounce = setTimeout(() => alFetch(1), 400);
        };

        window.alResetFilters = () => {
            ['al-log-id', 'al-client-search', 'al-order-number'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.value = '';
            });
            ['al-delivery-id', 'al-callcenter-id', 'al-admin-id'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.value = '';
                const lbl = document.getElementById('label-' + id);
                if (lbl) lbl.innerText = 'الكل';
            });
            alFetch(1);
        };

        // ── Auto-refresh every 15s ────────────────────────────────────
        function startPolling() {
            _alTimer = setInterval(() => alFetch(_alPage, false), 15000);
            if (typeof addPolling === 'function') addPolling(_alTimer);
        }

        startPolling();
    })();
</script>