@extends('layouts.app')

@section('content')
<div class="container">
    <h1>{{ $category->name }}</h1>

    <div class="row">
        @foreach ($category->products as $product)
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">{{ $product->name }}</h5>
                        <p class="card-text"><x-money :amount="$product->base_price ?? $product->price ?? 0" /></p>
                        <a href="{{ route('products.show', $product->slug) }}" class="btn btn-primary">View Details</a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection