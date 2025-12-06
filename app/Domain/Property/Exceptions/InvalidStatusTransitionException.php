<?php

declare(strict_types=1);

namespace App\Domain\Property\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

class InvalidStatusTransitionException extends DomainException
{
    public function __construct(string $from, string $to)
    {
        parent::__construct(
            message: "Invalid status transition from '{$from}' to '{$to}'",
            statusCode: 422,
        );
    }
}
