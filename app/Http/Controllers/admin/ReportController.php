<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(): View
    {
        $stats = [
            'students' => User::where('role', 'student')->count(),
            'teachers' => User::where('role', 'professor')->count(),
            'courses' => Course::count(),
            'enrollments' => Schema::hasTable('enrollments') ? Enrollment::count() : 0,
        ];

        return view('admin.reports.index', compact('stats'));
    }

    public function export(string $type, string $format): StreamedResponse|Response
    {
        abort_unless(in_array($format, ['csv', 'excel', 'pdf'], true), 404);

        $rows = match ($type) {
            'students' => User::where('role', 'student')->get(['name', 'email', 'phone', 'created_at', 'is_active']),
            'teachers' => User::where('role', 'professor')->get(['name', 'email', 'phone', 'created_at', 'is_active']),
            'courses' => Course::with('user:id,name')->get(),
            default => collect(),
        };

        if ($format === 'pdf') {
            return response('Export PDF — '.$type.' (à connecter avec DomPDF)', 200, [
                'Content-Type' => 'text/plain',
            ]);
        }

        $filename = "studyways-{$type}-".now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows, $type) {
            $handle = fopen('php://output', 'w');

            if ($type === 'courses') {
                fputcsv($handle, ['Titre', 'Professeur', 'Vues', 'Statut', 'Créé le']);
                foreach ($rows as $course) {
                    fputcsv($handle, [
                        $course->title,
                        $course->user?->name ?? '—',
                        $course->views ?? 0,
                        $course->status ?? 'published',
                        $course->created_at?->format('Y-m-d'),
                    ]);
                }
            } else {
                fputcsv($handle, ['Nom', 'E-mail', 'Téléphone', 'Inscrit le', 'Actif']);
                foreach ($rows as $user) {
                    fputcsv($handle, [
                        $user->name,
                        $user->email,
                        $user->phone ?? '—',
                        $user->created_at?->format('Y-m-d'),
                        $user->is_active ? 'Oui' : 'Non',
                    ]);
                }
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
