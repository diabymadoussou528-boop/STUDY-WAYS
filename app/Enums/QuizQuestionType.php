<?php

namespace App\Enums;

enum QuizQuestionType: string
{
    case MultipleChoice = 'multiple_choice';
    case TrueFalse = 'true_false';
    case ShortAnswer = 'short_answer';
    case Essay = 'essay';

    public function label(): string
    {
        return match ($this) {
            self::MultipleChoice => 'Choix multiple',
            self::TrueFalse => 'Vrai / Faux',
            self::ShortAnswer => 'Réponse courte',
            self::Essay => 'Dissertation',
        };
    }

    public function isAutoGraded(): bool
    {
        return $this !== self::Essay;
    }
}
