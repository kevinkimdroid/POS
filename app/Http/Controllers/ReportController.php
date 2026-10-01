<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleLine;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->allows('reports'), 403);

        $completed = Sale::query()->where('status', 'completed');

        $sales = [
            'today' => (clone $completed)->whereDate('created_at', today())->sum('total'),
            'week' => (clone $completed)->where('created_at', '>=', now()->startOfWeek())->sum('total'),
            'month' => (clone $completed)->where('created_at', '>=', now()->startOfMonth())->sum('total'),
        ];

        $margin = SaleLine::query()
            ->join('sales', 'sales.id', '=', 'sale_lines.sale_id')
            ->join('products', 'products.id', '=', 'sale_lines.product_id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->where('sales.status', 'completed')
            ->groupBy('categories.name')
            ->orderBy('categories.name')
            ->get([
                'categories.name as category',
                DB::raw('sum(sale_lines.line_total) as revenue'),
                DB::raw('sum(sale_lines.cost_price * sale_lines.base_quantity) as cost'),
            ]);

        $movers = SaleLine::query()
            ->join('sales', 'sales.id', '=', 'sale_lines.sale_id')
            ->where('sales.status', 'completed')
            ->where('sales.created_at', '>=', now()->subDays(30))
            ->groupBy('sale_lines.description')
            ->orderByDesc(DB::raw('sum(sale_lines.base_quantity)'))
            ->limit(8)
            ->get([
                'sale_lines.description',
                DB::raw('sum(sale_lines.base_quantity) as qty'),
            ]);

        $soldIds = SaleLine::query()
            ->join('sales', 'sales.id', '=', 'sale_lines.sale_id')
            ->where('sales.status', 'completed')
            ->where('sales.created_at', '>=', now()->subDays(90))
            ->pluck('sale_lines.product_id');

        $dead = Product::withSum('stocks', 'quantity')
            ->whereNotIn('id', $soldIds)
            ->get()
            ->filter(fn (Product $product) => (float) $product->stocks_sum_quantity > 0)
            ->take(8);

        $valuation = DB::table('stocks')
            ->join('products', 'products.id', '=', 'stocks.product_id')
            ->rawValue('coalesce(sum(stocks.quantity * products.buying_price), 0)');

        $byCashier = Sale::query()
            ->join('users', 'users.id', '=', 'sales.user_id')
            ->where('sales.status', 'completed')
            ->groupBy('users.name')
            ->orderByDesc(DB::raw('sum(sales.total)'))
            ->get(['users.name', DB::raw('sum(sales.total) as total')]);

        $byMethod = Payment::query()
            ->join('sales', 'sales.id', '=', 'payments.sale_id')
            ->where('sales.status', 'completed')
            ->groupBy('payments.method')
            ->get(['payments.method', DB::raw('sum(payments.amount) as total')]);

        $aging = Customer::where('balance', '>', 0)->orderByDesc('balance')->get()->map(function (Customer $customer) {
            $oldest = Sale::where('customer_id', $customer->id)
                ->where('status', 'completed')
                ->whereHas('payments', fn ($query) => $query->where('method', 'credit'))
                ->oldest()
                ->first();
            $days = $oldest ? $oldest->created_at->diffInDays(now()) : 0;
            $bucket = match (true) {
                $days > 90 => '90+',
                $days > 60 => '61–90',
                $days > 30 => '31–60',
                default => '0–30',
            };

            return ['name' => $customer->name, 'balance' => $customer->balance, 'bucket' => $bucket];
        });

        return view('reports.index', compact('sales', 'margin', 'movers', 'dead', 'valuation', 'byCashier', 'byMethod', 'aging'));
    }
}
