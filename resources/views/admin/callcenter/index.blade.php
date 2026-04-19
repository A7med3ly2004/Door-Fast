{{--
    resources/views/admin/callcenter/index.blade.php
    ─────────────────────────────────────────────
    Full-page wrapper. Extends the admin layout shell.
    On a direct URL visit this is what Laravel renders.
    On SPA navigation the layout is already in the DOM — only
    the partial (content.blade.php) is injected into #page-content.
--}}
@extends('layouts.admin')

@section('page-title', 'إدارة كول سنتر')

@section('content')
    @include('admin.callcenter.partials.content')
@endsection
