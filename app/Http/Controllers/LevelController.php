<?php

namespace App\Http\Controllers;

use App\Http\Requests\LevelRequest;
use App\Models\MembershipLevel;
use App\Models\MembershipTransaction;
use Illuminate\Database\QueryException;

class LevelController extends Controller
{
    public function index()
    {
        return view('membership-options.index', ['kind' => 'levels', 'items' => MembershipLevel::withCount('members')->orderBy('id')->paginate(10)]);
    }

    public function create()
    {
        return view('membership-options.form', ['kind' => 'levels', 'item' => new MembershipLevel]);
    }

    public function store(LevelRequest $request)
    {
        $level = MembershipLevel::create($request->validated());

        return redirect()->route('levels.show', $level)->with('success', 'Level created.');
    }

    public function show(MembershipLevel $level)
    {
        return view('membership-options.show', ['kind' => 'levels', 'item' => $level->loadCount('members')]);
    }

    public function edit(MembershipLevel $level)
    {
        return view('membership-options.form', ['kind' => 'levels', 'item' => $level]);
    }

    public function update(LevelRequest $request, MembershipLevel $level)
    {
        $level->update($request->validated());

        return redirect()->route('levels.show', $level)->with('success', 'Level updated.');
    }

    public function destroy(MembershipLevel $level)
    {
        if ($level->members()->exists() || MembershipTransaction::where('membership_level_id', $level->id)->exists()) {
            return back()->withErrors(['level' => 'This level has assigned members or transaction history and cannot be deleted.']);
        }
        try {
            $level->delete();
        } catch (QueryException $exception) {
            if ($exception->getCode() !== '23000') {
                throw $exception;
            }

            return back()->withErrors(['level' => 'This level is in use and cannot be deleted.']);
        }

        return redirect()->route('levels.index')->with('success', 'Level deleted.');
    }
}
