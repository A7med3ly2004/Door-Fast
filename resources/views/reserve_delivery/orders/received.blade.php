@extends('layouts.reserve_delivery')

@section('page_title', 'طلبات مستلمة')

@section('content')
    @include('reserve_delivery.orders.partials.received_content')
@endsection
