<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\StudentDashboardService;
use Illuminate\View\View;

class StudentDashboardController extends Controller
{
    public function index(StudentDashboardService $service): View
    {
        $data = $service->dashboardPayload(auth()->user());

        return view('student.dashboard', $data);
    }

    public function courses(): View
    {
        $service = app(StudentDashboardService::class);
        $data = $service->dashboardPayload(auth()->user());

        return view('student.courses', $data);
    }

    public function premium(): View
    {
        return view('student.premium', ['isPremium' => (bool) auth()->user()->is_premium]);
    }

    public function appointments(): View
    {
        return view('student.appointments');
    }
}
