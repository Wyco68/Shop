@extends('layouts.app')
@section('title', 'My Orders')
@section('content')
<h1>My Orders</h1>
@foreach($orders as $order)
    <div>Order #{{ $order->id }} - <x-money :amount="$order->total" /> - {{ $order->status }}</div>
@endforeach
@endsection
