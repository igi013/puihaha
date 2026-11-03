<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\MembershipTransaction;
use App\Services\MembershipProcessing;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['status' => ['nullable', Rule::in(['Pending', 'Paid', 'Approved', 'Expired', 'Cancelled'])], 'member_id' => ['nullable', 'integer', 'exists:members,id']]);
        $query = MembershipTransaction::with(['member', 'payments'])->latest('id');
        if (! empty($filters['member_id'])) {
            $query->where('member_id', $filters['member_id']);
        }
        if (! empty($filters['status'])) {
            match ($filters['status']) {
                'Expired' => $query->where('status', 'Approved')->whereDate('expires_at', '<', today()),
                'Approved' => $query->where('status', 'Approved')->whereDate('expires_at', '>=', today()),
                default => $query->where('status', $filters['status']),
            };
        }

        return view('transactions.index', ['transactions' => $query->paginate(15)->withQueryString()]);
    }

    public function create(Request $request)
    {
        $request->validate(['member_id' => ['nullable', 'integer', 'exists:members,id']]);

        return view('transactions.create', ['members' => Member::with(['level', 'category'])->where('status', 'active')->orderBy('last_name')->get()]);
    }

    public function store(Request $request, MembershipProcessing $processing)
    {
        $data = $request->validate(['member_id' => ['required', 'integer', 'exists:members,id'], 'type' => ['required', Rule::in(['Application', 'Renewal'])]]);
        $transaction = $processing->create($data['member_id'], $data['type'], $request->user()->id);

        return redirect()->route('transactions.show', $transaction)->with('success', 'Transaction created. Record payment before approval.');
    }

    public function show(MembershipTransaction $transaction)
    {
        return view('transactions.show', ['transaction' => $transaction->load(['member', 'payments']), 'paymentKey' => (string) Str::uuid()]);
    }

    public function approve(Request $request, MembershipTransaction $transaction, MembershipProcessing $processing)
    {
        $processing->approve($transaction, $request->user()->id);

        return back()->with('success', 'Membership approved and validity dates updated.');
    }

    public function cancel(MembershipTransaction $transaction, MembershipProcessing $processing)
    {
        $processing->cancel($transaction);

        return back()->with('success', 'Transaction cancelled.');
    }
}
