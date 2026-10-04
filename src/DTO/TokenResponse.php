<?php

declare(strict_types=1);

namespace Vestra\Sep\DTO;

use Vestra\Sep\Exceptions\SepApiException;

final readonly class TokenResponse
{
    public function __construct(
        public int $status,
        public ?string $token,
        public ?string $errorCode,
        public ?string $errorDescription,
        public array $raw,
    ) {}

    public function successful(): bool
    {
        return $this->status === 1 && $this->token !== null && $this->token !== '';
    }

    /** @param array<string,mixed> $data */
    public static function fromArray(array $data): self
    {
        $status = (int) ($data['status'] ?? -1);
        $token = isset($data['token']) ? (string) $data['token'] : null;
        $errorCode = isset($data['errorCode']) ? (string) $data['errorCode'] : null;
        $errorDescription = isset($data['errorDesc']) ? (string) $data['errorDesc'] : null;

        return new self($status, $token, $errorCode, $errorDescription, $data);
    }

    public function throwIfFailed(): void
    {
        if (!$this->successful()) {
            throw new SepApiException(
                $this->errorDescription ?: 'SEP token request failed.',
                $this->errorCode,
                null,
                $this->raw,
            );
        }
    }
}
