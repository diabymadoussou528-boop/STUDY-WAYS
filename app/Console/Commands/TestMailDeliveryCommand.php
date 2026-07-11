<?php

namespace App\Console\Commands;

use App\Exceptions\MailDeliveryException;
use App\Services\MailConfigValidator;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

#[Signature('mail:test {email : Adresse e-mail du destinataire}')]
#[Description('Envoie un e-mail de test pour vérifier la configuration SMTP du fichier .env')]
class TestMailDeliveryCommand extends Command
{
    public function handle(MailConfigValidator $validator): int
    {
        $email = $this->argument('email');

        try {
            $validator->ensureConfigured();

            Mail::raw(
                "Ceci est un e-mail de test envoyé depuis StudyWays.\n\nSi vous recevez ce message, votre configuration SMTP (.env) fonctionne correctement.",
                function ($message) use ($email): void {
                    $message->to($email)
                        ->subject('StudyWays — Test de configuration SMTP');
                }
            );

            $this->components->info("E-mail de test envoyé avec succès à {$email}.");

            return self::SUCCESS;
        } catch (MailDeliveryException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        } catch (Throwable $exception) {
            report($exception);
            $this->components->error('Échec de l\'envoi : '.$exception->getMessage());

            return self::FAILURE;
        }
    }
}
