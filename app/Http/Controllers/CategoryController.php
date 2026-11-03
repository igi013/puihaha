<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Models\MembershipCategory;
use App\Models\MembershipTransaction;
use Illuminate\Database\QueryException;

class CategoryController extends Controller
{
    public function index()
    {
        return view('membership-options.index', ['kind' => 'categories', 'items' => MembershipCategory::withCount('members')->orderBy('id')->paginate(10)]);
    }

    public function create()
    {
        return view('membership-options.form', ['kind' => 'categories', 'item' => new MembershipCategory]);
    }

    public function store(CategoryRequest $request)
    {
        $category = MembershipCategory::create($request->validated());

        return redirect()->route('categories.show', $category)->with('success', 'Category created.');
    }

    public function show(MembershipCategory $category)
    {
        return view('membership-options.show', ['kind' => 'categories', 'item' => $category->loadCount('members')]);
    }

    public function edit(MembershipCategory $category)
    {
        return view('membership-options.form', ['kind' => 'categories', 'item' => $category]);
    }

    public function update(CategoryRequest $request, MembershipCategory $category)
    {
        $category->update($request->validated());

        return redirect()->route('categories.show', $category)->with('success', 'Category updated.');
    }

    public function destroy(MembershipCategory $category)
    {
        if ($category->members()->exists() || MembershipTransaction::where('membership_category_id', $category->id)->exists()) {
            return back()->withErrors(['category' => 'This category has assigned members or transaction history and cannot be deleted.']);
        }
        try {
            $category->delete();
        } catch (QueryException $exception) {
            if ($exception->getCode() !== '23000') {
                throw $exception;
            }

            return back()->withErrors(['category' => 'This category is in use and cannot be deleted.']);
        }

        return redirect()->route('categories.index')->with('success', 'Category deleted.');
    }
}
