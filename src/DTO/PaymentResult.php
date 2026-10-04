<?php

declare(strict_types=1);

namespace Vestra\Sep\DTO;

final readonly class PaymentResult
{
    public function __construct(
        public bool $paid,
        public string $status,
        public string $message,
        public ?CallbackData $callback,
        public ?TransactionResponse $verification,
        public ?string $resNum,
        public ?string $refNum,
    ) {}
}
