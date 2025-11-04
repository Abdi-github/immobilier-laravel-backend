<?php

declare(strict_types=1);

namespace App\Domain\Property\Enums;

enum ImageSource: string
{
    case CLOUDINARY = 'cloudinary';
    case EXTERNAL = 'external';
    case LOCAL = 'local';

    public function label(): string
    {
        return match ($this) {
            self::CLOUDINARY => __('image_sources.cloudinary'),
            self::EXTERNAL => __('image_sources.external'),
            self::LOCAL => __('image_sources.local'),
        };
    }
}
