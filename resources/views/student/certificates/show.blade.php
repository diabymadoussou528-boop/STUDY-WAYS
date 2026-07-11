<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificat — {{ $courseTitle }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: #f5f0f1;
            color: #111;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 24px;
        }
        .toolbar {
            width: min(920px, 100%);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            gap: 12px;
        }
        .toolbar a, .toolbar button {
            font: inherit;
            border: 1px solid #ddd;
            background: #fff;
            border-radius: 10px;
            padding: 10px 16px;
            cursor: pointer;
            text-decoration: none;
            color: #111;
        }
        .toolbar .btn-primary {
            background: #8B2032;
            border-color: #8B2032;
            color: #fff;
        }
        .certificate {
            width: min(920px, 100%);
            aspect-ratio: 1.414 / 1;
            background: #fff;
            border: 12px solid #8B2032;
            outline: 3px solid #111;
            outline-offset: -18px;
            position: relative;
            padding: 48px 56px;
            box-shadow: 0 24px 60px rgba(139, 32, 50, 0.15);
        }
        .certificate::before {
            content: '';
            position: absolute;
            inset: 24px;
            border: 1px solid rgba(139, 32, 50, 0.25);
            pointer-events: none;
        }
        .brand {
            text-align: center;
            letter-spacing: .28em;
            font-size: .78rem;
            font-weight: 700;
            color: #8B2032;
            text-transform: uppercase;
        }
        .title {
            font-family: 'Playfair Display', serif;
            text-align: center;
            font-size: clamp(1.8rem, 4vw, 2.6rem);
            margin: 18px 0 8px;
            color: #111;
        }
        .subtitle {
            text-align: center;
            color: #666;
            font-size: .95rem;
            margin-bottom: 28px;
        }
        .recipient {
            text-align: center;
            font-family: 'Playfair Display', serif;
            font-size: clamp(1.6rem, 3.5vw, 2.2rem);
            color: #8B2032;
            margin: 12px 0 20px;
            border-bottom: 2px solid rgba(139, 32, 50, 0.2);
            display: inline-block;
            width: 100%;
            padding-bottom: 12px;
        }
        .body-text {
            text-align: center;
            line-height: 1.7;
            font-size: 1rem;
            max-width: 620px;
            margin: 0 auto 28px;
            color: #333;
        }
        .course-name {
            font-weight: 700;
            color: #111;
        }
        .meta {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 16px;
            margin-top: 36px;
            border-top: 1px solid #eee;
            padding-top: 24px;
        }
        .meta-block label {
            display: block;
            font-size: .72rem;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: #888;
            margin-bottom: 6px;
        }
        .meta-block span {
            font-size: .92rem;
            font-weight: 600;
        }
        .seal {
            position: absolute;
            bottom: 42px;
            right: 56px;
            width: 88px;
            height: 88px;
            border-radius: 50%;
            border: 3px solid #8B2032;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            font-size: .62rem;
            font-weight: 700;
            letter-spacing: .08em;
            color: #8B2032;
            text-transform: uppercase;
            line-height: 1.3;
            background: rgba(139, 32, 50, 0.04);
        }
        @media print {
            body { background: #fff; padding: 0; }
            .toolbar { display: none; }
            .certificate { box-shadow: none; width: 100%; max-width: none; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <a href="{{ route('student.certificates.index') }}">&larr; Mes certificats</a>
        <button type="button" class="btn-primary" onclick="window.print()">Imprimer / PDF</button>
    </div>

    <article class="certificate">
        <div class="brand">StudyWays</div>
        <h1 class="title">Certificat de réussite</h1>
        <p class="subtitle">Ce certificat atteste que</p>

        <div class="recipient">{{ $studentName }}</div>

        <p class="body-text">
            a complété avec succès le cours
            <span class="course-name">« {{ $courseTitle }} »</span>
            dispensé par <strong>{{ $professorName }}</strong>,
            conformément aux exigences pédagogiques de la plateforme StudyWays.
        </p>

        <div class="meta">
            <div class="meta-block">
                <label>Date d'émission</label>
                <span>{{ $issuedAt->translatedFormat('d F Y') }}</span>
            </div>
            <div class="meta-block">
                <label>N° certificat</label>
                <span>{{ $certificateNumber }}</span>
            </div>
            <div class="meta-block">
                <label>Plateforme</label>
                <span>StudyWays LMS</span>
            </div>
        </div>

        <div class="seal">StudyWays<br>Officiel</div>

        @if(!empty($qrCodeUrl))
            <img src="{{ $qrCodeUrl }}" alt="QR vérification" style="position:absolute;bottom:42px;left:56px;width:100px;height:100px;border:1px solid #eee;border-radius:8px;background:#fff;padding:6px;">
        @endif

        <div style="position:absolute;bottom:48px;left:180px;font-size:.75rem;color:#666;max-width:220px;">
            Scannez le QR code pour vérifier l'authenticité de ce certificat.
        </div>
    </article>
</body>
</html>
