@extends('layouts.app')

@section('title', 'Add item · Vancha')

@section('content')
    <div class="top">
        <div>
            <p class="eyebrow">Shelf</p>
            <h1>Add item</h1>
            <p class="sub">Category fills in the usual tax and unit. Each size can keep its own price and stock.</p>
        </div>
        <a class="btn secondary" href="{{ route('inventory.index') }}">Back to inventory</a>
    </div>

    @if ($errors->any())
        <div class="error">{{ $errors->first() }}</div>
    @endif

    <form class="panel" style="padding: 22px;" method="POST" action="{{ route('inventory.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="form-grid">
            <div class="field span-2">
                <label for="name">Item name</label>
                <input id="name" name="name" value="{{ old('name') }}" required placeholder="PVC Pipe Class B">
            </div>
            <div class="field">
                <label for="category_id">Category</label>
                <select id="category_id" name="category_id" required>
                    <option value="">Choose</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" data-tax="{{ $category->default_tax_type }}" data-unit="{{ $category->default_unit }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="subcategory_id">Subcategory</label>
                <select id="subcategory_id" name="subcategory_id">
                    <option value="">None</option>
                    @foreach ($subcategories as $subcategory)
                        <option value="{{ $subcategory->id }}" data-parent="{{ $subcategory->parent_id }}" @selected(old('subcategory_id') == $subcategory->id)>{{ $subcategory->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="brand">Brand</label>
                <input id="brand" name="brand" value="{{ old('brand') }}" placeholder="Kenpipe">
            </div>
            <div class="field">
                <label for="supplier_id">Supplier</label>
                <select id="supplier_id" name="supplier_id">
                    <option value="">None</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="sku">SKU</label>
                <input id="sku" name="sku" value="{{ old('sku') }}" placeholder="Leave blank to generate">
            </div>
            <div class="field">
                <label for="barcode">Barcode</label>
                <input id="barcode" name="barcode" value="{{ old('barcode') }}" placeholder="Scan or type">
            </div>
            <div class="field">
                <label for="base_unit">Base unit</label>
                <input id="base_unit" name="base_unit" list="units" value="{{ old('base_unit', 'Piece') }}" required>
                <datalist id="units">
                    @foreach ($units as $unit)
                        <option value="{{ $unit }}"></option>
                    @endforeach
                </datalist>
            </div>
            <div class="field">
                <label for="tax_type">Tax type</label>
                <select id="tax_type" name="tax_type">
                    <option value="vat_16" @selected(old('tax_type', 'vat_16') === 'vat_16')>VAT 16%</option>
                    <option value="zero" @selected(old('tax_type') === 'zero')>Zero-rated</option>
                    <option value="exempt" @selected(old('tax_type') === 'exempt')>Exempt</option>
                </select>
            </div>
            <div class="field">
                <label for="bulk_unit">Bulk unit</label>
                <input id="bulk_unit" name="bulk_unit" value="{{ old('bulk_unit') }}" placeholder="Bundle, carton, roll">
            </div>
            <div class="field">
                <label for="units_per_bulk">Base units in one bulk</label>
                <input id="units_per_bulk" name="units_per_bulk" type="number" min="0" step="0.001" value="{{ old('units_per_bulk') }}" placeholder="10">
            </div>
            <div class="field">
                <label for="buying_price">Buying price (KES)</label>
                <input id="buying_price" name="buying_price" type="number" min="0" step="0.01" value="{{ old('buying_price') }}" required>
            </div>
            <div class="field">
                <label for="retail_price">Retail price (KES)</label>
                <input id="retail_price" name="retail_price" type="number" min="0" step="0.01" value="{{ old('retail_price') }}" required>
                <p class="margin" id="margin">Margin —</p>
            </div>
            <div class="field">
                <label for="wholesale_price">Wholesale price (KES)</label>
                <input id="wholesale_price" name="wholesale_price" type="number" min="0" step="0.01" value="{{ old('wholesale_price') }}" required>
            </div>
            <div class="field">
                <label for="contractor_price">Contractor price (KES)</label>
                <input id="contractor_price" name="contractor_price" type="number" min="0" step="0.01" value="{{ old('contractor_price') }}" required>
            </div>
            <div class="field">
                <label for="break_qty">Quantity price from</label>
                <input id="break_qty" name="break_qty" type="number" min="0" step="0.001" placeholder="50">
                <p class="hint">Retail price changes once the base quantity reaches this.</p>
            </div>
            <div class="field">
                <label for="break_price">Price at that quantity</label>
                <input id="break_price" name="break_price" type="number" min="0" step="0.01">
            </div>
            <div class="field">
                <label for="reorder_level">Reorder level</label>
                <input id="reorder_level" name="reorder_level" type="number" min="0" step="0.001" value="{{ old('reorder_level', 0) }}">
            </div>
            <div class="field">
                <label for="image">Image</label>
                <input id="image" name="image" type="file" accept="image/*">
            </div>
            @foreach ($branches as $branch)
                <div class="field">
                    <label for="stock-{{ $branch->id }}">Opening stock · {{ $branch->name }}</label>
                    <input id="stock-{{ $branch->id }}" name="opening_stock[{{ $branch->id }}]" type="number" min="0" step="0.001" value="{{ old('opening_stock.'.$branch->id, 0) }}">
                    <p class="hint">Used when the item has no sizes. Sizes carry their own stock.</p>
                </div>
            @endforeach
            <div class="field span-2 checks">
                <label><input type="checkbox" name="allow_fractional" value="1" @checked(old('allow_fractional'))> Allow fractional sale</label>
                <label><input type="checkbox" name="track_serial_batch" value="1" @checked(old('track_serial_batch'))> Track serials or batches</label>
            </div>
        </div>

        <div class="span-2" style="margin-top: 8px;">
            <label>Sizes, gauges, colours</label>
            <p class="hint">Leave this empty for a single item. A blank price uses the prices above.</p>
            <div id="variants"></div>
            <button class="btn secondary" type="button" id="add-variant">Add a size</button>
        </div>

        <div style="margin-top: 18px;">
            <button class="btn" type="submit">Save item</button>
        </div>
    </form>

    <template id="variant-row">
        <div class="variant">
            <input name="variants[__i__][name]" placeholder="¾ inch" aria-label="Variant name">
            <input name="variants[__i__][sku]" placeholder="SKU" aria-label="Variant SKU">
            <input name="variants[__i__][barcode]" placeholder="Barcode" aria-label="Variant barcode">
            <input name="variants[__i__][opening_stock]" type="number" min="0" step="0.001" placeholder="Stock" aria-label="Opening stock">
            <button class="icon-btn" type="button" data-remove aria-label="Remove size">×</button>
            <input name="variants[__i__][retail_price]" type="number" min="0" step="0.01" placeholder="Retail" aria-label="Retail price">
            <input name="variants[__i__][wholesale_price]" type="number" min="0" step="0.01" placeholder="Wholesale" aria-label="Wholesale price">
            <input name="variants[__i__][contractor_price]" type="number" min="0" step="0.01" placeholder="Contractor" aria-label="Contractor price">
            <input name="variants[__i__][buying_price]" type="number" min="0" step="0.01" placeholder="Buying" aria-label="Buying price">
        </div>
    </template>
    <script>
        const category = document.getElementById('category_id');
        const subcategory = document.getElementById('subcategory_id');
        const tax = document.getElementById('tax_type');
        const unit = document.getElementById('base_unit');
        const buying = document.getElementById('buying_price');
        const retail = document.getElementById('retail_price');
        const margin = document.getElementById('margin');
        const variants = document.getElementById('variants');
        const template = document.getElementById('variant-row');
        let index = 0;

        const filterSubs = () => {
            const parent = category.value;
            [...subcategory.options].forEach((option, i) => {
                if (i === 0) return;
                const show = option.dataset.parent === parent;
                option.hidden = !show;
                if (!show && option.selected) subcategory.value = '';
            });
        };

        category.addEventListener('change', () => {
            const selected = category.selectedOptions[0];
            if (selected?.dataset.tax) tax.value = selected.dataset.tax;
            if (selected?.dataset.unit) unit.value = selected.dataset.unit;
            filterSubs();
        });

        const showMargin = () => {
            const cost = parseFloat(buying.value);
            const sell = parseFloat(retail.value);
            if (!sell) {
                margin.textContent = 'Margin —';
                return;
            }
            const pct = ((sell - (Number.isFinite(cost) ? cost : 0)) / sell) * 100;
            margin.textContent = 'Retail margin ' + pct.toFixed(1) + '%';
        };
        buying.addEventListener('input', showMargin);
        retail.addEventListener('input', showMargin);

        document.getElementById('add-variant').addEventListener('click', () => {
            const html = template.innerHTML.replaceAll('__i__', String(index++));
            const wrap = document.createElement('div');
            wrap.innerHTML = html;
            const row = wrap.firstElementChild;
            row.querySelector('[data-remove]').addEventListener('click', () => row.remove());
            variants.appendChild(row);
        });

        filterSubs();
        showMargin();
    </script>
@endsection
