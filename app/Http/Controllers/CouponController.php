<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Store;
use Illuminate\Support\Facades\Auth;

class CouponController extends Controller
{
    public function index()
    {
        $customer = Auth::guard('customer')->user();
        $store = Store::current();

        $available = Coupon::where('store_id', $store->id)
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()))
            ->where(fn ($q) => $q->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit'))
            ->get()
            ->filter(function (Coupon $coupon) use ($customer) {
                if (! $coupon->usage_limit_per_customer) {
                    return true;
                }

                $customerUses = $customer->orders()->where('coupon_id', $coupon->id)->count();

                return $customerUses < $coupon->usage_limit_per_customer;
            });

        $usedCouponIds = $customer->orders()->whereNotNull('coupon_id')->pluck('coupon_id');
        $used = Coupon::whereIn('id', $usedCouponIds)->get();

        $expired = Coupon::where('store_id', $store->id)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->whereNotIn('id', $usedCouponIds)
            ->get();

        return view('storefront.account.coupons', compact('available', 'used', 'expired'));
    }
}
