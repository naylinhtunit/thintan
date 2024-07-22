<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * Display the categories for admin.
     *
     * @param Request $request All input values from form
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $paginate_count = 10;
        if ($request->has('search')) {
            $search = $request->input('search');
            $categories = Category::where('name', 'LIKE', '%' . $search . '%')
                           ->paginate($paginate_count);
        } else {
            $categories = Category::paginate($paginate_count);
        }

        return view('admin.categories.index', compact('categories'));
    }

    public function getForm($category_id = '', Request $request)
    {
        if ($category_id) {
            $category = Category::find($category_id);
        } else {
            $category = new Category();
        }
        return view('admin.categories.form', compact('category'));
    }

    public function saveCategory(Request $request)
    {
        $category_id = $request->input('category_id');

        $validation_rules = ['name' => 'required|string|max:50'];
        $validator = Validator::make($request->all(), $validation_rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        if ($category_id) {
            $category = Category::find($category_id);
            $success_message = 'Category updated successfully';
        } else {
            $category = new Category();
            $success_message = 'Category added successfully';

            $slug = Str::slug($request->input('name'), '-');
            $results = DB::select(DB::raw("SELECT count(*) as total from categories where slug REGEXP '^{$slug}(-[0-9]+)?$' "));
            $finalSlug = ($results[0]->total > 0) ? "{$slug}-{$results[0]->total}" : $slug;
            $category->slug = $finalSlug;
        }

        $category->name = $request->input('name');
        $category->icon_class = $request->input('icon_class');
        $category->is_active = $request->input('is_active');
        $category->save();

        return redirect('admin/categories')->with('success', $success_message);
    }

    public function deleteCategory($category_id)
    {
        Category::destroy($category_id);
        return redirect('admin/categories')->with('success', 'Category deleted successfully');
    }
}
