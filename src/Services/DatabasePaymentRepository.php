<?php

declare(strict_types=1);

namespace Vestra\Sep\Services;

use Vestra\Sep\Contracts\PaymentRepository;
use Vestra\Sep\Models\SepPayment;

final class DatabasePaymentRepository implements PaymentRepository
{
    public function __construct(private readonly string $table) {}

    public function createPending(int|string $orderId, int $amount, string $resNum, array $meta = []): SepPayment
    {
        return SepPayment::query()->create([
            'order_id' => (string) $orderId,
            'amount' => $amount,
            'res_num' => $resNum,
            'status' => 'pending',
            'meta' => $meta,
        ]);
    }

    public function findByResNum(string $resNum): ?SepPayment
    {
        return SepPayment::query()->where('res_num', $resNum)->first();
    }

    public function findByRefNum(string $refNum): ?SepPayment
    {
        return SepPayment::query()->where('ref_num', $refNum)->first();
    }

    public function markTokenCreated(SepPayment $payment, string $token): void
    {
        $payment->forceFill(['token' => $token, 'status' => 'token_created'])->save();
    }

    public function markCallback(SepPayment $payment, array $callback): void
    {
        $payment->forceFill([
            'status' => $payment->status === 'paid' ? 'paid' : 'callback_received',
            'ref_num' => isset($callback['RefNum']) ? (string) $callback['RefNum'] : $payment->ref_num,
            'rrn' => isset($callback['RRN']) ? (string) $callback['RRN'] : $payment->rrn,
            'trace_no' => isset($callback['TraceNo']) ? (string) $callback['TraceNo'] : $payment->trace_no,
            'state' => isset($callback['State']) ? (string) $callback['State'] : null,
            'gateway_status' => isset($callback['Status']) ? (string) $callback['Status'] : null,
            'callback_payload' => $callback,
        ])->save();
    }

    public function markVerified(SepPayment $payment, array $verify): void
    {
        $detail = is_array($verify['TransactionDetail'] ?? null) ? $verify['TransactionDetail'] : [];
        $success = (bool) ($verify['Success'] ?? false) && (int) ($verify['ResultCode'] ?? -999) === 0;

        $payment->forceFill([
            'status' => $success ? 'paid' : 'verify_failed',
            'ref_num' => isset($detail['RefNum']) ? (string) $detail['RefNum'] : $payment->ref_num,
            'rrn' => isset($detail['RRN']) ? (string) $detail['RRN'] : $payment->rrn,
            'trace_no' => isset($detail['StraceNo']) ? (string) $detail['StraceNo'] : $payment->trace_no,
            'masked_pan' => isset($detail['MaskedPan']) ? (string) $detail['MaskedPan'] : $payment->masked_pan,
            'hashed_pan' => isset($detail['HashedPan']) ? (string) $detail['HashedPan'] : $payment->hashed_pan,
            'verified_amount' => isset($detail['OrginalAmount']) && is_numeric($detail['OrginalAmount']) ? (int) $detail['OrginalAmount'] : null,
            'verify_result_code' => isset($verify['ResultCode']) ? (int) $verify['ResultCode'] : null,
            'verify_result_description' => isset($verify['ResultDescription']) ? (string) $verify['ResultDescription'] : null,
            'verify_payload' => $verify,
            'verified_at' => $success ? now() : $payment->verified_at,
        ])->save();
    }

    public function markFailed(SepPayment $payment, string $reason, array $context = []): void
    {
        $payment->forceFill([
            'status' => 'failed',
            'failure_reason' => $reason,
            'failure_context' => $context,
        ])->save();
    }

    public function markReversed(SepPayment $payment, array $reverse): void
    {
        $payment->forceFill([
            'status' => 'reversed',
            'reverse_payload' => $reverse,
            'reversed_at' => now(),
        ])->save();
    }

    public function isRefNumConsumed(string $refNum): bool
    {
        return SepPayment::query()
            ->where('ref_num', $refNum)
            ->whereIn('status', ['paid', 'reversed'])
            ->exists();
    }
}
