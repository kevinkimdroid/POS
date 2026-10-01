<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class Shop
{
    public static function audit(string $action, string $description, array $meta = []): void
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'description' => $description,
            'meta' => $meta ?: null,
        ]);
    }

    public static function moveStock(int $productId, ?int $variantId, int $branchId, float $delta, string $reason, ?string $note = null): Stock
    {
        $stock = Stock::query()->firstOrCreate(
            [
                'product_id' => $productId,
                'product_variant_id' => $variantId,
                'branch_id' => $branchId,
            ],
            ['quantity' => 0],
        );

        $next = round((float) $stock->quantity + $delta, 3);
        if ($next < -0.0001) {
            $name = Product::find($productId)?->name ?? 'Item';
            throw ValidationException::withMessages([
                'quantity' => 'Not enough '.$name.' at this branch.',
            ]);
        }

        $stock->quantity = $next;
        $stock->save();

        StockMovement::create([
            'product_id' => $productId,
            'product_variant_id' => $variantId,
            'branch_id' => $branchId,
            'user_id' => Auth::id(),
            'quantity' => $delta,
            'reason' => $reason,
            'note' => $note,
        ]);

        if (in_array($reason, ['adjustment', 'count', 'void', 'refund'], true)) {
            self::audit($reason, $note ?: ucfirst($reason).' of '.$delta, [
                'product_id' => $productId,
                'branch_id' => $branchId,
                'quantity' => $delta,
            ]);
        }

        return $stock;
    }

    public static function variantForProduct(int $productId, mixed $variantId): ?int
    {
        $product = Product::with('variants')->findOrFail($productId);
        $variantId = $variantId ? (int) $variantId : null;

        if ($product->variants->isEmpty()) {
            return null;
        }

        if (! $variantId || ! $product->variants->contains('id', $variantId)) {
            throw ValidationException::withMessages([
                'product_variant_id' => 'Choose a size, gauge, colour, or finish for '.$product->name.'.',
            ]);
        }

        return $variantId;
    }

    public static function mpesaReady(): bool
    {
        return filled(config('services.mpesa.key'))
            && filled(config('services.mpesa.secret'))
            && filled(config('services.mpesa.shortcode'))
            && filled(config('services.mpesa.passkey'));
    }

    public static function etimsReady(): bool
    {
        return filled(config('services.etims.pin'))
            && filled(config('services.etims.key'));
    }
}
