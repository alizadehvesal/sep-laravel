<?php

declare(strict_types=1);

namespace Vestra\Sep\Contracts;

use Vestra\Sep\Models\SepPayment;

interface PaymentRepository
{
    public function createPending(int|string $orderId, int $amount, string $resNum, array $meta = []): SepPayment;
    public function findByResNum(string $resNum): ?SepPayment;
    public function findByRefNum(string $refNum): ?SepPayment;
    public function markTokenCreated(SepPayment $payment, string $token): void;
    public function markCallback(SepPayment $payment, array $callback): void;
    public function markVerified(SepPayment $payment, array $verify): void;
    public function markFailed(SepPayment $payment, string $reason, array $context = []): void;
    public function markReversed(SepPayment $payment, array $reverse): void;
    public function isRefNumConsumed(string $refNum): bool;
}
