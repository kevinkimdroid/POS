<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Label · {{ $product->name }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=fraunces:600|outfit:500,600" rel="stylesheet">
    <style>
        body { margin: 24px; font-family: Outfit, "Segoe UI", sans-serif; background: #efe7d8; color: #14261e; }
        .label {
            width: 260px;
            margin: 0 12px 12px 0;
            padding: 14px 14px 12px;
            display: inline-block;
            vertical-align: top;
            background: #fffdf8;
            border: 1px solid #12362b;
            border-radius: 12px;
        }
        .brand { margin: 0; font-size: 10px; letter-spacing: 0.16em; text-transform: uppercase; color: #a67c32; }
        strong { display: block; margin-top: 4px; font-family: Fraunces, Georgia, serif; font-size: 18px; letter-spacing: -0.02em; }
        .sku {
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px dashed #d9cdb8;
            font-family: "Courier New", monospace;
            font-size: 20px;
            letter-spacing: 1px;
        }
        .code { margin-top: 4px; color: #6d655b; font-size: 12px; }
        button {
            height: 40px;
            margin-bottom: 16px;
            padding: 0 14px;
            border: 0;
            border-radius: 10px;
            background: #12362b;
            color: #f6f1e7;
            font: inherit;
            font-weight: 600;
            cursor: pointer;
        }
        @media print {
            body { margin: 0; background: #fff; }
            .noprint { display: none; }
            .label { break-inside: avoid; }
        }
    </style>
</head>
<body>
    <p class="noprint"><button type="button" onclick="window.print()">Print labels</button></p>
    @php $codes = $product->variants->isEmpty() ? collect([(object) ['name' => null, 'sku' => $product->sku, 'barcode' => $product->barcode]]) : $product->variants; @endphp
    @foreach ($codes as $code)
        <div class="label">
            <p class="brand">Vancha</p>
            <strong>{{ $product->name }} {{ $code->name }}</strong>
            <div class="sku">{{ $code->barcode ?: $code->sku }}</div>
            <div class="code">{{ $code->sku }}</div>
        </div>
    @endforeach
</body>
</html>
