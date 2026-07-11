<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\LearningProgressService;
use Illuminate\View\View;

class LearningProgressController extends Controller
{
    public function index(LearningProgressService $service): View
    {
        $payload = $service->dashboardPayload(auth()->user());

        return view('student.progress.index', $payload);
    }
}
