@extends('storefront.layouts.app')
@section('title', 'My Returns')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 py-8">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">My Returns & Exchanges</h1>
        @include('storefront.account.partials.nav')
    </div>

    @forelse($returnRequests as $rr)
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-3">
            <div class="flex items-center justify-between">
                <span class="font-medium text-gray-800 text-sm">{{ $rr->orderItem?->product_name }}</span>
                <span @class([
                    'text-xs px-2.5 py-1 rounded-full font-medium',
                    'bg-yellow-100 text-yellow-700' => $rr->status === 'pending',
                    'bg-blue-100 text-blue-700' => $rr->status === 'approved',
                    'bg-red-100 text-red-700' => $rr->status === 'rejected',
                    'bg-green-100 text-green-700' => $rr->status === 'completed',
                ])>{{ \App\Models\ReturnRequest::STATUSES[$rr->status] }}</span>
            </div>
            <p class="text-xs text-gray-500 mt-1">
                {{ \App\Models\ReturnRequest::TYPES[$rr->type] }} &middot; {{ \App\Models\ReturnRequest::REASONS[$rr->reason] ?? $rr->reason }}
                &middot; Order {{ $rr->order?->order_number }}
            </p>
            @if($rr->description)
                <p class="text-sm text-gray-600 mt-2">{{ $rr->description }}</p>
            @endif
            @if($rr->admin_notes)
                <p class="text-xs text-gray-400 mt-2">Store response: {{ $rr->admin_notes }}</p>
            @endif
            <p class="text-xs text-gray-400 mt-2">{{ $rr->created_at->format('d M Y') }}</p>
        </div>
    @empty
        <div class="text-center py-16">
            <p class="text-gray-500">No return or exchange requests yet.</p>
        </div>
    @endforelse

    {{ $returnRequests->links() }}
</div>
@endsection
