<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vancha</title>
    <link rel="icon" href="{{ asset('brand/vancha-mark.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=fraunces:500,600|outfit:400,500,600" rel="stylesheet">
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            font-family: Outfit, "Segoe UI", sans-serif;
            color: #10281f;
            background: #f4efe6;
        }
        .wrap { text-align: center; }
        img { width: 72px; height: 72px; }
        h1 {
            margin: 16px 0 8px;
            font-family: Fraunces, Georgia, serif;
            font-weight: 560;
            font-size: 40px;
            letter-spacing: -0.03em;
        }
        p { margin: 0 0 24px; color: #6f675c; }
        button {
            height: 44px;
            padding: 0 18px;
            border: 0;
            border-radius: 12px;
            background: #12362b;
            color: #f6f1e7;
            font: inherit;
            font-weight: 600;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="wrap">
        <img src="{{ asset('brand/vancha-mark.svg') }}" alt="Vancha logo">
        <h1>Vancha</h1>
        <p>Signed in as {{ auth()->user()->name }}.</p>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Sign out</button>
        </form>
    </div>
</body>
</html>
