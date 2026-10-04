<?php

declare(strict_types=1);

namespace Vestra\Sep\Exceptions;

class SepApiException extends SepException
{
    public function __construct(
        string $message,
        public readonly ?string $errorCode = null,
        public readonly ?int $resultCode = null,
        public readonly array $response = [],
    ) {
        parent::__construct($message);
    }
}
