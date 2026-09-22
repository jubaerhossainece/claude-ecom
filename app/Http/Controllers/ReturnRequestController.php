<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use App\Models\ReturnRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReturnRequestController extends Controller
{
    public function index()
    {
        $customer = Auth::guard('customer')->user();
        $returnRequests = $customer->returnRequests()->with('order', 'orderItem')->latest()->paginate(10);

        return view('storefront.account.returns', compact('returnRequests'));
    }

    public function store(Request $request, OrderItem $orderItem)
    {
        $customer = Auth::guard('customer')->user();
        $order = $orderItem->order;

        abort_if(! $order || $order->customer_id !== $customer->id, 403);
        abort_unless($order->status === 'delivered', 422, 'Only delivered orders are eligible for return or exchange.');
        abort_if($orderItem->returnRequests()->exists(), 422, 'A return/exchange request already exists for this item.');

        $data = $request->validate([
            'type' => 'required|in:return,exchange',
            'reason' => 'required|in:'.implode(',', array_keys(ReturnRequest::REASONS)),
            'description' => 'nullable|string|max:2000',
            'photos.*' => 'nullable|image|max:5120',
        ]);

        $returnRequest = ReturnRequest::create([
            'store_id' => $order->store_id,
            'order_id' => $order->id,
            'order_item_id' => $orderItem->id,
            'customer_id' => $customer->id,
            'type' => $data['type'],
            'reason' => $data['reason'],
            'description' => $data['description'] ?? null,
            'status' => 'pending',
        ]);

        foreach ($request->file('photos', []) as $photo) {
            $returnRequest->addMedia($photo)->toMediaCollection('photos');
        }

        return back()->with('success', 'Your request has been submitted. We\'ll review it shortly.');
    }
}
