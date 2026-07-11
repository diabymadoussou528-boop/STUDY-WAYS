<?php

namespace App\Services;

use App\Exceptions\MailDeliveryException;

class MailConfigValidator
{
    /**
     * @throws MailDeliveryException
     */
    public function ensureConfigured(): void
    {
        $defaultMailer = config('mail.default');

        if (blank($defaultMailer)) {
            throw MailDeliveryException::fromConfigurationIssue('aucun transport mail par défaut n\'est défini.');
        }

        if (! app()->environment('testing') && in_array($defaultMailer, ['log', 'array'], true)) {
            throw MailDeliveryException::fromConfigurationIssue(
                'MAIL_MAILER est réglé sur « '.$defaultMailer.' ». Définissez MAIL_MAILER=smtp dans votre fichier .env pour envoyer de vrais e-mails.'
            );
        }

        if (blank(config('mail.from.address'))) {
            throw MailDeliveryException::fromConfigurationIssue('MAIL_FROM_ADDRESS est manquant.');
        }

        if ($defaultMailer !== 'smtp') {
            return;
        }

        if (blank(config('mail.mailers.smtp.host'))) {
            throw MailDeliveryException::fromConfigurationIssue('MAIL_HOST est manquant pour le transport SMTP.');
        }

        if (blank(config('mail.mailers.smtp.username'))) {
            throw MailDeliveryException::fromConfigurationIssue('MAIL_USERNAME est manquant pour le transport SMTP.');
        }

        if (blank(config('mail.mailers.smtp.password'))) {
            throw MailDeliveryException::fromConfigurationIssue('MAIL_PASSWORD est manquant pour le transport SMTP.');
        }
    }
}
