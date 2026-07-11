@php
    $logoUrl = $logoUrl ?? url('/images/logo.png');
@endphp

<tr>
    <td style="background:linear-gradient(135deg, #8b2032 0%, #6b1826 100%); padding:32px 40px; text-align:center;">
        <img src="{{ $logoUrl }}" alt="StudyWays" width="200" style="display:block; margin:0 auto 10px; max-width:200px; height:auto;">
        @if(! empty($subtitle))
            <div style="font-size:12px; color:rgba(255,255,255,0.85); letter-spacing:1px; text-transform:uppercase; font-weight:600;">
                {{ $subtitle }}
            </div>
        @endif
    </td>
</tr>
