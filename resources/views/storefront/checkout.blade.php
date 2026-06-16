@extends('storefront.layouts.app')
@section('title', 'Checkout')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Checkout</h1>

    @guest('customer')
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6 text-sm text-blue-800">
            <strong>Tip:</strong> <a href="{{ route('customer.login') }}" class="underline">Login</a> to save your address and track your orders easily.
        </div>
    @endguest

    @livewire('checkout-form')
</div>
@endsection
