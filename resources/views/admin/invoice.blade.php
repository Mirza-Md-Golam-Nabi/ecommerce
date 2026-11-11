@php
    use App\Enums\OrderItemStatus;
    use App\Support\Money;

    /** @var \App\Models\Order $order */
    $kg = fn (int $grams): string => rtrim(rtrim(number_format($grams / 1000, 3), '0'), '.').' কেজি';
@endphp
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ইনভয়েস {{ $order->order_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Hind Siliguri', 'Noto Sans Bengali', system-ui, sans-serif; color: #1c1c1c; margin: 0; padding: 24px; font-size: 14px; }
        .sheet { max-width: 720px; margin: 0 auto; }
        header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #1c1c1c; padding-bottom: 12px; margin-bottom: 16px; }
        h1 { margin: 0; font-size: 22px; }
        .muted { color: #666; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px; }
        .box { border: 1px solid #ddd; border-radius: 8px; padding: 10px 12px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th, td { border-bottom: 1px solid #e5e5e5; padding: 8px 6px; text-align: left; vertical-align: top; }
        th { background: #f5f5f5; }
        .right { text-align: right; }
        .struck { text-decoration: line-through; color: #888; }
        .totals { margin-left: auto; width: 280px; }
        .totals div { display: flex; justify-content: space-between; padding: 4px 0; }
        .grand { font-size: 18px; font-weight: 700; border-top: 2px solid #1c1c1c; margin-top: 4px; padding-top: 8px !important; }
        .note { margin-top: 16px; padding: 10px 12px; background: #f7f3e8; border-radius: 8px; }
        .actions { text-align: center; margin-bottom: 16px; }
        .actions button { font: inherit; padding: 8px 20px; border-radius: 8px; border: 0; background: #0F3D2E; color: #fff; cursor: pointer; }
        @media print { .actions { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
<div class="sheet">
    <div class="actions"><button type="button" onclick="window.print()">প্রিন্ট করুন</button></div>

    <header>
        <div>
            <h1>{{ $storeName }}</h1>
            @if ($storePhone)<div class="muted">হেল্পলাইন: {{ $storePhone }}</div>@endif
        </div>
        <div class="right">
            <strong>{{ $order->order_number }}</strong><br>
            <span class="muted">{{ $order->placed_at->format('d M Y, h:i A') }}</span><br>
            <span>ক্যাশ অন ডেলিভারি</span>
        </div>
    </header>

    <div class="grid">
        <div class="box">
            <strong>প্রাপক</strong><br>
            {{ $order->shipping_name }}<br>
            {{ $order->shipping_phone }}
        </div>
        <div class="box">
            <strong>ঠিকানা</strong><br>
            {{ $order->address_line }}, {{ $order->area_name }}
            @if ($order->customer_note)<br><span class="muted">নোট: {{ $order->customer_note }}</span>@endif
        </div>
    </div>

    <table>
        <thead>
            <tr><th>পণ্য</th><th>পরিমাণ</th><th class="right">একক দাম</th><th class="right">মোট</th></tr>
        </thead>
        <tbody>
        @foreach ($order->items as $item)
            @php $unavailable = $item->status === OrderItemStatus::Unavailable; @endphp
            <tr>
                <td class="{{ $unavailable ? 'struck' : '' }}">{{ $item->product_name }}<br><span class="muted">{{ $item->unit_label }}</span></td>
                <td>
                    @if ($unavailable)
                        পাওয়া যায়নি
                    @elseif ($item->isWeighted())
                        {{ $item->actual_weight_grams !== null ? $kg($item->actual_weight_grams) : '≈ '.$kg($item->quantity * (int) $item->nominal_weight_grams) }}
                    @else
                        {{ $item->fulfilled_quantity ?? $item->quantity }}টি
                    @endif
                </td>
                <td class="right">{{ Money::format($item->unit_price) }}</td>
                <td class="right">{{ $unavailable ? '—' : Money::format($item->line_total) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div><span>পণ্যের দাম</span><span>{{ Money::format($order->subtotal) }}</span></div>
        <div><span>ডেলিভারি চার্জ</span><span>{{ Money::format($order->delivery_charge) }}</span></div>
        <div class="grand"><span>মোট আদায়যোগ্য</span><span>{{ Money::format($order->grand_total) }}</span></div>
    </div>

    @if ($order->items->contains(fn ($item) => $item->status === OrderItemStatus::Unavailable))
        <div class="note">কিছু পণ্য আজ বাজারে মানসম্মত পাওয়া যায়নি, তাই সেগুলো পাঠানো হয়নি এবং দাম বিল থেকে বাদ দেওয়া হয়েছে।</div>
    @endif
</div>
</body>
</html>
