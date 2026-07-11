<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SimpleAdminWelcomeNotification extends Notification
{
    public function __construct(
        public string $temporaryPassword,
        public bool $isReset = false,
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
        $subject = $this->isReset
            ? 'Nouveau mot de passe temporaire — StudyWays Admin'
            : 'Bienvenue sur StudyWays — Vos accès administrateur';

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.simple-admin-welcome', [
                'user' => $notifiable,
                'temporaryPassword' => $this->temporaryPassword,
                'loginUrl' => route('login'),
                'logoUrl' => url('/images/logo.png'),
                'isReset' => $this->isReset,
            ]);
    }
}
