<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $sale->receipt_no }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=fraunces:600|outfit:400,500,600" rel="stylesheet">
    <style>
        body { margin: 0; background: #efe7d8; font-family: Outfit, "Segoe UI", sans-serif; color: #14261e; }
        .slip {
            width: 300px;
            margin: 28px auto;
            padding: 22px 18px 18px;
            background: #fffdf8;
            border-radius: 8px 8px 18px 18px;
            box-shadow: 0 18px 40px rgba(16, 40, 31, 0.12);
        }
        .mark { display: flex; align-items: center; gap: 10px; }
        .mark img { width: 36px; height: 36px; }
        h1 { margin: 0; font-family: Fraunces, Georgia, serif; font-size: 26px; letter-spacing: -0.03em; }
        .gold { margin: 2px 0 0; font-size: 10px; letter-spacing: 0.16em; text-transform: uppercase; color: #a67c32; }
        .rule { margin: 12px 0; border: 0; border-top: 1px dashed #d9cdb8; }
        p { margin: 0 0 8px; font-size: 13px; line-height: 1.45; }
        table { width: 100%; font-size: 13px; border-collapse: collapse; }
        td { padding: 4px 0; vertical-align: top; }
        .right { text-align: right; font-variant-numeric: tabular-nums; }
        .total { font-family: Fraunces, Georgia, serif; font-size: 20px; }
        .muted { color: #6d655b; }
        .noprint { width: 300px; margin: 0 auto 28px; display: flex; gap: 8px; }
        button {
            height: 40px;
            padding: 0 14px;
            border: 0;
            border-radius: 10px;
            background: #12362b;
            color: #f6f1e7;
            font: inherit;
            font-weight: 600;
            cursor: pointer;
        }
        button.ghost { background: #fffdf8; color: #14261e; border: 1px solid #e3d8c6; }
        @media print {
            body { background: #fff; }
            .slip { margin: 0; width: auto; box-shadow: none; }
            .noprint { display: none; }
        }
    </style>
</head>
<body>
    <article class="slip">
        <div class="mark">
            <img src="{{ asset('brand/vancha-mark.svg') }}" alt="">
            <div><h1>Vancha</h1><p class="gold">Hardware</p></div>
        </div>
        <hr class="rule">
        <p>{{ $sale->receipt_no }}<br>{{ $sale->created_at->format('d M Y H:i') }}<br>{{ $sale->user->name }}</p>
        <table>
            @foreach ($sale->lines as $line)
                <tr>
                    <td>{{ rtrim(rtrim(number_format($line->quantity, 3), '0'), '.') }} {{ $line->description }}</td>
                    <td class="right">{{ number_format($line->line_total, 2) }}</td>
                </tr>
            @endforeach
        </table>
        <hr class="rule">
        <p class="total">Total KES {{ number_format($sale->total, 2) }}</p>
        <p class="muted">VAT included KES {{ number_format($sale->tax, 2) }}</p>
        @foreach ($sale->payments as $payment)
            <p>{{ ucfirst($payment->method) }} {{ number_format($payment->amount, 2) }} @if ($payment->method === 'cash') · change {{ number_format($payment->change_amount, 2) }} @endif</p>
        @endforeach
        <p class="muted">This copy is stored locally. It has not been signed by KRA eTIMS.</p>
    </article>
    <p class="noprint">
        <button type="button" onclick="window.print()">Print</button>
        @if (auth()->user()->allows('void') && $sale->status === 'completed')
            <form method="POST" action="{{ route('sales.void', $sale) }}">
                @csrf
                <button class="ghost" type="submit">Void</button>
            </form>
        @endif
    </p>
</body>
</html>
