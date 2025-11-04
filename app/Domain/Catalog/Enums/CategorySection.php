<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Enums;

enum CategorySection: string
{
    case RESIDENTIAL = 'residential';
    case COMMERCIAL = 'commercial';
    case LAND = 'land';
    case PARKING = 'parking';
    case SPECIAL = 'special';
}
