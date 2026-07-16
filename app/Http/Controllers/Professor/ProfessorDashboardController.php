<?php

namespace App\Http\Controllers\Professor;

use App\Http\Controllers\Controller;
use App\Services\ProfessorDashboardService;
use Illuminate\View\View;

class ProfessorDashboardController extends Controller
{
    public function index(ProfessorDashboardService $service): View
    {
        $data = $service->dashboardPayload(auth()->user());

        return view('professor.dashboard', $data);
    }

    public function courses(ProfessorDashboardService $service): View
    {
        $data = $service->dashboardPayload(auth()->user());

        return view('professor.courses-index', $data);
    }

    public function students(ProfessorDashboardService $service): View
    {
        $data = $service->dashboardPayload(auth()->user());

        return view('professor.students', ['courses' => $data['courses']]);
    }

    public function messages(ProfessorDashboardService $service): View
    {
        $data = $service->dashboardPayload(auth()->user());

        return view('professor.messages', ['recentMessages' => $data['recentMessages']]);
    }

    public function appointments(ProfessorDashboardService $service): View
    {
        $data = $service->dashboardPayload(auth()->user());

        return view('professor.appointments', ['pendingAppointments' => $data['pendingAppointments']]);
    }

    public function reviews(ProfessorDashboardService $service): View
    {
        $data = $service->dashboardPayload(auth()->user());

        return view('professor.reviews', ['recentReviews' => $data['recentReviews'], 'courses' => $data['courses']]);
    }

    public function archive(ProfessorDashboardService $service): View
    {
        $data = $service->dashboardPayload(auth()->user());

        return view('professor.archive', $data);
    }
}
