{{--
    resources/views/admin/treasury/partials/pagination.blade.php
    ─────────────────────────────────────────────────────────────
    Used ONLY for the server-side first-paint render inside index().
    After first load, pagination is re-rendered by JS (renderPagination()).

    Variables:
      $currentPage → int
      $lastPage    → int
--}}
@if($lastPage > 1)
<ul class="pagination pagination-sm mb-0">

    {{-- Previous --}}
    <li class="page-item {{ $currentPage === 1 ? 'disabled' : '' }}">
        <a class="page-link" href="#"
           onclick="goToPage({{ $currentPage - 1 }}); return false;">
            <i class="fas fa-chevron-right"></i>
        </a>
    </li>

    @php
        $start = max(1, $currentPage - 2);
        $end   = min($lastPage, $currentPage + 2);
    @endphp

    @if($start > 1)
        <li class="page-item"><a class="page-link" href="#" onclick="goToPage(1); return false;">1</a></li>
        @if($start > 2)
            <li class="page-item disabled"><span class="page-link">…</span></li>
        @endif
    @endif

    @for($i = $start; $i <= $end; $i++)
        <li class="page-item {{ $i === $currentPage ? 'active' : '' }}">
            <a class="page-link" href="#" onclick="goToPage({{ $i }}); return false;">{{ $i }}</a>
        </li>
    @endfor

    @if($end < $lastPage)
        @if($end < $lastPage - 1)
            <li class="page-item disabled"><span class="page-link">…</span></li>
        @endif
        <li class="page-item"><a class="page-link" href="#" onclick="goToPage({{ $lastPage }}); return false;">{{ $lastPage }}</a></li>
    @endif

    {{-- Next --}}
    <li class="page-item {{ $currentPage === $lastPage ? 'disabled' : '' }}">
        <a class="page-link" href="#"
           onclick="goToPage({{ $currentPage + 1 }}); return false;">
            <i class="fas fa-chevron-left"></i>
        </a>
    </li>

</ul>
@endif
