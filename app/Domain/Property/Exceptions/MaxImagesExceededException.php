<?php

declare(strict_types=1);

namespace App\Domain\Property\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

class MaxImagesExceededException extends DomainException
{
    public function __construct(int $max = 50)
    {
        parent::__construct(
            message: "Maximum number of images ({$max}) exceeded",
            statusCode: 422,
        );
    }
}
