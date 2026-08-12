<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $customer = Auth::guard('customer')->user();
        $notifications = $customer->notifications()->paginate(20);

        $customer->unreadNotifications()->update(['read_at' => now()]);

        return view('storefront.account.notifications', compact('notifications'));
    }
}
