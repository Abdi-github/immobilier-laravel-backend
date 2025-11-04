<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Enums;

enum AmenityGroup: string
{
    case GENERAL = 'general';
    case KITCHEN = 'kitchen';
    case BATHROOM = 'bathroom';
    case OUTDOOR = 'outdoor';
    case SECURITY = 'security';
    case PARKING = 'parking';
    case ACCESSIBILITY = 'accessibility';
    case ENERGY = 'energy';
    case OTHER = 'other';
}
