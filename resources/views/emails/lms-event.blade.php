<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }} — StudyWays</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f7;font-family:Inter,Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f7;padding:32px 16px;">
    <tr>
        <td align="center">
            <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 8px 28px rgba(0,0,0,.08);">
                @include('emails.partials.brand-header', ['subtitle' => 'Notification LMS'])
                <tr>
                    <td style="padding:32px 40px;">
                        <p style="margin:0 0 8px;color:#6b7280;font-size:14px;">Bonjour {{ $user->name }},</p>
                        <h1 style="margin:0 0 16px;color:#111827;font-size:22px;">{{ $title }}</h1>
                        @if($body)
                            <p style="margin:0 0 24px;color:#374151;font-size:15px;line-height:1.7;">{{ $body }}</p>
                        @endif
                        @if($actionUrl)
                            <a href="{{ $actionUrl }}" style="display:inline-block;background:#8B2032;color:#ffffff;text-decoration:none;padding:12px 24px;border-radius:10px;font-weight:600;font-size:14px;">
                                {{ $actionLabel }}
                            </a>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="padding:20px 40px;background:#faf9fb;color:#9ca3af;font-size:12px;text-align:center;">
                        StudyWays — Plateforme e-learning
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
