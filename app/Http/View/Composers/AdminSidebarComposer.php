<?php

namespace App\Http\View\Composers;

use App\Enums\AdminActionStatus;
use App\Models\AdminActionRequest;
use App\Models\Enrollment;
use App\Models\Testimonial;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Schema;

class AdminSidebarComposer
{
    public function compose(View $view): void
    {
        $pendingApprovals = Schema::hasTable('admin_action_requests')
            ? AdminActionRequest::query()->where('status', AdminActionStatus::Pending)->count()
            : 0;

        $pendingTestimonials = Testimonial::query()->where('is_approved', false)->count();
        $enrollmentCount = Schema::hasTable('enrollments')
            ? Enrollment::query()->count()
            : 0;

        $view->with([
            'pendingApprovals' => $pendingApprovals,
            'pendingTestimonials' => $pendingTestimonials,
            'enrollmentCount' => $enrollmentCount,
        ]);
    }
}
