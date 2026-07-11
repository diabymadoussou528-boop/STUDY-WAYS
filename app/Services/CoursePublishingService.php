<?php

namespace App\Services;

use App\Enums\CourseStatus;
use App\Models\AdminAuditLog;
use App\Models\Course;
use App\Models\User;

class CoursePublishingService
{
    public function __construct(
        private NotificationDispatchService $notifications,
    ) {}

    public function submitForReview(Course $course, User $teacher): Course
    {
        abort_unless((int) $course->user_id === (int) $teacher->id, 403);

        $course->update([
            'status' => CourseStatus::PendingReview,
            'submitted_at' => now(),
        ]);

        AdminAuditLog::recordDetailed('course.submitted', 'course', 'Cours soumis pour revue : '.$course->title);

        $this->notifications->notifyAdmins(
            'approval_request',
            'Cours en attente de revue',
            '« '.$course->title.' » a été soumis par '.$teacher->name.'.',
            ['course_id' => $course->id],
        );

        return $course->fresh();
    }

    public function publish(Course $course, User $admin): Course
    {
        $course->update([
            'status' => CourseStatus::Published,
            'published_at' => now(),
        ]);

        AdminAuditLog::recordDetailed('course.published', 'course', 'Cours publié : '.$course->title);

        if ($course->user_id) {
            $this->notifications->notify(
                User::query()->find($course->user_id),
                'course_published',
                'Cours publié',
                '« '.$course->title.' » est maintenant publié.',
                ['course_id' => $course->id],
            );
        }

        return $course->fresh();
    }

    public function archive(Course $course): Course
    {
        $course->update(['status' => CourseStatus::Archived]);

        AdminAuditLog::recordDetailed('course.archived', 'course', 'Cours archivé : '.$course->title);

        return $course->fresh();
    }

    public function duplicate(Course $course): Course
    {
        $copy = $course->replicate(['slug', 'views', 'published_at', 'submitted_at']);
        $copy->title = $course->title.' (copie)';
        $copy->status = CourseStatus::Draft;
        $copy->views = 0;
        $copy->save();

        foreach ($course->lessons as $lesson) {
            $copy->lessons()->create($lesson->only(['title', 'video_url']));
        }

        AdminAuditLog::recordDetailed('course.duplicated', 'course', 'Cours dupliqué : '.$course->title);

        return $copy;
    }
}
