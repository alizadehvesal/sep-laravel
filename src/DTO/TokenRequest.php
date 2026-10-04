<?php

declare(strict_types=1);

namespace Vestra\Sep\DTO;

final readonly class TokenRequest
{
    public function __construct(
        public int $amount,
        public string $resNum,
        public string $redirectUrl,
        public ?string $cellNumber = null,
        public ?int $tokenExpiryInMin = null,
        public ?int $wage = null,
        public ?string $hashedCardNumber = null,
        public ?string $resNum1 = null,
        public ?string $resNum2 = null,
        public ?string $resNum3 = null,
        public ?string $resNum4 = null,
    ) {}

    /** @return array<string,mixed> */
    public function toArray(string $terminalId): array
    {
        $data = [
            'Action' => 'Token',
            'TerminalId' => $terminalId,
            'Amount' => $this->amount,
            'ResNum' => $this->resNum,
            'RedirectUrl' => $this->redirectUrl,
        ];

        foreach ([
            'Wage' => $this->wage,
            'CellNumber' => $this->cellNumber,
            'TokenExpiryInMin' => $this->tokenExpiryInMin,
            'HashedCardNumber' => $this->hashedCardNumber,
            'ResNum1' => $this->resNum1,
            'ResNum2' => $this->resNum2,
            'ResNum3' => $this->resNum3,
            'ResNum4' => $this->resNum4,
        ] as $key => $value) {
            if ($value !== null && $value !== '') {
                $data[$key] = $value;
            }
        }

        return $data;
    }
}
