<?php

namespace App\Http\Controllers;

use App\Models\Quotation;
use Illuminate\View\View;

class QuoteController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->allows('sell'), 403);

        return view('quotes.index', [
            'quotes' => Quotation::with(['customer', 'lines'])->latest()->limit(30)->get(),
        ]);
    }
}
