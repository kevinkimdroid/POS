<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Vancha')</title>
    <link rel="icon" href="{{ asset('brand/vancha-mark.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=fraunces:560,600|outfit:400,500,600" rel="stylesheet">
    <style>
        :root {
            --ink: #14261e;
            --pine: #12362b;
            --pine-2: #1b4a3c;
            --gold: #c4963d;
            --gold-soft: #e7c27a;
            --cream: #efe7d8;
            --paper: #fffdf8;
            --line: #e3d8c6;
            --muted: #6d655b;
            --danger: #8d3a2c;
            --danger-bg: #f8ece8;
            --ok: #1d6b45;
            --ok-bg: #e7f6ee;
            --shadow: 0 1px 0 rgba(255, 255, 255, 0.7), 0 16px 40px rgba(16, 40, 31, 0.06);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            grid-template-columns: 248px 1fr;
            font-family: Outfit, "Segoe UI", sans-serif;
            color: var(--ink);
            background:
                radial-gradient(900px 420px at 100% -8%, rgba(228, 184, 106, 0.22), transparent 55%),
                linear-gradient(180deg, #f7f1e6 0%, var(--cream) 220px);
        }
        aside {
            position: sticky;
            top: 0;
            height: 100vh;
            display: flex;
            flex-direction: column;
            padding: 22px 14px 16px;
            color: #f6f1e7;
            background:
                radial-gradient(280px 180px at 20% 0%, rgba(228, 184, 106, 0.2), transparent 70%),
                linear-gradient(180deg, #1a4c3b 0%, #0d241c 100%);
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 0 8px 22px;
            padding-bottom: 18px;
            border-bottom: 1px solid rgba(246, 241, 231, 0.12);
            text-decoration: none;
            color: inherit;
        }
        .brand img { width: 42px; height: 42px; border-radius: 12px; }
        .brand strong {
            display: block;
            font-family: Fraunces, Georgia, serif;
            font-size: 24px;
            font-weight: 600;
            letter-spacing: -0.03em;
            line-height: 1;
        }
        .brand span {
            display: block;
            margin-top: 5px;
            font-size: 10px;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--gold-soft);
        }
        nav { display: flex; flex-direction: column; gap: 2px; overflow: auto; min-height: 0; flex: 1; padding-right: 4px; }
        .nav-label { margin: 18px 12px 6px; font-size: 10px; letter-spacing: 0.18em; text-transform: uppercase; color: rgba(231, 194, 122, 0.85); }
        nav a {
            padding: 9px 12px;
            border-radius: 11px;
            color: rgba(246, 241, 231, 0.78);
            text-decoration: none;
            font-weight: 500;
            font-size: 14.5px;
        }
        nav a:hover { background: rgba(246, 241, 231, 0.08); color: #fff; }
        nav a.active {
            background: rgba(231, 194, 122, 0.16);
            color: #fff;
            box-shadow: inset 3px 0 0 var(--gold-soft);
        }
        .side-foot {
            margin-top: 12px;
            padding: 12px;
            border-radius: 16px;
            background: rgba(7, 22, 17, 0.35);
        }
        .who { margin: 0 0 10px; font-size: 12px; letter-spacing: 0.04em; text-transform: uppercase; color: rgba(246, 241, 231, 0.62); }
        .who b { display: block; margin-top: 2px; color: #fff; font-size: 15px; font-weight: 600; letter-spacing: 0; text-transform: none; }
        .side-foot button {
            width: 100%;
            height: 38px;
            border: 1px solid rgba(246, 241, 231, 0.18);
            border-radius: 10px;
            background: transparent;
            color: #f6f1e7;
            font: inherit;
            font-weight: 500;
            cursor: pointer;
        }
        .side-foot button:hover { background: rgba(246, 241, 231, 0.08); }
        main { padding: 28px 36px 64px; min-width: 0; }
        .canvas { max-width: 1280px; }
        .shelf-tools { margin-bottom: 12px; }
        .shelf {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(168px, 1fr));
            gap: 10px;
            max-height: calc(100vh - 210px);
            overflow: auto;
            padding: 2px 4px 12px 0;
        }
        .shelf-label {
            grid-column: 1 / -1;
            margin: 8px 0 0;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--gold);
        }
        .tile {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 4px;
            min-height: 108px;
            padding: 12px;
            border: 1px solid var(--line);
            border-radius: 16px;
            background: var(--paper);
            text-align: left;
            font: inherit;
            color: inherit;
            cursor: pointer;
            box-shadow: 0 8px 18px rgba(16, 40, 31, 0.04);
        }
        .tile:hover { border-color: #c9a15a; background: #fffaf2; }
        .tile b { font-size: 14px; line-height: 1.25; }
        .tile .sub { margin: 0; font-size: 12px; }
        .tile .price {
            margin-top: auto;
            font-family: Fraunces, Georgia, serif;
            font-size: 18px;
            letter-spacing: -0.02em;
        }
        .ticket-sum { margin-top: 8px; }
        .ticket-sum .sold {
            margin-top: 8px;
            padding-top: 10px;
            border-top: 1px dashed #ddcfb6;
        }
        .ticket-sum .sold span, .ticket-sum .sold strong {
            font-family: Fraunces, Georgia, serif;
            font-size: 28px;
            letter-spacing: -0.03em;
        }
        .more { margin: 8px 0 12px; }
        .more summary { cursor: pointer; font-weight: 600; color: var(--pine); }
        .drop {
            height: 32px;
            width: 32px;
            border: 0;
            border-radius: 8px;
            background: transparent;
            color: var(--muted);
            cursor: pointer;
            font-size: 18px;
        }
        .sale-line {
            padding: 12px 0;
            border-bottom: 1px solid #efe6d8;
        }
        .sale-line .toprow { display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; }
        .sale-line .who { min-width: 0; }
        .sale-line .who strong { display: block; }
        .stepper { display: flex; align-items: center; gap: 6px; }
        .stepper button {
            width: 36px;
            height: 36px;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: #fff;
            font: inherit;
            font-size: 18px;
            cursor: pointer;
        }
        .stepper input { width: 76px; height: 36px; text-align: center; padding: 0 6px; }
        .stepper .unit { min-width: 52px; color: var(--muted); font-size: 13px; }
        .line-amt { font-variant-numeric: tabular-nums; text-align: right; }
        .top {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 16px;
            margin-bottom: 22px;
        }
        .eyebrow {
            margin: 0 0 6px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: var(--gold);
        }
        h1 {
            margin: 0;
            font-family: Fraunces, Georgia, serif;
            font-size: 40px;
            font-weight: 560;
            letter-spacing: -0.035em;
            line-height: 1.05;
        }
        h2 {
            margin: 28px 0 12px;
            font-family: Fraunces, Georgia, serif;
            font-size: 24px;
            font-weight: 560;
            letter-spacing: -0.03em;
        }
        .sub { margin: 8px 0 0; color: var(--muted); line-height: 1.45; }
        a { color: var(--pine-2); font-weight: 600; text-decoration: none; }
        a:hover { color: #8a6424; }
        .btn, button.btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 42px;
            padding: 0 16px;
            border: 0;
            border-radius: 12px;
            background: linear-gradient(180deg, #1b4d3c, var(--pine));
            color: #f6f1e7;
            font: inherit;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            box-shadow: 0 8px 16px rgba(18, 54, 43, 0.16);
        }
        .btn:hover { background: #1c5644; color: #fff; }
        .btn.secondary {
            background: var(--paper);
            color: var(--ink);
            border: 1px solid var(--line);
            box-shadow: none;
        }
        .btn.secondary:hover { background: #f7f1e6; color: var(--ink); }
        .flash, .error, .note {
            margin-bottom: 16px;
            padding: 12px 14px;
            border-radius: 14px;
            font-size: 14px;
            line-height: 1.45;
        }
        .flash { background: var(--ok-bg); color: var(--ok); border: 1px solid #cfe8d8; }
        .error { background: var(--danger-bg); color: var(--danger); border: 1px solid #f0d2cb; }
        .error a { color: inherit; text-decoration: underline; }
        .note { background: #f8f1e2; color: #6a5424; border: 1px solid #ead9b4; }
        .panel {
            background: var(--paper);
            border: 1px solid rgba(227, 216, 198, 0.95);
            border-radius: 20px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        .panel:has(input), .panel:has(select), form.panel { overflow: visible; }
        form.panel, .panel > form:not(.search) { padding: 18px 20px 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 13px 16px; text-align: left; border-bottom: 1px solid #efe6d8; font-size: 14px; vertical-align: middle; }
        th {
            background: #f8f3ea;
            font-size: 11px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--muted);
            font-weight: 600;
        }
        tbody tr:last-child td { border-bottom: 0; }
        tbody tr:hover td { background: #fbf7f0; }
        td[colspan] { color: var(--muted); }
        .money, .num { font-variant-numeric: tabular-nums; }
        .low { color: var(--danger); font-weight: 650; }
        .search {
            display: flex;
            gap: 8px;
            margin-bottom: 14px;
            padding: 10px;
            background: var(--paper);
            border: 1px solid var(--line);
            border-radius: 16px;
            box-shadow: var(--shadow);
        }
        input, select, textarea {
            height: 44px;
            padding: 0 12px;
            border: 1px solid #ddd3c2;
            border-radius: 12px;
            background: #fffdf9;
            font: inherit;
            color: var(--ink);
        }
        input[type="file"] { height: auto; padding: 10px 12px; background: #fff; }
        input[type="checkbox"] { width: 16px; height: 16px; accent-color: var(--pine); }
        .field input, .field select { width: 100%; }
        input:focus, select:focus, textarea:focus { outline: 3px solid rgba(196, 150, 61, 0.22); border-color: #c9a15a; }
        .search input { flex: 1; height: 48px; border-color: transparent; background: transparent; box-shadow: none; }
        .search input:focus { outline: none; }
        .pager { display: flex; gap: 12px; align-items: center; padding: 14px 16px; color: var(--muted); }
        .grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; }
        .grid:has(.stat:only-child) { grid-template-columns: minmax(180px, 260px); }
        .stat { position: relative; padding: 18px 18px 16px 20px; }
        .stat::before {
            content: "";
            position: absolute;
            left: 0;
            top: 16px;
            bottom: 16px;
            width: 3px;
            border-radius: 3px;
            background: linear-gradient(180deg, var(--gold-soft), var(--gold));
        }
        .stat span { display: block; color: var(--muted); font-size: 13px; }
        .stat strong {
            display: block;
            margin-top: 8px;
            font-family: Fraunces, Georgia, serif;
            font-size: 28px;
            font-weight: 560;
            letter-spacing: -0.03em;
            font-variant-numeric: tabular-nums;
        }
        label { display: block; margin-bottom: 6px; font-size: 13px; font-weight: 600; }
        .field { margin-bottom: 14px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; align-items: start; }
        .form-grid > .field { margin-bottom: 0; }
        .span-2 { grid-column: span 2; }
        .hint { margin: 6px 0 0; color: var(--muted); font-size: 12px; line-height: 1.4; }
        .margin { margin: 8px 0 0; font-family: Fraunces, Georgia, serif; font-size: 22px; color: var(--pine); }
        .checks { display: flex; flex-wrap: wrap; gap: 16px 22px; align-items: center; padding: 4px 0 8px; }
        .checks label { display: flex; gap: 8px; align-items: center; margin: 0; font-weight: 500; }
        .variant {
            display: grid;
            grid-template-columns: 1.2fr 1fr 1fr 0.8fr auto;
            gap: 8px;
            margin-bottom: 8px;
            padding: 10px;
            border: 1px dashed var(--line);
            border-radius: 14px;
            background: #fbf7f1;
        }
        .variant input { width: 100%; }
        .icon-btn {
            height: 44px;
            width: 44px;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: #fff;
            cursor: pointer;
            font-size: 18px;
        }
        .counter {
            display: grid;
            grid-template-columns: minmax(0, 1.15fr) minmax(320px, 0.85fr);
            gap: 18px;
            align-items: start;
        }
        .counter .panel { overflow: visible; }
        .counter > section:last-child { position: sticky; top: 18px; }
        .results button {
            width: 100%;
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 16px;
            border: 0;
            border-bottom: 1px solid #efe6d8;
            background: transparent;
            text-align: left;
            font: inherit;
            cursor: pointer;
        }
        .results button:hover { background: #f8f3ea; }
        .results button:last-child { border-bottom: 0; border-radius: 0 0 20px 20px; }
        .cart-line {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 124px auto;
            gap: 10px;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #efe6d8;
        }
        .cart-line input, .cart-line select { height: 36px; width: 100%; }
        .cart-line select { margin-top: 6px; }
        .totals { margin-top: 8px; }
        .totals div { display: flex; justify-content: space-between; gap: 12px; padding: 4px 0; }
        .totals .grand {
            margin-top: 6px;
            padding-top: 10px;
            border-top: 1px dashed #ddcfb6;
            font-family: Fraunces, Georgia, serif;
            font-size: 30px;
            letter-spacing: -0.03em;
        }
        .receipt {
            margin-top: 16px;
            padding: 16px;
            border-radius: 16px;
            background: #10281f;
            color: #f6f1e7;
        }
        .receipt .sub, .receipt a { color: var(--gold-soft); }
        td form, .receive {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }
        td form input { height: 38px; width: 92px; }
        td form .btn { height: 38px; box-shadow: none; }
        .head-actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .chip {
            display: inline-flex;
            margin-left: 8px;
            padding: 2px 8px;
            border-radius: 999px;
            background: #f3ead6;
            color: #6a5424;
            font-size: 12px;
            font-weight: 600;
        }
        .panel .search { margin: 0; border: 0; box-shadow: none; background: transparent; }
        .split { display: grid; grid-template-columns: 1fr 1fr; gap: 16px 18px; align-items: start; }
        .split h2 { margin-top: 22px; }
        @media (max-width: 980px) {
            body { display: block; }
            aside {
                position: sticky;
                top: 0;
                z-index: 4;
                height: auto;
                padding: 14px 14px 10px;
            }
            .brand { margin: 0 0 10px; padding-bottom: 12px; }
            nav { flex-direction: row; overflow-x: auto; gap: 6px; padding-bottom: 4px; }
            .nav-label { display: none; }
            nav a { white-space: nowrap; }
            nav a.active { box-shadow: none; }
            .side-foot { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: 10px; }
            .who { margin: 0; }
            .side-foot form { margin: 0; }
            .side-foot button { width: auto; padding: 0 14px; }
            .grid, .form-grid, .counter, .variant, .split { grid-template-columns: 1fr; }
            .span-2 { grid-column: auto; }
            .counter > section:last-child { position: static; }
            .shelf { max-height: none; }
            main { padding: 18px 14px 48px; }
            h1 { font-size: 32px; }
            .top { align-items: flex-start; flex-direction: column; }
        }
    </style>
</head>
<body>
    <aside>
        <a class="brand" href="{{ route('home') }}">
            <img src="{{ asset('brand/vancha-mark.svg') }}" alt="">
            <div><strong>Vancha</strong><span>Hardware</span></div>
        </a>
        <nav>
            <a href="{{ route('home') }}" @class(['active' => request()->routeIs('home')])>Today</a>
            @if (auth()->user()->allows('sell'))
                <p class="nav-label">Sell</p>
                <a href="{{ route('checkout.index') }}" @class(['active' => request()->routeIs('checkout.*')])>Counter</a>
                <a href="{{ route('quotes.index') }}" @class(['active' => request()->routeIs('quotes.*')])>Quotes</a>
                @if (auth()->user()->allows('refund'))
                    <a href="{{ route('returns.index') }}" @class(['active' => request()->routeIs('returns.*')])>Returns</a>
                @endif
                <a href="{{ route('deliveries.index') }}" @class(['active' => request()->routeIs('deliveries.*')])>Deliveries</a>
            @endif
            <p class="nav-label">Stock</p>
            <a href="{{ route('inventory.index') }}" @class(['active' => request()->routeIs('inventory.*')])>Inventory</a>
            @if (auth()->user()->allows('stock'))
                <a href="{{ route('stock.index') }}" @class(['active' => request()->routeIs('stock.*')])>Adjust &amp; count</a>
            @endif
            @if (auth()->user()->allows('purchase'))
                <a href="{{ route('purchasing.index') }}" @class(['active' => request()->routeIs('purchasing.*')])>Purchasing</a>
            @endif
            <p class="nav-label">Books</p>
            <a href="{{ route('customers.index') }}" @class(['active' => request()->routeIs('customers.*')])>Customers</a>
            @if (auth()->user()->allows('reports'))
                <a href="{{ route('reports.index') }}" @class(['active' => request()->routeIs('reports.*')])>Reports</a>
            @endif
            <a href="{{ route('shifts.index') }}" @class(['active' => request()->routeIs('shifts.*')])>Shift</a>
            @if (auth()->user()->role === 'admin')
                <a href="{{ route('team.index') }}" @class(['active' => request()->routeIs('team.*')])>Team &amp; audit</a>
            @endif
        </nav>
        <div class="side-foot">
            <p class="who">{{ ucfirst(auth()->user()->role) }}<b>{{ auth()->user()->name }}</b></p>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit">Sign out</button>
            </form>
        </div>
    </aside>
    <main>
        <div class="canvas">
            @if (session('status'))
                <div class="flash">{{ session('status') }}</div>
            @endif
            @yield('content')
        </div>
    </main>
    <script>
        document.querySelectorAll('form').forEach(function (form) {
            var product = form.querySelector('[name="product_id"]');
            var variant = form.querySelector('[name="product_variant_id"]');
            if (!product || !variant) return;
            var field = variant.closest('.field');
            function sync() {
                var matches = 0;
                Array.prototype.forEach.call(variant.options, function (option) {
                    if (!option.dataset.product) {
                        option.hidden = true;
                        option.disabled = true;
                        return;
                    }
                    var show = option.dataset.product === product.value;
                    option.hidden = !show;
                    option.disabled = !show;
                    if (show) matches += 1;
                });
                if (field) field.hidden = matches === 0;
                variant.required = matches > 0;
                var chosen = Array.prototype.find.call(variant.options, function (option) { return option.selected && !option.disabled; });
                if (!chosen) {
                    var first = Array.prototype.find.call(variant.options, function (option) { return !option.disabled; });
                    if (first) first.selected = true;
                }
            }
            product.addEventListener('change', sync);
            sync();
        });
    </script>
</body>
</html>
