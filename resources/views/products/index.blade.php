@extends('layouts.app')
@section('title', 'Products')
@section('content')
<h1>Products</h1>
@foreach($products as $product)
    <div>
        <a href="{{ route('products.show', $product) }}">{{ $product->name }}</a>
        <span><x-money :amount="$product->base_price" /></span>
        <span>{{ $product->category->name }}</span>
    </div>
@endforeach
{{ $products->links() }}
@endsection