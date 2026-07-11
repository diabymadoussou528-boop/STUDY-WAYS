<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Services\CertificateService;
use Illuminate\View\View;

class CertificateController extends Controller
{
    public function index(CertificateService $service): View
    {
        $student = auth()->user();
        $enrollments = $service->eligibleEnrollmentsFor($student);
        $isPremium = $student->hasActivePremium();

        return view('student.certificates.index', compact('enrollments', 'isPremium'));
    }

    public function show(Enrollment $enrollment, CertificateService $service): View
    {
        $this->authorize('view', $enrollment);

        $payload = $service->issue(auth()->user(), $enrollment);

        return view('student.certificates.show', $payload);
    }
}
