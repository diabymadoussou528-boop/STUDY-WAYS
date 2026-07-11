<?php

namespace App\Http\View\Composers;

use App\Services\MessagingService;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class StudentSidebarComposer
{
    public function __construct(private MessagingService $messaging) {}

    public function compose(View $view): void
    {
        $user = auth()->user();

        $view->with('unreadMessages', $user && Schema::hasTable('messages')
            ? $this->messaging->unreadCount($user)
            : 0);
    }
}
