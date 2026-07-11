<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $logs = $this->filteredQuery($request)->paginate(25)->withQueryString();

        return view('admin.audit-logs.index', compact('logs'));
    }

    public function export(Request $request, string $format): StreamedResponse|Response
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        abort_unless(in_array($format, ['csv', 'excel', 'pdf'], true), 404);

        $logs = $this->filteredQuery($request)->limit(2000)->get();
        $date = now()->format('Y-m-d');

        if ($format === 'pdf') {
            return response()->view('admin.audit-logs.export-pdf', compact('logs'), 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="audit-logs-'.$date.'.html"',
            ]);
        }

        $extension = $format === 'excel' ? 'xls' : 'csv';
        $contentType = $format === 'excel'
            ? 'application/vnd.ms-excel; charset=UTF-8'
            : 'text/csv; charset=UTF-8';

        return response()->streamDownload(function () use ($logs, $format) {
            $handle = fopen('php://output', 'w');

            if ($format === 'excel') {
                fwrite($handle, "\xEF\xBB\xBF");
            }

            fputcsv($handle, ['Date', 'Utilisateur', 'Rôle', 'Action', 'Module', 'Description', 'IP', 'User Agent']);

            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->created_at?->format('Y-m-d H:i:s'),
                    $log->actor?->name,
                    $log->role,
                    $log->action,
                    $log->module,
                    $log->description,
                    $log->ip_address,
                    $log->user_agent,
                ]);
            }

            fclose($handle);
        }, "audit-logs-{$date}.{$extension}", ['Content-Type' => $contentType]);
    }

    private function filteredQuery(Request $request)
    {
        return AdminAuditLog::query()
            ->with(['actor:id,name,role,email', 'targetUser:id,name'])
            ->when($request->filled('action'), fn ($q) => $q->where('action', 'like', '%'.$request->action.'%'))
            ->when($request->filled('module'), fn ($q) => $q->where('module', $request->module))
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->role))
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->user_id))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->latest();
    }
}
