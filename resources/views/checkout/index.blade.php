@extends('layouts.app')

@section('title', 'Counter · Vancha')

@section('content')
    <div class="top">
        <div>
            <p class="eyebrow">Sell</p>
            <h1>Counter</h1>
            <p class="sub">Tap an item to put it on the sale. Change the quantity if you need to, then take payment.</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="error">{{ $errors->first() }}</div>
    @endif

    <div class="counter">
        <section>
            <div class="shelf-tools search" style="margin-bottom: 12px;">
                <input id="q" type="search" placeholder="Find an item, or scan a barcode" autocomplete="off" autofocus>
            </div>
            <div class="shelf" id="shelf"></div>
            @if (count($catalogue) === 0)
                <div class="panel" style="padding: 16px;">The shelf is empty. <a href="{{ route('inventory.create') }}">Add an item</a>, then come back and tap it.</div>
            @endif
            @if ($held->isNotEmpty())
                <div class="panel" style="margin-top: 12px; padding: 12px 14px;">
                    <strong>On hold</strong>
                    @foreach ($held as $sale)
                        <div><a href="{{ route('checkout.index', ['hold' => $sale->id]) }}">{{ $sale->receipt_no }}</a> · {{ $sale->customer->name ?? 'Walk-in' }}</div>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="panel" style="padding: 16px;">
            <form method="POST" action="{{ route('checkout.store') }}" id="sale" autocomplete="off">
                @csrf
                <input type="hidden" name="action" id="action" value="complete">
                <input type="hidden" name="held_id" value="{{ $heldId }}">
                <input type="hidden" name="quote_id" value="{{ $quoteId }}">
                <div id="lines"></div>
                <p class="sub" id="empty">Nothing selected yet.</p>
                <div class="field" style="margin-top: 12px;">
                    <label for="discount">Discount (KES)</label>
                    <input id="discount" name="discount" type="number" min="0" step="0.01" value="0">
                    <p class="hint">Up to {{ rtrim(rtrim(number_format($maxDiscount, 1), '0'), '.') }}% off the shelf price.</p>
                </div>
                <div class="totals ticket-sum">
                    <div><span>Shelf</span><span id="shelf-total">KES 0.00</span></div>
                    <div><span>Discount</span><span id="off-total">KES 0.00</span></div>
                    <div class="sold"><span>Sold amount</span><strong id="total">KES 0.00</strong></div>
                </div>
                <div class="field" style="margin-top: 12px;">
                    <label for="pay_method">Payment</label>
                    <select id="pay_method" name="payments[0][method]">
                        <option value="cash">Cash</option>
                        <option value="mpesa" @disabled(! $mpesaReady)>M-Pesa</option>
                        <option value="card">Card</option>
                        <option value="bank">Bank transfer</option>
                        <option value="cheque">Cheque</option>
                        <option value="credit">Credit account</option>
                    </select>
                </div>
                <div class="field">
                    <label for="tendered">Cash received (KES)</label>
                    <input id="tendered" name="payments[0][tendered]" type="number" min="0" step="0.01" value="0" autocomplete="off">
                    <input type="hidden" name="payments[0][amount]" id="pay_amount" value="0">
                </div>
                <p class="sub" id="change">Change KES 0.00</p>
                <button class="btn" type="submit" style="width: 100%; margin-top: 8px;">Take payment</button>
                <details class="more">
                    <summary>Customer, price, delivery</summary>
                    <div class="field" style="margin-top: 12px;">
                        <label for="customer_id">Customer</label>
                        <select id="customer_id" name="customer_id">
                            <option value="">Walk-in</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}" data-level="{{ $customer->price_level }}" @selected($customerId == $customer->id)>{{ $customer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label for="price_level">Price level</label>
                        <select id="price_level" name="price_level">
                            @foreach (['retail' => 'Retail', 'wholesale' => 'Wholesale', 'contractor' => 'Contractor'] as $value => $label)
                                <option value="{{ $value }}" @selected($priceLevel === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label for="delivery_address">Delivery address</label>
                        <input id="delivery_address" name="delivery_address" placeholder="Leave blank if they carry it">
                    </div>
                    <div class="field">
                        <label for="reference">Payment reference</label>
                        <input id="reference" name="payments[0][reference]" placeholder="Cheque, bank, or terminal ref">
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <button class="btn secondary" type="submit" data-action="hold" style="flex: 1;">Hold</button>
                        <button class="btn secondary" type="submit" data-action="quote" style="flex: 1;">Save quote</button>
                    </div>
                </details>
            </form>
            @if ($receipt)
                <article class="receipt">
                    <strong>{{ $receipt['number'] }}</strong>
                    <p class="sub">Sold amount KES {{ $receipt['total'] }} · VAT included KES {{ $receipt['tax'] }}</p>
                    @foreach ($receipt['lines'] as $line)
                        <div class="totals"><div><span>{{ $line['quantity'] }} {{ $line['unit'] }} {{ $line['description'] }}</span><span>KES {{ $line['total'] }}</span></div></div>
                    @endforeach
                    <div class="totals">
                        <div><span>Received</span><span>KES {{ $receipt['tendered'] }}</span></div>
                        <div><span>Change</span><span>KES {{ $receipt['change'] }}</span></div>
                    </div>
                    <a href="{{ route('sales.receipt', $receipt['id']) }}">Print receipt</a>
                </article>
            @endif
        </section>
    </div>
    <script>
        const catalogue = @json($catalogue);
        const preload = @json($preload);
        const maxPercent = @json($maxDiscount);
        const q = document.getElementById('q');
        const shelf = document.getElementById('shelf');
        const lines = document.getElementById('lines');
        const empty = document.getElementById('empty');
        const shelfTotal = document.getElementById('shelf-total');
        const offTotal = document.getElementById('off-total');
        const totalEl = document.getElementById('total');
        const tendered = document.getElementById('tendered');
        const changeEl = document.getElementById('change');
        const level = document.getElementById('price_level');
        const discount = document.getElementById('discount');
        const payAmount = document.getElementById('pay_amount');
        const action = document.getElementById('action');
        const cart = [];
        let focusKey = null;
        const money = (n) => 'KES ' + Number(n || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const qtyText = (n) => String(n).replace(/\.0+$/, '').replace(/(\.\d*?)0+$/, '$1');
        const esc = (value) => String(value ?? '').replace(/[&<>"']/g, (ch) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch]));
        const keyOf = (row) => row.product_id + ':' + (row.variant_id || '');
        const allRows = Object.values(catalogue).flat();

        const priceOf = (row) => {
            const price = Number(row.prices[level.value] || row.prices.retail || 0);
            return row.sold_unit === 'bulk' ? price * Number(row.units_per_bulk || 1) : price;
        };

        const paintShelf = () => {
            const term = q.value.trim().toLowerCase();
            shelf.innerHTML = '';
            Object.keys(catalogue).forEach((name) => {
                const rows = catalogue[name].filter((row) => {
                    if (!term) return true;
                    const hay = [row.name, row.variant, row.sku, row.barcode, row.unit].join(' ').toLowerCase();
                    return hay.includes(term);
                });
                if (!rows.length) return;
                const label = document.createElement('p');
                label.className = 'shelf-label';
                label.textContent = name;
                shelf.appendChild(label);
                rows.forEach((row) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'tile';
                    const size = row.variant ? esc(row.variant) + ' · ' : '';
                    button.innerHTML = `<b>${esc(row.name)}</b><span class="sub">${size}${qtyText(row.stock)} ${esc(row.unit)}</span><span class="price">${money(row.prices[level.value] || row.prices.retail)}</span>`;
                    button.addEventListener('click', () => add(row));
                    shelf.appendChild(button);
                });
            });
        };

        const stepOf = (row) => row.fractional ? 0.001 : 1;

        const tally = () => {
            let shelfSum = 0;
            cart.forEach((row, i) => {
                const list = priceOf(row) * (Number(row.quantity) || 0);
                shelfSum += list;
                const amount = lines.children[i]?.querySelector('.line-amt');
                if (amount) amount.textContent = money(list);
            });
            const cap = shelfSum * (Number(maxPercent) / 100);
            let saleOff = Math.max(0, parseFloat(discount.value) || 0);
            if (saleOff > cap + 0.001) {
                saleOff = Math.max(0, cap);
                discount.value = saleOff ? saleOff.toFixed(2) : '0';
            }
            const sold = Math.max(0, shelfSum - saleOff);
            empty.hidden = cart.length > 0;
            shelfTotal.textContent = money(shelfSum);
            offTotal.textContent = money(saleOff);
            totalEl.textContent = money(sold);
            payAmount.value = sold.toFixed(2);
            const cash = parseFloat(tendered.value) || 0;
            if (cash > 0 && cash + 0.001 < sold) {
                changeEl.textContent = 'Short ' + money(sold - cash);
            } else {
                changeEl.textContent = 'Change ' + money(Math.max(0, cash - sold));
            }
        };

        const render = () => {
            lines.innerHTML = '';
            cart.forEach((row, i) => {
                const node = document.createElement('div');
                node.className = 'sale-line';
                const bulk = row.bulk_unit
                    ? `<select name="lines[${i}][unit]"><option value="base" ${row.sold_unit === 'bulk' ? '' : 'selected'}>${esc(row.unit || 'Piece')}</option><option value="bulk" ${row.sold_unit === 'bulk' ? 'selected' : ''}>${esc(row.bulk_unit)}</option></select>`
                    : `<input type="hidden" name="lines[${i}][unit]" value="base"><span class="unit">${esc(row.unit || '')}</span>`;
                node.innerHTML = `<div class="toprow"><div class="who"><strong>${esc(row.name)}</strong><span class="sub">${esc(row.variant || '')}</span></div><button class="drop" type="button" aria-label="Remove">×</button></div>
                    <input type="hidden" name="lines[${i}][product_id]" value="${row.product_id}">
                    <input type="hidden" name="lines[${i}][variant_id]" value="${row.variant_id || ''}">
                    <input type="hidden" name="lines[${i}][discount]" value="0">
                    ${row.track ? `<input name="lines[${i}][serial]" placeholder="Serial or batch" style="margin-top:6px" autocomplete="off">` : ''}
                    <div class="stepper" style="margin-top:8px">
                        <button type="button" data-minus aria-label="Less">−</button>
                        <input class="qty" name="lines[${i}][quantity]" type="number" min="0" step="${stepOf(row)}" value="${row.quantity}" aria-label="Quantity" autocomplete="off">
                        <button type="button" data-plus aria-label="More">+</button>
                        ${bulk}
                        <strong class="line-amt">${money(priceOf(row) * row.quantity)}</strong>
                    </div>`;
                const qty = node.querySelector('.qty');
                const setQty = (next, write) => {
                    row.quantity = Math.max(0, Math.round(next * 1000) / 1000);
                    if (write) qty.value = row.quantity;
                    tally();
                };
                qty.addEventListener('input', () => setQty(parseFloat(qty.value) || 0, false));
                node.querySelector('[data-minus]').addEventListener('click', () => setQty((Number(row.quantity) || 0) - stepOf(row), true));
                node.querySelector('[data-plus]').addEventListener('click', () => setQty((Number(row.quantity) || 0) + stepOf(row), true));
                const unit = node.querySelector('select');
                if (unit) unit.addEventListener('change', (event) => { row.sold_unit = event.target.value; tally(); });
                node.querySelector('.drop').addEventListener('click', () => { cart.splice(i, 1); render(); });
                lines.appendChild(node);
            });
            if (focusKey) {
                const match = cart.findIndex((row) => keyOf(row) === focusKey);
                const input = lines.children[match]?.querySelector('.qty');
                if (input) {
                    input.focus();
                    input.select();
                }
                focusKey = null;
            }
            tally();
        };

        const add = (row) => {
            const existing = cart.find((item) => keyOf(item) === keyOf(row));
            if (existing) existing.quantity = (Number(existing.quantity) || 0) + 1;
            else cart.push({ ...row, quantity: row.quantity > 0 ? row.quantity : 1, sold_unit: row.sold_unit || 'base', discount: 0 });
            focusKey = keyOf(existing || row);
            render();
        };

        document.getElementById('sale').addEventListener('submit', (event) => {
            const ready = cart.some((row) => Number(row.quantity) > 0 && Number(row.product_id) > 0);
            if (!ready) {
                event.preventDefault();
                empty.hidden = false;
                empty.textContent = 'Tap an item on the shelf first.';
                return;
            }
            cart.forEach((row, i) => {
                if (Number(row.quantity) > 0 && Number(row.product_id) > 0) return;
                lines.children[i]?.querySelectorAll('input, select').forEach((el) => { el.disabled = true; });
            });
        });

        q.addEventListener('input', paintShelf);
        q.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter') return;
            event.preventDefault();
            const term = q.value.trim().toLowerCase();
            if (!term) return;
            const exact = allRows.filter((row) => row.sku.toLowerCase() === term || String(row.barcode || '').toLowerCase() === term);
            const named = allRows.filter((row) => [row.name, row.variant, row.sku].join(' ').toLowerCase().includes(term));
            const pick = exact.length === 1 ? exact[0] : (named.length === 1 ? named[0] : null);
            if (pick) {
                add(pick);
                q.value = '';
                paintShelf();
            }
        });
        document.getElementById('customer_id').addEventListener('change', (event) => {
            const levelName = event.target.selectedOptions[0]?.dataset.level;
            if (levelName) level.value = levelName;
            paintShelf();
            render();
        });
        document.querySelectorAll('[data-action]').forEach((button) => button.addEventListener('click', () => { action.value = button.dataset.action; }));
        level.addEventListener('change', () => { paintShelf(); render(); });
        discount.addEventListener('input', render);
        tendered.addEventListener('input', render);
        preload.forEach(add);
        paintShelf();
        render();
    </script>
@endsection
