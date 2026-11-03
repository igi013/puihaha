<?php

namespace App\Http\Controllers;

use App\Models\MembershipTransaction;
use App\Models\Payment;
use App\Services\MembershipProcessing;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function index()
    {
        return view('payments.index', ['payments' => Payment::with('transaction.member')->latest('id')->paginate(15)]);
    }

    public function store(Request $request, MembershipTransaction $transaction, MembershipProcessing $processing)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999.99', 'decimal:0,2'],
            'method' => ['required', Rule::in(['Cash', 'Bank transfer', 'GCash', 'Card'])],
            'external_reference' => ['nullable', 'string', 'max:100', Rule::requiredIf($request->input('method') !== 'Cash')],
            'notes' => ['nullable', 'string', 'max:2000'],
            'idempotency_key' => ['required', 'uuid'],
        ]);
        $processing->pay($transaction, $data, $request->user()->id);

        return redirect()->route('transactions.show', $transaction)->with('success', 'Payment recorded. No money was charged by this system.');
    }
}
