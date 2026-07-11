<?php

namespace App\Services;

use App\Enums\AdminActionStatus;
use App\Models\AdminActionRequest;
use App\Models\Course;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class PlatformNotificationService
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function feed(int $limit = 12): Collection
    {
        $items = collect();

        User::query()->latest()->limit(4)->get()->each(function (User $user) use ($items) {
            $items->push([
                'type' => 'user',
                'icon' => $user->role === 'professor' ? 'fa-chalkboard-user' : 'fa-user-graduate',
                'title' => 'Nouvel utilisateur',
                'message' => $user->name.' a rejoint la plateforme en tant que '.$user->role.'.',
                'time' => $user->created_at,
            ]);
        });

        Course::query()->latest()->limit(3)->get()->each(function (Course $course) use ($items) {
            $items->push([
                'type' => 'course',
                'icon' => 'fa-book',
                'title' => 'Nouveau cours',
                'message' => '« '.$course->title.' » a été publié.',
                'time' => $course->created_at,
            ]);
        });

        Testimonial::query()->where('is_approved', false)->latest()->limit(3)->get()->each(function (Testimonial $testimonial) use ($items) {
            $items->push([
                'type' => 'testimonial',
                'icon' => 'fa-quote-left',
                'title' => 'Témoignage en attente',
                'message' => ($testimonial->user?->name ?? 'Un étudiant').' a laissé un avis.',
                'time' => $testimonial->created_at,
            ]);
        });

        if (Schema::hasTable('admin_action_requests')) {
            AdminActionRequest::query()
                ->where('status', AdminActionStatus::Pending)
                ->with('requester:id,name')
                ->latest()
                ->limit(4)
                ->get()
                ->each(function (AdminActionRequest $request) use ($items) {
                    $items->push([
                        'type' => 'approval',
                        'icon' => 'fa-clipboard-check',
                        'title' => 'Demande d\'approbation',
                        'message' => $request->title.' · par '.$request->requester?->name,
                        'time' => $request->created_at,
                    ]);
                });
        }

        return $items->sortByDesc('time')->take($limit)->values();
    }
}
