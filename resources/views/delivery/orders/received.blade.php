@extends('layouts.delivery')

@section('page_title', 'الطلبات المستلمة')

@section('content')
    @include('delivery.orders.partials.received_content')
@endsection
