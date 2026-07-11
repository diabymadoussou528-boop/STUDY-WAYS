<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LmsEventNotification extends Notification
{
    /**
     * @param  array<string, mixed>|null  $data
     */
    public function __construct(
        public string $type,
        public string $title,
        public ?string $body = null,
        public ?array $data = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $actionUrl = $this->resolveActionUrl();

        return (new MailMessage)
            ->subject($this->title.' — StudyWays')
            ->view('emails.lms-event', [
                'user' => $notifiable,
                'type' => $this->type,
                'title' => $this->title,
                'body' => $this->body,
                'actionUrl' => $actionUrl,
                'actionLabel' => $this->actionLabel(),
                'logoUrl' => url('/images/logo.png'),
            ]);
    }

    private function resolveActionUrl(): ?string
    {
        $data = $this->data ?? [];

        if (isset($data['payment_id'])) {
            return route('payments.receipt', $data['payment_id']);
        }

        if (isset($data['course_id'])) {
            return route('courses.show', $data['course_id']);
        }

        if (isset($data['quiz_id'])) {
            return route('student.quizzes.index');
        }

        if (isset($data['attempt_id'])) {
            return route('student.quizzes.result', $data['attempt_id']);
        }

        return route('dashboard');
    }

    private function actionLabel(): string
    {
        return match ($this->type) {
            'payment_received' => 'Voir le reçu',
            'new_enrollment' => 'Voir le cours',
            'quiz_completed', 'quiz_graded' => 'Voir le résultat',
            'enrollment_confirmed' => 'Accéder au cours',
            default => 'Ouvrir StudyWays',
        };
    }
}
