<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdminActionStatus;
use App\Http\Controllers\Controller;
use App\Models\AdminActionRequest;
use App\Models\Course;
use App\Services\AdminActionRequestService;
use App\Services\NotificationDispatchService;
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

    public function approve(AdminActionRequest $approval, Request $request, AdminActionRequestService $service, NotificationDispatchService $notifications): RedirectResponse
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        abort_unless($approval->isPending(), 422);

        $service->approve($approval, auth()->user(), $request->input('review_note'));
        $this->notifyRequester($approval, $notifications, 'approved');

        return redirect()
            ->route('admin.approvals')
            ->with('success', 'Demande approuvée et action exécutée.');
    }

    public function reject(AdminActionRequest $approval, Request $request, AdminActionRequestService $service, NotificationDispatchService $notifications): RedirectResponse
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        abort_unless($approval->isPending(), 422);

        $service->reject($approval, auth()->user(), $request->input('review_note'));
        $this->notifyRequester($approval, $notifications, 'rejected');

        return redirect()
            ->route('admin.approvals')
            ->with('success', 'Demande rejetée.');
    }

    private function notifyRequester(AdminActionRequest $approval, NotificationDispatchService $notifications, string $decision): void
    {
        $approval->refresh();
        $requester = $approval->requester;

        if (! $requester) {
            return;
        }

        $course = $approval->resolveTarget();
        $link = $course instanceof Course ? route('courses.show', $course) : null;

        if ($decision === 'approved') {
            $notifications->notify(
                $requester,
                'course_approved',
                'Cours approuvé',
                'Votre cours « '.($course?->title ?? 'demandé').' » a été approuvé et publié.',
                ['course_id' => $course?->id, 'link' => $link],
                false,
            );

            return;
        }

        $notifications->notify(
            $requester,
            'course_rejected',
            'Cours rejeté',
            'Votre demande de création du cours « '.($course?->title ?? 'demandé').' » a été rejetée.',
            ['course_id' => $course?->id, 'link' => $link],
            false,
        );
    }
}
