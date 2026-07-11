<?php

namespace App\Policies;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStudent() || $user->isTeacher() || $user->isAdmin();
    }

    public function view(User $user, Appointment $appointment): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return (int) $appointment->student_id === (int) $user->id
            || (int) $appointment->professor_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isStudent();
    }

    public function respond(User $user, Appointment $appointment): bool
    {
        return $user->isTeacher()
            && (int) $appointment->professor_id === (int) $user->id
            && $appointment->status === AppointmentStatus::Pending;
    }

    public function cancel(User $user, Appointment $appointment): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ((int) $appointment->student_id === (int) $user->id) {
            return in_array($appointment->status, [
                AppointmentStatus::Pending,
                AppointmentStatus::Accepted,
                AppointmentStatus::Rescheduled,
            ], true);
        }

        return $user->isTeacher()
            && (int) $appointment->professor_id === (int) $user->id;
    }
}
