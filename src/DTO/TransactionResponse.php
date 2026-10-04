<?php

declare(strict_types=1);

namespace Vestra\Sep\DTO;

final readonly class TransactionResponse
{
    public function __construct(
        public bool $success,
        public int $resultCode,
        public ?string $resultDescription,
        public TransactionDetail $detail,
        public array $raw,
    ) {}

    /** @param array<string,mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            filter_var($data['Success'] ?? false, FILTER_VALIDATE_BOOL),
            (int) ($data['ResultCode'] ?? -999999),
            isset($data['ResultDescription']) ? (string) $data['ResultDescription'] : null,
            TransactionDetail::fromArray(isset($data['TransactionDetail']) && is_array($data['TransactionDetail']) ? $data['TransactionDetail'] : null),
            $data,
        );
    }

    public function successful(): bool
    {
        return $this->success && $this->resultCode === 0;
    }
}
