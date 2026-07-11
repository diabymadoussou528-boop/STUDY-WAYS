<?php

namespace App\Enums;

enum CourseStatus: string
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case Published = 'published';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::PendingReview => 'En revue',
            self::Published => 'Publié',
            self::Archived => 'Archivé',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft => 'badge-admin',
            self::PendingReview => 'badge-warning',
            self::Published => 'badge-success',
            self::Archived => 'badge-admin',
        };
    }
}
