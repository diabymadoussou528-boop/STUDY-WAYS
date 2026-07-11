<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use Illuminate\View\View;

class EnrollmentManagementController extends Controller
{
    public function index(): View
    {
        $enrollments = Enrollment::query()
            ->with(['user:id,name,email,avatar', 'course:id,title'])
            ->latest('enrolled_at')
            ->paginate(20);

        return view('admin.enrollments.index', compact('enrollments'));
    }
}
