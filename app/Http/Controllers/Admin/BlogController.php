<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use SiteHelpers;

class BlogController extends Controller
{
    /**
     * Display the blogs for admin.
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
            $blogs = Blog::where('blog_title', 'LIKE', '%' . $search . '%')
                           ->paginate($paginate_count);
        } else {
            $blogs = Blog::paginate($paginate_count);
        }

        return view('admin.blogs.index', compact('blogs'));
    }

    public function getForm($blog_id = '', Request $request)
    {
        if ($blog_id) {
            $blog = Blog::find($blog_id);
        } else {
            $blog = new Blog();
        }
        return view('admin.blogs.form', compact('blog'));
    }

    public function saveBlog(Request $request)
    {
        $blog_id = $request->input('blog_id');

        $validation_rules = ['blog_title' => 'required|string'];
        $validator = Validator::make($request->all(), $validation_rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        if ($blog_id) {
            $blog = Blog::find($blog_id);
            $success_message = 'Blog updated successfully';
        } else {
            $blog = new Blog();
            $success_message = 'Blog added successfully';

            $slug = str_slug($request->input('blog_title'), '-');
            $results = DB::select(DB::raw("SELECT count(*) as total from blogs where blog_slug REGEXP '^{$slug}(-[0-9]+)?$' "));
            $finalSlug = ($results[0]->total > 0) ? "{$slug}-{$results[0]->total}" : $slug;
            $blog->blog_slug = $finalSlug;
        }

        $blog->blog_title = $request->input('blog_title');
        $blog->description = $request->input('description');
        $blog->is_active = $request->input('is_active');

        if ($request->hasFile('blog_image') && $request->has('blog_image_base64')) {
            $old_image = $request->input('old_blog_image');
            if (Storage::exists($old_image)) {
                Storage::delete($old_image);
            }

            $file_name = $request->file('blog_image')->getClientOriginalName();
            $image_make = Image::make($request->input('blog_image_base64'))->encode('jpg');
            $path = "blogs";
            $new_file_name = SiteHelpers::checkFileName($path, $file_name);
            Storage::put($path . "/" . $new_file_name, $image_make->__toString(), 'public');

            $blog->blog_image = $path . "/" . $new_file_name;
        }

        $blog->save();

        return redirect('admin/blogs')->with('success', $success_message);
    }

    public function deleteBlog($blog_id)
    {
        Blog::destroy($blog_id);
        return redirect('admin/blogs')->with('success', 'Blog deleted successfully');
    }
}
