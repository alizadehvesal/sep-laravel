<?php

declare(strict_types=1);

namespace Vestra\Sep\Services;

use Illuminate\Database\DatabaseManager;
use Vestra\Sep\Contracts\PaymentRepository;
use Vestra\Sep\DTO\CallbackData;
use Vestra\Sep\DTO\PaymentResult;
use Vestra\Sep\DTO\TokenRequest;
use Vestra\Sep\DTO\TransactionResponse;
use Vestra\Sep\Exceptions\InvalidCallbackException;
use Vestra\Sep\Exceptions\PaymentAlreadyProcessedException;
use Vestra\Sep\Models\SepPayment;

final class SepPaymentService
{
    public function __construct(
        private readonly SepGateway $gateway,
        private readonly PaymentRepository $payments,
        private readonly DatabaseManager $db,
        private readonly int $verifyRetries = 3,
        private readonly int $verifyRetryDelayMs = 1000,
    ) {}

    public function createPayment(
        int|string $orderId,
        int $amountRial,
        string $resNum,
        string $redirectUrl,
        array $meta = [],
        ?string $cellNumber = null,
        ?int $tokenExpiryInMin = null,
        ?int $wage = null,
        ?string $hashedCardNumber = null,
    ): SepPayment {
        $payment = $this->payments->createPending($orderId, $amountRial, $resNum, $meta);

        try {
            $token = $this->gateway->requestToken(new TokenRequest(
                amount: $amountRial,
                resNum: $resNum,
                redirectUrl: $redirectUrl,
                cellNumber: $cellNumber,
                tokenExpiryInMin: $tokenExpiryInMin,
                wage: $wage,
                hashedCardNumber: $hashedCardNumber,
            ));

            $this->payments->markTokenCreated($payment, $token->token);
        } catch (\Throwable $e) {
            $this->payments->markFailed($payment, $e->getMessage());
            throw $e;
        }

        return $payment->fresh();
    }

    public function processCallback(CallbackData $callback): PaymentResult
    {
        if ($callback->resNum === null) {
            throw new InvalidCallbackException('Missing ResNum.');
        }

        $payment = $this->payments->findByResNum($callback->resNum);
        if (!$payment) {
            throw new InvalidCallbackException('No local payment exists for the received ResNum.');
        }

        $this->payments->markCallback($payment, $callback->raw);

        if (!$callback->successfulState() || $callback->refNum === null) {
            $this->payments->markFailed($payment, $callback->state ?: 'SEP transaction was not successful.', $callback->raw);
            return new PaymentResult(false, 'failed', 'پرداخت توسط درگاه موفق اعلام نشد.', $callback, null, $callback->resNum, $callback->refNum);
        }

        if ($payment->status === 'paid' && $payment->ref_num === $callback->refNum) {
            return new PaymentResult(true, 'paid', 'این Callback قبلاً با موفقیت پردازش شده است.', $callback, null, $callback->resNum, $callback->refNum);
        }

        $consumed = $this->payments->findByRefNum($callback->refNum);
        if ($consumed && $consumed->getKey() !== $payment->getKey() && $consumed->status === 'paid') {
            throw new PaymentAlreadyProcessedException('The SEP RefNum is already consumed by another payment.');
        }

        $verification = $this->verifyWithRetry($callback->refNum);

        if (!$verification->successful()) {
            $this->payments->markFailed($payment, $verification->resultDescription ?: 'SEP verification failed.', $verification->raw);
            return new PaymentResult(false, 'verify_failed', $verification->resultDescription ?: 'تأیید پرداخت ناموفق بود.', $callback, $verification, $callback->resNum, $callback->refNum);
        }

        $this->gateway->assertAmountMatches($payment->amount, $verification);

        $this->db->transaction(function () use ($payment, $callback, $verification): void {
            $fresh = $payment->newQuery()->lockForUpdate()->find($payment->getKey());
            if (!$fresh) {
                throw new InvalidCallbackException('Payment disappeared while finalizing transaction.');
            }
            if ($fresh->status === 'paid') {
                return;
            }
            $existing = $this->payments->findByRefNum($callback->refNum);
            if ($existing && $existing->getKey() !== $fresh->getKey()) {
                throw new PaymentAlreadyProcessedException('The SEP RefNum is already associated with another payment.');
            }
            $this->payments->markVerified($fresh, $verification->raw);
        });

        return new PaymentResult(true, 'paid', 'پرداخت با موفقیت تأیید شد.', $callback, $verification, $callback->resNum, $callback->refNum);
    }

    public function reverse(string $refNum): TransactionResponse
    {
        return $this->gateway->reverse($refNum);
    }

    public function reversePayment(SepPayment $payment): TransactionResponse
    {
        if (!$payment->ref_num) {
            throw new InvalidCallbackException('Cannot reverse a payment without RefNum.');
        }

        $result = $this->gateway->reverse($payment->ref_num);
        if ($result->successful()) {
            $this->payments->markReversed($payment, $result->raw);
        }

        return $result;
    }

    private function verifyWithRetry(string $refNum): TransactionResponse
    {
        $attempts = max(1, $this->verifyRetries);
        $lastTransportException = null;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                return $this->gateway->verify($refNum);
            } catch (\Vestra\Sep\Exceptions\SepTransportException $e) {
                $lastTransportException = $e;
                if ($attempt < $attempts) {
                    usleep(max(0, $this->verifyRetryDelayMs) * 1000);
                }
            }
        }

        throw $lastTransportException ?? new \Vestra\Sep\Exceptions\SepTransportException('SEP verification failed without a response.');
    }
}
