<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Réinitialisation du mot de passe</title>
</head>
<body style="margin:0; padding:0; background-color:#f3f4f7; font-family:'Segoe UI', Helvetica, Arial, sans-serif; color:#111827;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f7; padding:32px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:600px; max-width:92%; background-color:#ffffff; border-radius:18px; overflow:hidden; box-shadow:0 10px 40px rgba(0,0,0,0.08);">

                    @include('emails.partials.brand-header', ['subtitle' => "Plateforme d'apprentissage"])

                    <!-- Body -->
                    <tr>
                        <td style="padding:40px 40px 16px;">
                            <h1 style="margin:0 0 18px; font-size:21px; font-weight:800; color:#111827;">
                                Bonjour {{ $user->name }},
                            </h1>
                            <p style="margin:0 0 16px; font-size:15px; line-height:1.7; color:#4b5563;">
                                Nous avons reçu une demande de réinitialisation du mot de passe de votre compte StudyWays.
                                Cliquez sur le bouton ci-dessous pour choisir un nouveau mot de passe.
                            </p>
                        </td>
                    </tr>

                    <!-- Button -->
                    <tr>
                        <td align="center" style="padding:8px 40px 28px;">
                            <table role="presentation" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="border-radius:12px; background:linear-gradient(135deg, #8b2032 0%, #6b1826 100%);">
                                        <a href="{{ $url }}" target="_blank"
                                           style="display:inline-block; padding:15px 38px; font-size:15px; font-weight:700; color:#ffffff; text-decoration:none; border-radius:12px;">
                                            Réinitialiser le mot de passe
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Expiration notice -->
                    <tr>
                        <td style="padding:0 40px 28px;">
                            <div style="background-color:#faf0f2; border-left:4px solid #8b2032; border-radius:8px; padding:14px 18px;">
                                <p style="margin:0; font-size:13px; line-height:1.6; color:#6b1826;">
                                    <strong>Lien sécurisé&nbsp;:</strong> ce lien expirera dans {{ $count }} minutes.
                                    Si vous n'avez pas demandé de réinitialisation, ignorez simplement cet e-mail —
                                    votre mot de passe restera inchangé.
                                </p>
                            </div>
                        </td>
                    </tr>

                    <!-- Fallback link -->
                    <tr>
                        <td style="padding:0 40px 36px;">
                            <p style="margin:0 0 6px; font-size:12px; color:#9ca3af;">
                                Si le bouton ne fonctionne pas, copiez-collez ce lien dans votre navigateur&nbsp;:
                            </p>
                            <p style="margin:0; font-size:12px; word-break:break-all;">
                                <a href="{{ $url }}" style="color:#8b2032; text-decoration:underline;">{{ $url }}</a>
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color:#0a0a0a; padding:24px 40px; text-align:center;">
                            <p style="margin:0; font-size:12px; color:rgba(255,255,255,0.6);">
                                &copy; {{ date('Y') }} StudyWays. Tous droits réservés.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
