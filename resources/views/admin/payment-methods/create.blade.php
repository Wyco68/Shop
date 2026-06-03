@extends('layouts.admin')

@section('title', 'Add Payment Method')

@section('content')
<div class="max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold text-slate-800 mb-6">Add payment method</h1>
    <x-admin.card title="Details">
        <form action="{{ route('admin.payment-methods.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @include('admin.payment-methods._form', ['paymentMethod' => null])
            <div class="flex gap-3 pt-2">
                <button type="submit" class="px-5 py-2.5 bg-sky-600 hover:bg-sky-700 text-white rounded-xl font-semibold text-sm">Save</button>
                <a href="{{ route('admin.payment-methods.index') }}" class="px-5 py-2.5 border border-slate-200 rounded-xl text-sm font-medium text-slate-600">Cancel</a>
            </div>
        </form>
    </x-admin.card>
</div>
@endsection
