<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\EnrollmentStatus;
use App\Models\Appointment;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class AppointmentService
{
    public function __construct(
        private NotificationDispatchService $notifications,
    ) {}

    /**
     * @param  array{scheduled_at: string, message?: string|null}  $data
     */
    public function request(User $student, Course $course, array $data): Appointment
    {
        if (! Schema::hasTable('enrollments')) {
            throw new RuntimeException('Inscriptions non disponibles.');
        }

        $enrolled = Enrollment::query()
            ->where('user_id', $student->id)
            ->where('course_id', $course->id)
            ->where('status', EnrollmentStatus::Active)
            ->exists();

        if (! $enrolled) {
            throw new RuntimeException('Vous devez être inscrit au cours pour demander un rendez-vous.');
        }

        $professor = $course->user;
        if (! $professor || ! $professor->isTeacher()) {
            throw new RuntimeException('Professeur introuvable pour ce cours.');
        }

        $appointment = Appointment::query()->create([
            'student_id' => $student->id,
            'professor_id' => $professor->id,
            'course_id' => $course->id,
            'scheduled_at' => $data['scheduled_at'],
            'message' => $data['message'] ?? null,
            'status' => AppointmentStatus::Pending,
        ]);

        $this->notifications->notify(
            $professor,
            'appointment_request',
            'Demande de rendez-vous',
            $student->name.' demande un rendez-vous pour « '.$course->title.' ».',
            ['appointment_id' => $appointment->id],
        );

        return $appointment;
    }

    public function accept(Appointment $appointment, User $professor, ?string $note = null, ?string $meetingLink = null): Appointment
    {
        $this->assertProfessorOwns($appointment, $professor);

        $appointment->update([
            'status' => AppointmentStatus::Accepted,
            'response_note' => $note,
            'meeting_link' => $meetingLink,
        ]);

        $this->notifications->notify(
            $appointment->student,
            'appointment_accepted',
            'Rendez-vous accepté',
            'Votre rendez-vous a été accepté.',
            ['appointment_id' => $appointment->id],
        );

        return $appointment->fresh();
    }

    public function reject(Appointment $appointment, User $professor, ?string $note = null): Appointment
    {
        $this->assertProfessorOwns($appointment, $professor);

        $appointment->update([
            'status' => AppointmentStatus::Rejected,
            'response_note' => $note,
        ]);

        $this->notifications->notify(
            $appointment->student,
            'appointment_rejected',
            'Rendez-vous refusé',
            $note ?? 'Votre demande de rendez-vous a été refusée.',
            ['appointment_id' => $appointment->id],
        );

        return $appointment->fresh();
    }

    public function reschedule(Appointment $appointment, User $professor, string $newDate, ?string $note = null): Appointment
    {
        $this->assertProfessorOwns($appointment, $professor);

        $appointment->update([
            'status' => AppointmentStatus::Rescheduled,
            'scheduled_at' => $newDate,
            'response_note' => $note,
        ]);

        $this->notifications->notify(
            $appointment->student,
            'appointment_rescheduled',
            'Rendez-vous reprogrammé',
            'Un nouveau créneau vous a été proposé.',
            ['appointment_id' => $appointment->id],
        );

        return $appointment->fresh();
    }

    public function cancel(Appointment $appointment, User $actor): Appointment
    {
        if ((int) $appointment->student_id !== (int) $actor->id
            && (int) $appointment->professor_id !== (int) $actor->id) {
            throw new RuntimeException('Action non autorisée.');
        }

        $appointment->update(['status' => AppointmentStatus::Cancelled]);

        $recipient = (int) $actor->id === (int) $appointment->student_id
            ? $appointment->professor
            : $appointment->student;

        $this->notifications->notify(
            $recipient,
            'appointment_cancelled',
            'Rendez-vous annulé',
            'Un rendez-vous a été annulé.',
            ['appointment_id' => $appointment->id],
        );

        return $appointment->fresh();
    }

    private function assertProfessorOwns(Appointment $appointment, User $professor): void
    {
        if ((int) $appointment->professor_id !== (int) $professor->id) {
            throw new RuntimeException('Rendez-vous non autorisé.');
        }
    }
}
