<?php

declare(strict_types=1);

namespace Vestra\Sep\DTO;

use Illuminate\Http\Request;

final readonly class CallbackData
{
    public function __construct(
        public ?string $token,
        public ?string $resNum,
        public ?string $refNum,
        public ?string $traceNo,
        public ?string $state,
        public ?string $status,
        public ?string $terminalId,
        public ?string $mid,
        public ?string $rrn,
        public ?int $amount,
        public ?int $wage,
        public ?int $affectiveAmount,
        public ?string $securePan,
        public ?string $hashedCardNumber,
        public array $raw,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $input = $request->all();

        return self::fromArray($input);
    }

    /** @param array<string,mixed> $input */
    public static function fromArray(array $input): self
    {
        return new self(
            self::string($input, 'Token'),
            self::string($input, 'ResNum'),
            self::string($input, 'RefNum'),
            self::string($input, 'TraceNo'),
            self::string($input, 'State'),
            self::string($input, 'Status'),
            self::string($input, 'TerminalId'),
            self::string($input, 'MID'),
            self::string($input, 'RRN'),
            self::int($input, 'Amount'),
            self::int($input, 'Wage'),
            self::int($input, 'AffectiveAmount'),
            self::string($input, 'SecurePan'),
            self::string($input, 'HashedCardNumber'),
            $input,
        );
    }

    public function successfulState(): bool
    {
        return strtoupper((string) $this->state) === 'OK';
    }

    private static function string(array $input, string $key): ?string
    {
        return isset($input[$key]) && $input[$key] !== '' ? (string) $input[$key] : null;
    }

    private static function int(array $input, string $key): ?int
    {
        return isset($input[$key]) && $input[$key] !== '' && is_numeric($input[$key]) ? (int) $input[$key] : null;
    }
}
