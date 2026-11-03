<?php

namespace App\Http\Controllers;

use App\Http\Requests\MemberRequest;
use App\Models\Member;
use App\Models\MembershipCategory;
use App\Models\MembershipLevel;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'integer', 'exists:membership_categories,id'],
            'level' => ['nullable', 'integer', 'exists:membership_levels,id'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);
        $members = Member::with(['category', 'level'])
            ->when($filters['q'] ?? null, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('first_name', 'like', '%'.$search.'%')
                        ->orWhere('last_name', 'like', '%'.$search.'%')
                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%'.$search.'%'])
                        ->orWhere('membership_number', 'like', '%'.$search.'%');
                });
            })
            ->when($filters['category'] ?? null, fn ($query, $id) => $query->where('membership_category_id', $id))
            ->when($filters['level'] ?? null, fn ($query, $id) => $query->where('membership_level_id', $id))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest('id')->paginate(10)->withQueryString();

        return view('members.index', array_merge($this->options(), compact('members')));
    }

    public function create()
    {
        return view('members.form', array_merge($this->options(), ['member' => new Member(['status' => 'active'])]));
    }

    public function store(MemberRequest $request)
    {
        $member = Member::register($request->validated());

        return redirect()->route('members.show', $member)->with('success', 'Member registered successfully.');
    }

    public function show(Member $member)
    {
        $member->load(['category', 'level'])->loadCount('transactions');
        $transactions = $member->transactions()->with('payments')->latest('id')->paginate(10);
        $activeMembership = $member->transactions()->whereNotNull('approved_at')
            ->whereDate('starts_at', '<=', today())->whereDate('expires_at', '>=', today())
            ->latest('starts_at')->first();

        return view('members.show', compact('member', 'transactions', 'activeMembership'));
    }

    public function edit(Member $member)
    {
        return view('members.form', array_merge($this->options(), compact('member')));
    }

    public function update(MemberRequest $request, Member $member)
    {
        $member->update($request->validated());

        return redirect()->route('members.show', $member)->with('success', 'Member details updated.');
    }

    public function deactivate(Member $member)
    {
        $member->update(['status' => 'inactive']);

        return redirect()->route('members.index')->with('success', 'Member deactivated. Their profile has been retained.');
    }

    public function destroy(Request $request, Member $member)
    {
        $request->validate(['confirm_delete' => ['accepted']]);
        if ($member->transactions()->exists()) {
            return back()->withErrors(['member' => 'This member has transaction history and cannot be permanently deleted. Deactivate them instead.']);
        }
        $member->delete();

        return redirect()->route('members.index')->with('success', 'Member permanently deleted.');
    }

    private function options(): array
    {
        return ['categories' => MembershipCategory::orderBy('id')->get(), 'levels' => MembershipLevel::orderBy('id')->get()];
    }
}
