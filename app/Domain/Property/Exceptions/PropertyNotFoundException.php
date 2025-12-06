<?php

declare(strict_types=1);

namespace App\Domain\Property\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

class PropertyNotFoundException extends DomainException
{
    public function __construct(int|string $identifier = '')
    {
        parent::__construct(
            message: "Property not found" . ($identifier ? ": {$identifier}" : ''),
            statusCode: 404,
            errorCode: 'BIZ_PROPERTY_NOT_FOUND',
        );
    }
}
