<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class NotificationBadge extends Component
{
    public int $count = 0;

    public function mount(): void
    {
        $this->count = Auth::guard('customer')->check()
            ? Auth::guard('customer')->user()->unreadNotifications()->count()
            : 0;
    }

    public function render()
    {
        return view('livewire.notification-badge');
    }
}
