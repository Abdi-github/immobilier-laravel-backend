<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

enum SupportedLanguage: string
{
    case EN = 'en';
    case FR = 'fr';
    case DE = 'de';
    case IT = 'it';

    public function label(): string
    {
        return match ($this) {
            self::EN => __('languages.english'),
            self::FR => __('languages.french'),
            self::DE => __('languages.german'),
            self::IT => __('languages.italian'),
        };
    }
}
