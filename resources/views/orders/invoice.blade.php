<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $order->order_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #1f2937; margin: 0; padding: 2rem; font-size: 14px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #1f2937; padding-bottom: 1rem; margin-bottom: 1.5rem; }
        .store-name { font-size: 1.5rem; font-weight: bold; }
        .muted { color: #6b7280; }
        .invoice-title { text-align: right; }
        .invoice-title h1 { margin: 0; font-size: 1.75rem; }
        .grid { display: flex; justify-content: space-between; gap: 2rem; margin-bottom: 1.5rem; }
        .grid > div { flex: 1; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 1.5rem; }
        th, td { text-align: left; padding: 0.5rem; border-bottom: 1px solid #e5e7eb; }
        th { background: #f9fafb; font-size: 0.75rem; text-transform: uppercase; color: #6b7280; }
        .totals { width: 300px; margin-left: auto; }
        .totals td { border: none; padding: 0.25rem 0.5rem; }
        .totals .total-row td { font-weight: bold; font-size: 1.1rem; border-top: 2px solid #1f2937; }
        .badge { display: inline-block; padding: 0.15rem 0.6rem; border-radius: 999px; font-size: 0.75rem; background: #f3f4f6; }
        .print-bar { text-align: right; margin-bottom: 1rem; }
        @media print {
            .print-bar { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="print-bar">
        <button onclick="window.print()">Print</button>
    </div>

    <div class="header">
        <div>
            <div class="store-name">{{ $order->store->name }}</div>
            @if($order->store->tagline)
                <div class="muted">{{ $order->store->tagline }}</div>
            @endif
            @if($order->store->getSetting('support_phone'))
                <div class="muted">{{ $order->store->getSetting('support_phone') }}</div>
            @endif
        </div>
        <div class="invoice-title">
            <h1>Invoice</h1>
            <div class="muted">#{{ $order->order_number }}</div>
            <div class="muted">{{ $order->created_at->format('d M Y') }}</div>
        </div>
    </div>

    <div class="grid">
        <div>
            <strong>Bill / Ship To</strong>
            <div>{{ $order->customer_name }}</div>
            <div>{{ $order->customer_phone }}</div>
            @if($order->customer_email)
                <div>{{ $order->customer_email }}</div>
            @endif
            <div>{{ implode(', ', array_filter([$order->address_line, $order->area, $order->thana_name, $order->district_name, $order->division_name])) }}</div>
        </div>
        <div>
            <strong>Payment</strong>
            <div>Method: {{ \App\Models\Order::PAYMENT_METHODS[$order->payment_method] ?? $order->payment_method }}</div>
            <div>Status: <span class="badge">{{ \App\Models\Order::PAYMENT_STATUSES[$order->payment_status] ?? $order->payment_status }}</span></div>
            <div>Order Status: <span class="badge">{{ $order->status_label }}</span></div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Unit Price</th>
                <th>Qty</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
                <tr>
                    <td>
                        {{ $item->product_name }}
                        @if($item->variant_label)
                            <div class="muted">{{ $item->variant_label }}</div>
                        @endif
                    </td>
                    <td>৳{{ number_format($item->unit_price, 2) }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>৳{{ number_format($item->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Subtotal</td><td>৳{{ number_format($order->subtotal, 2) }}</td></tr>
        @if($order->discount_amount > 0)
            <tr><td>Discount</td><td>-৳{{ number_format($order->discount_amount, 2) }}</td></tr>
        @endif
        <tr><td>Delivery</td><td>৳{{ number_format($order->delivery_charge, 2) }}</td></tr>
        @if($order->tax_amount > 0)
            <tr><td>Tax</td><td>৳{{ number_format($order->tax_amount, 2) }}</td></tr>
        @endif
        <tr class="total-row"><td>Total</td><td>৳{{ number_format($order->total, 2) }}</td></tr>
        @if($order->refunded_amount > 0)
            <tr><td>Refunded</td><td>-৳{{ number_format($order->refunded_amount, 2) }}</td></tr>
        @endif
    </table>

    @if($order->customer_notes)
        <div><strong>Customer Notes:</strong> {{ $order->customer_notes }}</div>
    @endif
</body>
</html>
