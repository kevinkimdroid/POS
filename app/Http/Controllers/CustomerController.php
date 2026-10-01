<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(): View
    {
        return view('customers.index', [
            'customers' => Customer::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:120'],
            'account_type' => ['required', 'in:individual,contractor,company'],
            'price_level' => ['required', 'in:retail,wholesale,contractor'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'loyalty' => ['nullable', 'boolean'],
        ]);

        Customer::create([
            ...$data,
            'credit_limit' => $data['credit_limit'] ?? 0,
            'loyalty' => $request->boolean('loyalty'),
        ]);

        return back()->with('status', $data['name'].' is on the customer list.');
    }

    public function show(Customer $customer): View
    {
        $customer->load(['sales' => fn ($query) => $query->with('lines')->latest()->limit(20)]);

        return view('customers.show', ['customer' => $customer]);
    }

    public function collect(Request $request, Customer $customer): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'method' => ['required', 'in:cash,mpesa,bank,cheque'],
            'reference' => ['nullable', 'string', 'max:80'],
        ]);

        DB::transaction(function () use ($request, $customer, $data) {
            $customer = Customer::lockForUpdate()->find($customer->id);
            $customer->balance = max(0, (float) $customer->balance - (float) $data['amount']);
            $customer->save();
            CustomerPayment::create([
                'customer_id' => $customer->id,
                'user_id' => $request->user()->id,
                'amount' => $data['amount'],
                'method' => $data['method'],
                'reference' => $data['reference'] ?? null,
            ]);
        });

        return back()->with('status', 'Payment collected.');
    }
}
