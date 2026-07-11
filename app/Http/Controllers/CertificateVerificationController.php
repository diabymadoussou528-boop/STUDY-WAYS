<?php

namespace App\Http\Controllers;

use App\Services\CertificateService;
use Illuminate\View\View;

class CertificateVerificationController extends Controller
{
    public function show(string $token, CertificateService $service): View
    {
        $enrollment = $service->verify($token);

        return view('certificates.verify', [
            'valid' => $enrollment !== null,
            'enrollment' => $enrollment,
        ]);
    }
}
