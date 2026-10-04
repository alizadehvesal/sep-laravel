<?php

declare(strict_types=1);

namespace Vestra\Sep\DTO;

final readonly class TransactionDetail
{
    public function __construct(
        public ?string $rrn,
        public ?string $refNum,
        public ?string $maskedPan,
        public ?string $hashedPan,
        public ?int $terminalNumber,
        public ?int $originalAmount,
        public ?int $affectiveAmount,
        public ?string $straceDate,
        public ?string $straceNo,
        public array $raw,
    ) {}

    /** @param array<string,mixed>|null $data */
    public static function fromArray(?array $data): self
    {
        $data ??= [];
        return new self(
            self::str($data, 'RRN'),
            self::str($data, 'RefNum'),
            self::str($data, 'MaskedPan'),
            self::str($data, 'HashedPan'),
            self::int($data, 'TerminalNumber'),
            self::int($data, 'OrginalAmount'),
            self::int($data, 'AffectiveAmount'),
            self::str($data, 'StraceDate'),
            self::str($data, 'StraceNo'),
            $data,
        );
    }

    private static function str(array $data, string $key): ?string
    {
        return isset($data[$key]) && $data[$key] !== '' ? (string) $data[$key] : null;
    }

    private static function int(array $data, string $key): ?int
    {
        return isset($data[$key]) && is_numeric($data[$key]) ? (int) $data[$key] : null;
    }
}
