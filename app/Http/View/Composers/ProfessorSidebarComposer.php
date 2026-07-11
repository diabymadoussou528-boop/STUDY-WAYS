<?php

namespace App\Http\View\Composers;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Services\MessagingService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Schema;

class ProfessorSidebarComposer
{
    public function __construct(private MessagingService $messaging) {}

    public function compose(View $view): void
    {
        $user = auth()->user();

        if (! $user || ! $user->isTeacher()) {
            return;
        }

        $unreadMessages = Schema::hasTable('messages')
            ? $this->messaging->unreadCount($user)
            : 0;

        $pendingAppointments = Schema::hasTable('appointments')
            ? Appointment::query()
                ->where('professor_id', $user->id)
                ->where('status', AppointmentStatus::Pending)
                ->count()
            : 0;

        $view->with([
            'unreadMessages' => $unreadMessages,
            'pendingAppointments' => $pendingAppointments,
        ]);
    }
}
