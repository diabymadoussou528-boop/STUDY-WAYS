<?php

namespace App\Enums;

enum LessonType: string
{
    case Video = 'video';
    case Text = 'text';
    case Resource = 'resource';

    public function label(): string
    {
        return match ($this) {
            self::Video => 'Vidéo',
            self::Text => 'Texte',
            self::Resource => 'Ressource',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Video => 'fa-play-circle',
            self::Text => 'fa-file-lines',
            self::Resource => 'fa-file-pdf',
        };
    }
}
