<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isTeacher() || $user->isAdmin() || $user->isStudent();
    }

    public function view(?User $user, Course $course): bool
    {
        if ($course->isPublished()) {
            return true;
        }

        if (! $user) {
            return false;
        }

        return $user->isAdmin() || (int) $course->user_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isTeacher() || $user->isAdmin();
    }

    public function update(User $user, Course $course): bool
    {
        return $user->isAdmin() || (int) $course->user_id === (int) $user->id;
    }

    public function delete(User $user, Course $course): bool
    {
        return $user->isAdmin() || (int) $course->user_id === (int) $user->id;
    }
}
