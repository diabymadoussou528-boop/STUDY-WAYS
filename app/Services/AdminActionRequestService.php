<?php

namespace App\Services;

use App\Enums\AdminActionStatus;
use App\Enums\ApprovalStatus;
use App\Enums\CourseStatus;
use App\Models\AdminActionRequest;
use App\Models\AdminAuditLog;
use App\Models\Course;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AdminActionRequestService
{
    /**
     * @param  array<string, mixed>|null  $payload
     */
    public function submit(User $requester, string $action, string $title, ?string $description = null, ?Model $target = null, ?array $payload = null): AdminActionRequest
    {
        $request = AdminActionRequest::query()->create([
            'requested_by' => $requester->id,
            'action' => $action,
            'target_type' => $target ? $target::class : null,
            'target_id' => $target?->getKey(),
            'payload' => $payload,
            'title' => $title,
            'description' => $description,
            'status' => AdminActionStatus::Pending,
        ]);

        AdminAuditLog::record('admin_action.requested', $requester, [
            'request_id' => $request->id,
            'action' => $action,
            'title' => $title,
        ]);

        return $request;
    }

    public function requiresApproval(User $actor): bool
    {
        return $actor->isAdmin() && ! $actor->isSuperAdmin();
    }

    public function approve(AdminActionRequest $request, User $reviewer, ?string $note = null): void
    {
        DB::transaction(function () use ($request, $reviewer, $note) {
            $request->update([
                'status' => AdminActionStatus::Approved,
                'reviewed_by' => $reviewer->id,
                'review_note' => $note,
                'reviewed_at' => now(),
            ]);

            $this->execute($request);

            AdminAuditLog::record('admin_action.approved', $reviewer, [
                'request_id' => $request->id,
                'action' => $request->action,
            ]);
        });
    }

    public function reject(AdminActionRequest $request, User $reviewer, ?string $note = null): void
    {
        $request->update([
            'status' => AdminActionStatus::Rejected,
            'reviewed_by' => $reviewer->id,
            'review_note' => $note,
            'reviewed_at' => now(),
        ]);

        AdminAuditLog::record('admin_action.rejected', $reviewer, [
            'request_id' => $request->id,
            'action' => $request->action,
        ]);
    }

    private function execute(AdminActionRequest $request): void
    {
        match ($request->action) {
            'delete_user' => $this->deleteUser($request),
            'delete_course' => $this->deleteCourse($request),
            'delete_testimonial' => $this->deleteTestimonial($request),
            'toggle_user_status' => $this->toggleUserStatus($request),
            'update_course_status' => $this->updateCourseStatus($request),
            'create_course' => $this->publishCourse($request),
            'publish_course' => $this->publishCourse($request),
            default => null,
        };
    }

    private function publishCourse(AdminActionRequest $request): void
    {
        $target = $request->resolveTarget();

        if ($target instanceof Course) {
            $target->update([
                'status' => CourseStatus::Published,
                'published_at' => now(),
                'approval_status' => ApprovalStatus::Approved,
                'approved_by' => $request->reviewed_by,
                'approved_at' => $request->reviewed_at ?? now(),
            ]);
        }
    }

    private function deleteUser(AdminActionRequest $request): void
    {
        $target = $request->resolveTarget();

        if ($target instanceof User) {
            $target->delete();
        }
    }

    private function deleteCourse(AdminActionRequest $request): void
    {
        $target = $request->resolveTarget();

        if ($target instanceof Course) {
            $target->delete();
        }
    }

    private function deleteTestimonial(AdminActionRequest $request): void
    {
        $modelClass = Testimonial::class;
        $target = $modelClass::query()->find($request->target_id);

        $target?->delete();
    }

    private function toggleUserStatus(AdminActionRequest $request): void
    {
        $target = $request->resolveTarget();

        if ($target instanceof User) {
            $target->update([
                'is_active' => ! $target->is_active,
            ]);
        }
    }

    private function updateCourseStatus(AdminActionRequest $request): void
    {
        $target = $request->resolveTarget();

        if (! $target instanceof Course || ! isset($request->payload['status'])) {
            return;
        }

        if ($request->payload['status'] === CourseStatus::Published->value) {
            $this->publishCourse($request);

            return;
        }

        $target->update(['status' => $request->payload['status']]);
    }
}
