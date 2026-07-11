<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class MessagingService
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function conversationsFor(User $user): Collection
    {
        if (! Schema::hasTable('messages')) {
            return collect();
        }

        $messages = Message::query()
            ->where(fn ($q) => $q->where('from_user_id', $user->id)->orWhere('to_user_id', $user->id))
            ->with(['sender:id,name,avatar,role', 'recipient:id,name,avatar,role', 'course:id,title'])
            ->orderByDesc('created_at')
            ->get();

        return $messages
            ->groupBy(fn (Message $m) => $this->conversationKey($m, $user))
            ->map(function (Collection $thread) use ($user) {
                $latest = $thread->first();
                $other = $latest->from_user_id === $user->id ? $latest->recipient : $latest->sender;

                return [
                    'key' => $this->conversationKey($latest, $user),
                    'other_user' => $other,
                    'course' => $latest->course,
                    'course_id' => $latest->course_id,
                    'other_user_id' => $other?->id,
                    'last_message' => $latest,
                    'unread_count' => $thread->filter(
                        fn (Message $m) => $m->to_user_id === $user->id && $m->read_at === null
                    )->count(),
                    'updated_at' => $latest->created_at,
                ];
            })
            ->sortByDesc('updated_at')
            ->values();
    }

    /**
     * @return Collection<int, Message>
     */
    public function threadMessages(User $user, int $otherUserId, int $courseId): Collection
    {
        $this->assertCanAccessThread($user, $otherUserId, $courseId);

        return Message::query()
            ->where('course_id', $courseId)
            ->where(function ($q) use ($user, $otherUserId) {
                $q->where(function ($inner) use ($user, $otherUserId) {
                    $inner->where('from_user_id', $user->id)->where('to_user_id', $otherUserId);
                })->orWhere(function ($inner) use ($user, $otherUserId) {
                    $inner->where('from_user_id', $otherUserId)->where('to_user_id', $user->id);
                });
            })
            ->with(['sender:id,name,avatar', 'recipient:id,name,avatar'])
            ->orderBy('created_at')
            ->get();
    }

    public function send(User $sender, int $recipientId, int $courseId, string $body): Message
    {
        $course = Course::query()->with('user:id,name,role')->findOrFail($courseId);
        $recipient = User::query()->findOrFail($recipientId);

        $this->authorizeParticipants($sender, $recipient, $course);

        return Message::query()->create([
            'from_user_id' => $sender->id,
            'to_user_id' => $recipient->id,
            'course_id' => $course->id,
            'body' => $body,
        ]);
    }

    public function markThreadAsRead(User $user, int $otherUserId, int $courseId): void
    {
        $this->assertCanAccessThread($user, $otherUserId, $courseId);

        Message::query()
            ->where('course_id', $courseId)
            ->where('to_user_id', $user->id)
            ->where('from_user_id', $otherUserId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function unreadCount(User $user): int
    {
        if (! Schema::hasTable('messages')) {
            return 0;
        }

        return Message::query()
            ->where('to_user_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function contactableTeachersForStudent(User $student): Collection
    {
        if (! Schema::hasTable('enrollments')) {
            return collect();
        }

        return Enrollment::query()
            ->where('user_id', $student->id)
            ->with(['course.user:id,name,avatar,role', 'course:id,title,user_id'])
            ->get()
            ->filter(fn (Enrollment $e) => $e->course?->user)
            ->map(fn (Enrollment $e) => [
                'course_id' => $e->course_id,
                'course_title' => $e->course->title,
                'teacher' => $e->course->user,
                'teacher_id' => $e->course->user_id,
            ])
            ->unique(fn ($item) => $item['course_id'].'-'.$item['teacher_id'])
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function contactableStudentsForProfessor(User $professor): Collection
    {
        if (! Schema::hasTable('enrollments')) {
            return collect();
        }

        $courseIds = $professor->taughtCourses()->pluck('id');

        return Enrollment::query()
            ->whereIn('course_id', $courseIds)
            ->with(['user:id,name,avatar,role', 'course:id,title,user_id'])
            ->get()
            ->map(fn (Enrollment $e) => [
                'course_id' => $e->course_id,
                'course_title' => $e->course?->title,
                'student' => $e->user,
                'student_id' => $e->user_id,
            ])
            ->filter(fn ($item) => $item['student'])
            ->unique(fn ($item) => $item['course_id'].'-'.$item['student_id'])
            ->values();
    }

    private function conversationKey(Message $message, User $viewer): string
    {
        $otherId = $message->from_user_id === $viewer->id
            ? $message->to_user_id
            : $message->from_user_id;

        return $message->course_id.'-'.$otherId;
    }

    private function assertCanAccessThread(User $user, int $otherUserId, int $courseId): void
    {
        $course = Course::query()->findOrFail($courseId);
        $other = User::query()->findOrFail($otherUserId);

        $this->authorizeParticipants($user, $other, $course);
    }

    private function authorizeParticipants(User $sender, User $recipient, Course $course): void
    {
        $isStudentTeacherPair = (
            ($sender->isStudent() && $recipient->isTeacher() && (int) $course->user_id === $recipient->id)
            || ($sender->isTeacher() && $recipient->isStudent())
        );

        if (! $isStudentTeacherPair) {
            throw new RuntimeException('Conversation non autorisée.');
        }

        if ($sender->isStudent()) {
            $this->assertStudentEnrolled($sender, $course);
        }

        if ($recipient->isStudent()) {
            $this->assertStudentEnrolled($recipient, $course);
        }

        if ($sender->isTeacher() && (int) $course->user_id !== $sender->id) {
            throw new RuntimeException('Vous ne pouvez pas accéder à cette conversation.');
        }
    }

    private function assertStudentEnrolled(User $student, Course $course): void
    {
        if (! Schema::hasTable('enrollments')) {
            throw new RuntimeException('Inscriptions non disponibles.');
        }

        $enrolled = Enrollment::query()
            ->where('user_id', $student->id)
            ->where('course_id', $course->id)
            ->exists();

        if (! $enrolled) {
            throw new RuntimeException('L\'étudiant doit être inscrit au cours pour communiquer.');
        }
    }
}
