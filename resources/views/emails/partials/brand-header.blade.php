@php
    $logoUrl = $logoUrl ?? null;
@endphp
<div style="text-align:center; padding: 24px 0 8px;">
    @if($logoUrl)
        <img src="{{ $logoUrl }}" alt="StudyWays" width="200" style="display:block; margin:0 auto 10px; max-width:200px; height:auto;">
    @else
        <div style="font-family: Georgia, 'Times New Roman', serif; color:#8B2032; letter-spacing:0.07em; text-transform:uppercase; font-size:22px; font-weight:700;">
            Study<span style="font-size:16px;">🎓</span>
        </div>
        <div style="font-family: 'Segoe Script', 'Brush Script MT', cursive; color:#8B2032; font-size:28px; font-weight:700; margin-top:-6px;">
            Ways
        </div>
    @endif
    <p style="margin:8px 0 0; color:#6b7280; font-size:13px;">Plateforme e-learning StudyWays</p>
</div>
