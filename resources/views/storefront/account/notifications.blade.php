@extends('storefront.layouts.app')
@section('title', 'Notifications')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 py-8">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Notifications</h1>
        @include('storefront.account.partials.nav')
    </div>

    @forelse($notifications as $notification)
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-3">
            <p class="font-medium text-gray-800 text-sm">{{ $notification->data['title'] ?? 'Notification' }}</p>
            <p class="text-sm text-gray-600 mt-1">{{ $notification->data['message'] ?? '' }}</p>
            <div class="flex items-center justify-between mt-2">
                <span class="text-xs text-gray-400">{{ $notification->created_at->diffForHumans() }}</span>
                @if(isset($notification->data['order_id']))
                    <a href="{{ route('account.orders.show', $notification->data['order_id']) }}" class="text-xs text-primary font-medium hover:underline">View Order</a>
                @endif
            </div>
        </div>
    @empty
        <div class="text-center py-16">
            <p class="text-4xl mb-3">🔔</p>
            <p class="text-gray-500">No notifications yet</p>
        </div>
    @endforelse

    {{ $notifications->links() }}
</div>
@endsection
