<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('admin')->after('password');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->decimal('balance', 12, 2)->default(0);
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('account_type')->default('individual');
            $table->string('price_level')->default('retail');
            $table->decimal('credit_limit', 12, 2)->default(0);
            $table->decimal('balance', 12, 2)->default(0);
            $table->unsignedInteger('loyalty_points')->default(0);
            $table->boolean('loyalty')->default(false);
            $table->timestamps();
        });

        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('branch_id')->constrained();
            $table->decimal('opening_float', 12, 2)->default(0);
            $table->decimal('counted_cash', 12, 2)->nullable();
            $table->decimal('expected_cash', 12, 2)->nullable();
            $table->decimal('variance', 12, 2)->nullable();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('description');
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->constrained();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('quantity', 12, 3);
            $table->string('reason');
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_takes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->string('status')->default('open');
            $table->timestamps();
        });

        Schema::create('stock_take_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_take_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('expected', 12, 3);
            $table->decimal('counted', 12, 3)->nullable();
            $table->timestamps();
        });

        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_branch_id')->constrained('branches');
            $table->foreignId('to_branch_id')->constrained('branches');
            $table->foreignId('user_id')->constrained();
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_transfer_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('quantity', 12, 3);
            $table->timestamps();
        });

        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->constrained();
            $table->string('code');
            $table->date('expires_on')->nullable();
            $table->decimal('quantity', 12, 3)->default(0);
            $table->timestamps();
        });

        Schema::create('serial_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('serial')->unique();
            $table->string('status')->default('in_stock');
            $table->foreignId('sale_line_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('quantity_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('price_level');
            $table->decimal('min_quantity', 12, 3);
            $table->decimal('price', 12, 2);
            $table->timestamps();
        });

        Schema::create('price_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('buying_price', 12, 2);
            $table->decimal('landed_cost', 12, 2)->nullable();
            $table->string('source');
            $table->timestamps();
        });

        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained();
            $table->foreignId('branch_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->string('status')->default('ordered');
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('quantity', 12, 3);
            $table->decimal('received', 12, 3)->default(0);
            $table->decimal('unit_cost', 12, 2);
            $table->timestamps();
        });

        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->decimal('transport_cost', 12, 2)->default(0);
            $table->decimal('handling_cost', 12, 2)->default(0);
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('supplier_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->decimal('amount', 12, 2);
            $table->string('method');
            $table->string('reference')->nullable();
            $table->timestamps();
        });

        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->string('price_level');
            $table->string('status')->default('open');
            $table->date('valid_until')->nullable();
            $table->decimal('total', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('quotation_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description');
            $table->decimal('quantity', 12, 3);
            $table->string('sold_unit')->default('base');
            $table->decimal('unit_price', 12, 2);
            $table->timestamps();
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('branch_id')->constrained()->nullOnDelete();
            $table->foreignId('shift_id')->nullable()->after('customer_id')->constrained()->nullOnDelete();
            $table->foreignId('quotation_id')->nullable()->after('shift_id')->constrained()->nullOnDelete();
            $table->string('status')->default('completed')->after('price_level');
            $table->decimal('discount', 12, 2)->default(0)->after('status');
            $table->string('delivery_address')->nullable();
            $table->string('etims_status')->default('local');
        });

        Schema::table('sale_lines', function (Blueprint $table) {
            $table->decimal('discount', 12, 2)->default(0);
            $table->string('sold_unit')->default('base');
            $table->decimal('base_quantity', 12, 3)->default(0);
            $table->decimal('cost_price', 12, 2)->default(0);
            $table->string('batch_code')->nullable();
            $table->string('serial')->nullable();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->string('reference')->nullable();
            $table->string('status')->default('confirmed');
        });

        Schema::create('delivery_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->string('address');
            $table->string('status')->default('pending');
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamps();
        });

        Schema::create('sale_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->string('method');
            $table->decimal('total', 12, 2);
            $table->timestamps();
        });

        Schema::create('sale_return_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_line_id')->constrained();
            $table->decimal('quantity', 12, 3);
            $table->decimal('amount', 12, 2);
            $table->timestamps();
        });

        Schema::create('customer_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->decimal('amount', 12, 2);
            $table->string('method');
            $table->string('reference')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_payments');
        Schema::dropIfExists('sale_return_lines');
        Schema::dropIfExists('sale_returns');
        Schema::dropIfExists('delivery_notes');
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['reference', 'status']);
        });
        Schema::table('sale_lines', function (Blueprint $table) {
            $table->dropColumn(['discount', 'sold_unit', 'base_quantity', 'cost_price', 'batch_code', 'serial']);
        });
        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('quotation_id');
            $table->dropConstrainedForeignId('shift_id');
            $table->dropConstrainedForeignId('customer_id');
            $table->dropColumn(['status', 'discount', 'delivery_address', 'etims_status']);
        });
        Schema::dropIfExists('quotation_lines');
        Schema::dropIfExists('quotations');
        Schema::dropIfExists('supplier_payments');
        Schema::dropIfExists('goods_receipts');
        Schema::dropIfExists('purchase_order_lines');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('price_histories');
        Schema::dropIfExists('quantity_prices');
        Schema::dropIfExists('serial_numbers');
        Schema::dropIfExists('batches');
        Schema::dropIfExists('stock_transfer_lines');
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('stock_take_lines');
        Schema::dropIfExists('stock_takes');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('shifts');
        Schema::dropIfExists('customers');
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn(['email', 'address', 'balance']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
