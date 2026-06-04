@extends('layouts.app')
@section('title', $product->name)
@section('content')
<h1>{{ $product->name }}</h1>
<p>{{ $product->description }}</p>
<p><x-money :amount="$product->base_price" /></p>
<p>Category: {{ $product->category->name }}</p>
@foreach($product->variants as $variant)
    <div>{{ $variant->name }} - SKU: {{ $variant->sku }}</div>
@endforeach
@endsection