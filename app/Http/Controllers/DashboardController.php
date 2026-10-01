<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $lowStock = Product::query()
            ->withSum('stocks', 'quantity')
            ->get()
            ->filter(fn (Product $product) => (float) $product->stocks_sum_quantity <= (float) $product->reorder_level)
            ->count();

        return view('dashboard', [
            'itemCount' => Product::count(),
            'stockValue' => DB::table('stocks')
                ->join('products', 'products.id', '=', 'stocks.product_id')
                ->rawValue('coalesce(sum(stocks.quantity * products.buying_price), 0)'),
            'todaySales' => Sale::whereDate('created_at', today())->sum('total'),
            'lowStock' => $lowStock,
            'recent' => Product::with('category')->latest()->limit(5)->get(),
        ]);
    }
}
