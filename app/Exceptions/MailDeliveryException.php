<?php

namespace App\Exceptions;

use Exception;

class MailDeliveryException extends Exception
{
    public static function fromConfigurationIssue(string $detail): self
    {
        return new self(
            "Impossible d'envoyer l'e-mail : {$detail} Vérifiez les variables MAIL_* dans votre fichier .env."
        );
    }

    public static function fromTransportFailure(): self
    {
        return new self(
            "Impossible d'envoyer l'e-mail. Vérifiez la configuration MAIL_* dans votre fichier .env et assurez-vous que le serveur SMTP est accessible."
        );
    }
}
