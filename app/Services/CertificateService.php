<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class CertificateService
{
    public function __construct(private AuditLogService $auditLog) {}

    /**
     * @return Collection<int, Enrollment>
     */
    public function eligibleEnrollmentsFor(User $student): Collection
    {
        return Enrollment::query()
            ->with(['course.user:id,name', 'course.category:id,name'])
            ->where('user_id', $student->id)
            ->where('certificate_eligible', true)
            ->where(function ($query) {
                $query->where('progress', '>=', 100)
                    ->orWhereNotNull('completed_at');
            })
            ->orderByDesc('completed_at')
            ->get();
    }

    public function isEligible(Enrollment $enrollment): bool
    {
        return $enrollment->certificate_eligible && $enrollment->isCompleted();
    }

    public function canDownload(User $user, Enrollment $enrollment): bool
    {
        if ((int) $enrollment->user_id !== (int) $user->id && ! $user->isAdmin()) {
            return false;
        }

        if (! $this->isEligible($enrollment)) {
            return false;
        }

        return $user->hasActivePremium();
    }

    /**
     * @return array<string, mixed>
     */
    public function issue(User $user, Enrollment $enrollment): array
    {
        abort_unless($this->canDownload($user, $enrollment), 403);

        $enrollment->loadMissing(['user', 'course.user', 'course.category']);

        if (! $enrollment->certificate_number) {
            $enrollment->update([
                'certificate_number' => $this->generateNumber($enrollment),
                'certificate_issued_at' => now(),
                'verification_token' => $enrollment->verification_token ?? Str::random(40),
            ]);
            $enrollment->refresh();

            $this->auditLog->log(
                action: 'certificate.generated',
                module: 'certificates',
                description: 'Certificat émis pour '.$enrollment->course?->title,
                metadata: ['enrollment_id' => $enrollment->id, 'certificate_number' => $enrollment->certificate_number],
                actor: $user,
            );
        }

        $verifyUrl = route('certificates.verify', $enrollment->verification_token);

        return [
            'enrollment' => $enrollment,
            'studentName' => $enrollment->user?->name ?? $user->name,
            'courseTitle' => $enrollment->course?->title ?? 'Cours StudyWays',
            'professorName' => $enrollment->course?->user?->name ?? 'StudyWays',
            'issuedAt' => $enrollment->certificate_issued_at ?? now(),
            'certificateNumber' => $enrollment->certificate_number,
            'verifyUrl' => $verifyUrl,
            'qrCodeUrl' => 'https://api.qrserver.com/v1/create-qr-code/?size=140x140&data='.urlencode($verifyUrl),
        ];
    }

    public function generateNumber(Enrollment $enrollment): string
    {
        $year = ($enrollment->completed_at ?? now())->format('Y');

        return sprintf('SW-%s-%s', $year, str_pad((string) $enrollment->id, 6, '0', STR_PAD_LEFT));
    }

    public function verify(string $token): ?Enrollment
    {
        return Enrollment::query()
            ->with(['user', 'course.user'])
            ->where('verification_token', $token)
            ->where('certificate_eligible', true)
            ->first();
    }
}
