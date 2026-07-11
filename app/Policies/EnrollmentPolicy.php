<?php

namespace App\Policies;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\User;

class EnrollmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isStudent() || $user->isTeacher();
    }

    public function view(User $user, Enrollment $enrollment): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ((int) $enrollment->user_id === (int) $user->id) {
            return true;
        }

        return $user->isTeacher()
            && (int) $enrollment->course?->user_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isStudent();
    }

    public function update(User $user, Enrollment $enrollment): bool
    {
        return (int) $enrollment->user_id === (int) $user->id;
    }

    public function delete(User $user, Enrollment $enrollment): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isStudent()
            && (int) $enrollment->user_id === (int) $user->id
            && $enrollment->status === EnrollmentStatus::Active;
    }
}
