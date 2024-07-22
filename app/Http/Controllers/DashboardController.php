<?php
/**
 * Functions for dashboard
 */
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Class contain functions for admin
 */
class DashboardController extends Controller
{
    /**
     * Function to display the dashboard contents for admin
     *
     * @param array $request All input values from form
     *
     * @return contents to display in dashboard
     */
    
    public function instructorDashboard(Request $request)
    {
        return view('instructor.dashboard');
    }
    
}
