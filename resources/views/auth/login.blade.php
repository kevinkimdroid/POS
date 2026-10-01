<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in · Vancha</title>
    <link rel="icon" href="{{ asset('brand/vancha-mark.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=fraunces:500,600,700|outfit:300,400,500,600" rel="stylesheet">
    <style>
        :root {
            --ink: #10281f;
            --pine: #14382c;
            --deep: #071611;
            --gold: #e4b86a;
            --gold-deep: #a67c32;
            --cream: #f3eee4;
            --paper: #fffdf8;
            --line: #e3dacb;
            --muted: #6d655b;
            --danger: #8d3a2c;
            --danger-bg: #f8ece8;
        }

        * { box-sizing: border-box; }

        html, body { margin: 0; min-height: 100%; }

        body {
            font-family: Outfit, "Segoe UI", sans-serif;
            color: var(--ink);
            background: var(--cream);
            overflow-x: hidden;
        }

        .stage {
            min-height: 100vh;
            display: grid;
            grid-template-columns: minmax(420px, 1.15fr) minmax(380px, 0.85fr);
        }

        .brand {
            position: relative;
            overflow: hidden;
            color: #f6f1e7;
            padding: 36px 48px 40px;
            display: flex;
            flex-direction: column;
            background:
                radial-gradient(520px 320px at 18% 0%, rgba(228, 184, 106, 0.28), transparent 60%),
                radial-gradient(640px 420px at 100% 80%, rgba(46, 122, 90, 0.35), transparent 55%),
                linear-gradient(160deg, #1c5340 0%, #0c241c 46%, var(--deep) 100%);
        }

        .brand::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image: radial-gradient(rgba(246, 241, 231, 0.09) 1px, transparent 1px);
            background-size: 18px 18px;
            mask-image: linear-gradient(180deg, rgba(0, 0, 0, 0.85), transparent 78%);
            pointer-events: none;
        }

        .glow {
            position: absolute;
            width: 420px;
            height: 420px;
            right: -120px;
            bottom: -80px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(228, 184, 106, 0.2), transparent 68%);
            filter: blur(8px);
            pointer-events: none;
        }

        .rings {
            position: absolute;
            width: 280px;
            height: 280px;
            right: 36px;
            top: 92px;
            border-radius: 50%;
            border: 1px solid rgba(228, 184, 106, 0.22);
            animation: spin 48s linear infinite;
            pointer-events: none;
        }

        .rings::before,
        .rings::after {
            content: "";
            position: absolute;
            inset: 36px;
            border-radius: 50%;
            border: 1px solid rgba(246, 241, 231, 0.12);
        }

        .rings::after { inset: 78px; border-color: rgba(228, 184, 106, 0.18); }

        @keyframes spin { to { transform: rotate(360deg); } }

        .brand-top, .cluster { position: relative; z-index: 1; }

        .brand-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .lockup { display: flex; align-items: center; gap: 14px; }

        .mark {
            width: 52px;
            height: 52px;
            flex: none;
            filter: drop-shadow(0 10px 16px rgba(0, 0, 0, 0.28));
        }

        .word {
            font-family: Fraunces, Georgia, serif;
            font-weight: 600;
            font-size: 30px;
            letter-spacing: -0.03em;
            line-height: 1;
        }

        .word span {
            display: block;
            margin-top: 5px;
            font-family: Outfit, sans-serif;
            font-size: 11px;
            font-weight: 500;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            color: var(--gold);
        }

        .status {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(246, 241, 231, 0.08);
            border: 1px solid rgba(246, 241, 231, 0.14);
            font-size: 13px;
            letter-spacing: 0.02em;
        }

        .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #8ed7a8;
            box-shadow: 0 0 0 0 rgba(142, 215, 168, 0.7);
            animation: pulse 2.4s ease-out infinite;
        }

        @keyframes pulse {
            70% { box-shadow: 0 0 0 8px rgba(142, 215, 168, 0); }
            100% { box-shadow: 0 0 0 0 rgba(142, 215, 168, 0); }
        }

        .cluster {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 22px;
            max-width: 680px;
            padding: 28px 0 8px;
        }

        .story { max-width: 520px; }

        .story h1 {
            margin: 0 0 12px;
            font-family: Fraunces, Georgia, serif;
            font-weight: 500;
            font-size: clamp(42px, 4.2vw, 60px);
            line-height: 0.96;
            letter-spacing: -0.04em;
        }

        .story p {
            margin: 0;
            max-width: 38ch;
            font-weight: 300;
            font-size: 17px;
            line-height: 1.55;
            color: rgba(246, 241, 231, 0.76);
        }

        .ticket {
            padding: 16px 18px 0;
            border-radius: 20px;
            background: linear-gradient(180deg, rgba(255, 252, 246, 0.12), rgba(255, 252, 246, 0.05));
            border: 1px solid rgba(228, 184, 106, 0.32);
            box-shadow: 0 18px 40px rgba(0, 0, 0, 0.22);
            backdrop-filter: blur(10px);
        }

        .ticket-head {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 12px;
            font-size: 12px;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--gold);
        }

        .ticket-head b { font-weight: 500; color: #f6f1e7; letter-spacing: 0; text-transform: none; font-size: 13px; }

        .rule {
            height: 1px;
            margin: 10px 0;
            background: repeating-linear-gradient(90deg, rgba(246, 241, 231, 0.35) 0 6px, transparent 6px 10px);
        }

        .line {
            display: grid;
            grid-template-columns: 1fr 28px 48px;
            align-items: baseline;
            padding: 7px 0;
            font-size: 14px;
            color: rgba(246, 241, 231, 0.9);
        }

        .qty { color: rgba(246, 241, 231, 0.45); text-align: right; }
        .amt { text-align: right; font-variant-numeric: tabular-nums; }

        .total-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin: 8px -18px 0;
            padding: 12px 18px 14px;
            border-radius: 0 0 19px 19px;
            background: rgba(228, 184, 106, 0.12);
            border-top: 1px solid rgba(228, 184, 106, 0.28);
        }

        .total-bar span {
            display: block;
            font-size: 11px;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: rgba(246, 241, 231, 0.62);
        }

        .total-bar strong {
            font-family: Fraunces, Georgia, serif;
            font-size: 28px;
            font-weight: 600;
            letter-spacing: -0.03em;
            color: var(--gold);
            font-variant-numeric: tabular-nums;
        }

        .paid {
            padding: 5px 10px;
            border-radius: 999px;
            background: rgba(142, 215, 168, 0.16);
            color: #c8efd4;
            font-size: 12px;
            letter-spacing: 0.04em;
        }

        .notes {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }

        .note {
            padding: 12px 14px;
            border-radius: 14px;
            background: rgba(246, 241, 231, 0.06);
            border: 1px solid rgba(246, 241, 231, 0.1);
        }

        .note strong { display: block; margin-bottom: 3px; font-weight: 500; font-size: 14px; }
        .note span { font-size: 13px; color: rgba(246, 241, 231, 0.62); }

        .panel {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 36px 28px;
            overflow: hidden;
            background:
                radial-gradient(480px 280px at 80% 0%, rgba(228, 184, 106, 0.2), transparent 70%),
                var(--cream);
        }

        .panel::before {
            content: "";
            position: absolute;
            width: 220px;
            height: 220px;
            left: -70px;
            bottom: -40px;
            border-radius: 50%;
            border: 1px solid rgba(166, 124, 50, 0.18);
        }

        .card {
            position: relative;
            width: min(100%, 420px);
            padding: 36px 32px 28px;
            border-radius: 28px;
            background: var(--paper);
            border: 1px solid rgba(255, 255, 255, 0.8);
            box-shadow:
                0 1px 0 rgba(255, 255, 255, 0.8) inset,
                0 24px 50px rgba(16, 40, 31, 0.08);
            animation: rise 0.55s ease both;
        }

        @keyframes rise {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: none; }
        }

        .mobile-lockup { display: none; }

        .eyebrow {
            margin: 0 0 8px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--gold-deep);
        }

        .card h2 {
            margin: 0;
            font-family: Fraunces, Georgia, serif;
            font-size: 42px;
            font-weight: 560;
            letter-spacing: -0.03em;
        }

        .lede { margin: 8px 0 26px; color: var(--muted); font-size: 15.5px; line-height: 1.5; }

        .alert {
            margin-bottom: 16px;
            padding: 12px 14px;
            border-radius: 12px;
            background: var(--danger-bg);
            color: var(--danger);
            font-size: 14px;
        }

        label { display: block; margin-bottom: 8px; font-size: 13px; font-weight: 600; }

        .field { margin-bottom: 14px; }

        .control { position: relative; }

        .control svg {
            position: absolute;
            left: 14px;
            top: 16px;
            width: 18px;
            height: 18px;
            color: #8a8174;
            pointer-events: none;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            height: 52px;
            padding: 0 16px 0 42px;
            border: 1px solid var(--line);
            border-radius: 14px;
            background: #fbf7f1;
            color: var(--ink);
            font: inherit;
            font-size: 16px;
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
        }

        input[type="password"] { padding-right: 72px; }

        input:focus {
            background: #fff;
            border-color: #c9a15a;
            box-shadow: 0 0 0 4px rgba(224, 177, 90, 0.22);
        }

        input:-webkit-autofill,
        input:-webkit-autofill:hover,
        input:-webkit-autofill:focus {
            -webkit-text-fill-color: var(--ink);
            caret-color: var(--ink);
            box-shadow: 0 0 0 1000px #fbf7f1 inset;
            transition: background-color 9999s ease-out;
        }

        .toggle {
            position: absolute;
            right: 8px;
            top: 8px;
            height: 36px;
            padding: 0 12px;
            border: 0;
            border-radius: 10px;
            background: transparent;
            color: var(--muted);
            font: inherit;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }

        .toggle:hover { color: var(--ink); }

        .row {
            display: flex;
            align-items: center;
            margin: 4px 0 20px;
            font-size: 14px;
        }

        .check {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--muted);
            cursor: pointer;
        }

        .check input { width: 16px; height: 16px; accent-color: var(--pine); }

        button[type="submit"] {
            width: 100%;
            height: 54px;
            border: 0;
            border-radius: 14px;
            background: linear-gradient(180deg, #1e5340, #12362b);
            color: #f6f1e7;
            font: inherit;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 14px 24px rgba(18, 54, 43, 0.22);
            transition: transform 0.15s, filter 0.15s;
        }

        button[type="submit"]:hover { filter: brightness(1.08); transform: translateY(-1px); }
        button[type="submit"]:active { transform: translateY(0); }

        .fine {
            display: flex;
            justify-content: space-between;
            margin: 22px 0 0;
            color: #9a9186;
            font-size: 12px;
            letter-spacing: 0.04em;
        }

        @media (max-width: 980px) {
            .stage { display: block; width: 100%; min-width: 0; }
            .brand { display: none; }
            .mobile-lockup {
                display: flex;
                align-items: center;
                gap: 12px;
                margin-bottom: 22px;
                color: var(--ink);
            }
            .mobile-lockup .mark { width: 46px; height: 46px; }
            .mobile-lockup .word { font-size: 26px; }
            .panel {
                display: block;
                min-height: 100vh;
                width: 100%;
                max-width: 100%;
                min-width: 0;
                padding: 24px 16px;
            }
            .card {
                width: auto;
                max-width: 420px;
                min-width: 0;
                margin: 0 auto;
                padding: 28px 20px 22px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .rings, .dot, .card { animation: none; }
        }
    </style>
</head>
<body>
    <main class="stage">
        <section class="brand">
            <div class="rings" aria-hidden="true"></div>
            <div class="glow" aria-hidden="true"></div>

            <div class="brand-top">
                <div class="lockup">
                    <img class="mark" src="{{ asset('brand/vancha-mark.svg') }}" alt="Vancha logo">
                    <div class="word">Vancha<span>Point of sale</span></div>
                </div>
                <div class="status">
                    <span class="dot" aria-hidden="true"></span>
                    <span id="clock">Register ready</span>
                </div>
            </div>

            <div class="cluster">
                <div class="story">
                    <h1>The till, kept calm.</h1>
                    <p>Open the counter, take the payment, and close the day with the numbers already in order.</p>
                </div>

                <article class="ticket" aria-label="Sample sale">
                    <div class="ticket-head"><span>Sample sale</span><b>Counter 1</b></div>
                    <div class="rule"></div>
                    <div class="line"><span>Americano</span><span class="qty">1</span><span class="amt">280</span></div>
                    <div class="line"><span>Butter croissant</span><span class="qty">1</span><span class="amt">220</span></div>
                    <div class="line"><span>Espresso</span><span class="qty">2</span><span class="amt">360</span></div>
                    <div class="total-bar">
                        <div><span>Total</span><strong>860</strong></div>
                        <div class="paid">Card · paid</div>
                    </div>
                </article>

                <div class="notes">
                    <div class="note"><strong>Fast checkout</strong><span>Fewer taps to get paid.</span></div>
                    <div class="note"><strong>Clear totals</strong><span>Cash and card on one ticket.</span></div>
                    <div class="note"><strong>Quiet close</strong><span>A number you can trust.</span></div>
                </div>
            </div>
        </section>

        <section class="panel">
            <div class="card">
                <div class="mobile-lockup">
                    <img class="mark" src="{{ asset('brand/vancha-mark.svg') }}" alt="">
                    <div class="word">Vancha<span>Point of sale</span></div>
                </div>

                <p class="eyebrow">Welcome back</p>
                <h2>Sign in</h2>
                <p class="lede">Username and password open the register.</p>

                @if ($errors->any())
                    <div class="alert" role="alert">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('login') }}" novalidate>
                    @csrf

                    <div class="field">
                        <label for="username">Username</label>
                        <div class="control">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <circle cx="12" cy="8" r="3.2"/>
                                <path d="M5.5 19.2c1.4-3 3.7-4.4 6.5-4.4s5.1 1.4 6.5 4.4" stroke-linecap="round"/>
                            </svg>
                            <input id="username" name="username" type="text" value="{{ old('username') }}" autocomplete="username" required autofocus>
                        </div>
                    </div>

                    <div class="field">
                        <label for="password">Password</label>
                        <div class="control">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <rect x="5" y="11" width="14" height="9" rx="2"/>
                                <path d="M8 11V8.5a4 4 0 0 1 8 0V11" stroke-linecap="round"/>
                            </svg>
                            <input id="password" name="password" type="password" autocomplete="current-password" required>
                            <button class="toggle" type="button" id="toggle-password" aria-controls="password">Show</button>
                        </div>
                    </div>

                    <div class="row">
                        <label class="check">
                            <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                            Remember this register
                        </label>
                    </div>

                    <button type="submit">Sign in to Vancha</button>
                </form>

                <p class="fine"><span>Vancha</span><span>Point of sale</span></p>
            </div>
        </section>
    </main>
    <script>
        const toggle = document.getElementById('toggle-password');
        const password = document.getElementById('password');
        toggle.addEventListener('click', () => {
            const visible = password.type === 'text';
            password.type = visible ? 'password' : 'text';
            toggle.textContent = visible ? 'Show' : 'Hide';
        });

        const clock = document.getElementById('clock');
        const renderClock = () => {
            clock.textContent = new Date().toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
        };
        renderClock();
        setInterval(renderClock, 30000);
    </script>
</body>
</html>
