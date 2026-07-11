<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdminActionStatus;
use App\Http\Controllers\Controller;
use App\Models\AdminActionRequest;
use App\Services\AdminActionRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApprovalRequestController extends Controller
{
    public function index(): View
    {
        $requests = AdminActionRequest::query()
            ->with(['requester:id,name,avatar,email', 'reviewer:id,name'])
            ->latest()
            ->paginate(15);

        $pendingCount = AdminActionRequest::query()
            ->where('status', AdminActionStatus::Pending)
            ->count();

        return view('admin.approvals.index', compact('requests', 'pendingCount'));
    }

    public function show(AdminActionRequest $approval): View
    {
        $approval->load(['requester:id,name,email,avatar', 'reviewer:id,name']);

        return view('admin.approvals.show', compact('approval'));
    }

    public function approve(AdminActionRequest $approval, Request $request, AdminActionRequestService $service): RedirectResponse
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        abort_unless($approval->isPending(), 422);

        $service->approve($approval, auth()->user(), $request->input('review_note'));

        return redirect()
            ->route('admin.approvals')
            ->with('success', 'Demande approuvée et action exécutée.');
    }

    public function reject(AdminActionRequest $approval, Request $request, AdminActionRequestService $service): RedirectResponse
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        abort_unless($approval->isPending(), 422);

        $service->reject($approval, auth()->user(), $request->input('review_note'));

        return redirect()
            ->route('admin.approvals')
            ->with('success', 'Demande rejetée.');
    }
}
