<?php

declare(strict_types=1);

namespace App\Domain\Property\Enums;

enum TranslationSource: string
{
    case ORIGINAL = 'original';
    case DEEPL = 'deepl';
    case LIBRETRANSLATE = 'libretranslate';
    case HUMAN = 'human';

    public function label(): string
    {
        return match ($this) {
            self::ORIGINAL => __('translation_sources.original'),
            self::DEEPL => __('translation_sources.deepl'),
            self::LIBRETRANSLATE => __('translation_sources.libretranslate'),
            self::HUMAN => __('translation_sources.human'),
        };
    }
}
