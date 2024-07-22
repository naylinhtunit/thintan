<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Instructor;

class DashboardController extends Controller
{
    /**
     * Display the dashboard contents for admin
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $courses = DB::table('courses')
                        ->select('courses.*', 'categories.name as category_name', 'instructors.first_name as instructor_name')
                        ->leftJoin('categories', 'categories.id', '=', 'courses.category_id')
                        ->leftJoin('instructors', 'instructors.id', '=', 'courses.instructor_id')
                        ->paginate(5);

        $metrics = Instructor::admin_metrics();

        return view('admin.dashboard.index', compact('courses', 'metrics'));
    }
}
