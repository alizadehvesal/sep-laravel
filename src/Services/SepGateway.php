<?php

declare(strict_types=1);

namespace Vestra\Sep\Services;

use Illuminate\Contracts\Routing\UrlGenerator;
use Vestra\Sep\Contracts\SepHttpClient;
use Vestra\Sep\DTO\CallbackData;
use Vestra\Sep\DTO\TokenRequest;
use Vestra\Sep\DTO\TokenResponse;
use Vestra\Sep\DTO\TransactionResponse;
use Vestra\Sep\Exceptions\AmountMismatchException;
use Vestra\Sep\Exceptions\InvalidConfigurationException;
use Vestra\Sep\Exceptions\InvalidCallbackException;
use Vestra\Sep\Exceptions\SepApiException;

final class SepGateway
{
    public function __construct(
        private readonly SepHttpClient $http,
        private readonly UrlGenerator $url,
        private readonly string $terminalId,
        private readonly array $urls,
        private readonly int $defaultTokenExpiryMinutes = 20,
    ) {
        if ($this->terminalId === '') {
            throw new InvalidConfigurationException('SEP_TERMINAL_ID is not configured.');
        }
    }

    public function requestToken(TokenRequest $request): TokenResponse
    {
        $this->assertPositiveAmount($request->amount);
        $this->assertResNum($request->resNum);
        $this->assertRedirectUrl($request->redirectUrl);

        $expiry = $request->tokenExpiryInMin ?? $this->defaultTokenExpiryMinutes;
        $expiry = max(20, min(3600, $expiry));

        $payload = $request->toArray($this->terminalId);
        $payload['TokenExpiryInMin'] = $expiry;

        $response = $this->http->postJson($this->urls['token'], $payload);
        $result = TokenResponse::fromArray($response);
        $result->throwIfFailed();

        return $result;
    }

    public function verify(string $refNum): TransactionResponse
    {
        $this->assertRefNum($refNum);
        $response = $this->http->postJson($this->urls['verify'], [
            'RefNum' => $refNum,
            'TerminalNumber' => (int) $this->terminalId,
        ]);

        return TransactionResponse::fromArray($response);
    }

    public function reverse(string $refNum): TransactionResponse
    {
        $this->assertRefNum($refNum);
        $response = $this->http->postJson($this->urls['reverse'], [
            'RefNum' => $refNum,
            'TerminalNumber' => (int) $this->terminalId,
        ]);

        return TransactionResponse::fromArray($response);
    }

    public function callbackFromArray(array $payload): CallbackData
    {
        $callback = CallbackData::fromArray($payload);

        if ($callback->resNum === null) {
            throw new InvalidCallbackException('SEP callback does not contain ResNum.');
        }

        if ($callback->refNum === null && $callback->successfulState()) {
            throw new InvalidCallbackException('A successful SEP callback must contain RefNum.');
        }

        return $callback;
    }

    public function paymentUrl(string $token): string
    {
        if ($token === '') {
            throw new InvalidConfigurationException('Cannot build SEP payment URL without a token.');
        }

        return rtrim($this->urls['send_token'], '?&').'?token='.rawurlencode($token);
    }

    public function paymentForm(string $token, bool $getMethod = false): string
    {
        if ($token === '') {
            throw new InvalidConfigurationException('Cannot build SEP payment form without a token.');
        }

        $action = htmlspecialchars($this->urls['payment'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $escapedToken = htmlspecialchars($token, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $getMethodValue = $getMethod ? 'true' : '';

        return sprintf(
            '<form id="sep-payment-form" method="POST" action="%s">'.
            '<input type="hidden" name="Token" value="%s">'.
            '<input type="hidden" name="GetMethod" value="%s">'.
            '<noscript><button type="submit">ادامه به درگاه پرداخت</button></noscript>'.
            '</form><script>document.getElementById("sep-payment-form").submit();</script>',
            $action,
            $escapedToken,
            htmlspecialchars($getMethodValue, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        );
    }

    public function callbackUrl(): string
    {
        return $this->url->to(config('sep.callback_route'));
    }

    public function assertAmountMatches(int $expected, TransactionResponse $verification): void
    {
        $actual = $verification->detail->originalAmount;
        if ($actual === null || $actual !== $expected) {
            throw new AmountMismatchException(sprintf(
                'SEP amount mismatch. Expected %d IRR, received %s IRR.',
                $expected,
                $actual === null ? 'null' : (string) $actual,
            ));
        }
    }

    private function assertPositiveAmount(int $amount): void
    {
        if ($amount <= 0) {
            throw new InvalidConfigurationException('SEP amount must be a positive integer in IRR.');
        }
    }

    private function assertResNum(string $resNum): void
    {
        if ($resNum === '' || strlen($resNum) > 50) {
            throw new InvalidConfigurationException('SEP ResNum must be non-empty and at most 50 characters.');
        }
    }

    private function assertRedirectUrl(string $redirectUrl): void
    {
        if (!filter_var($redirectUrl, FILTER_VALIDATE_URL)) {
            throw new InvalidConfigurationException('SEP RedirectUrl must be a valid URL.');
        }
        if (strlen($redirectUrl) > 2083) {
            throw new InvalidConfigurationException('SEP RedirectUrl exceeds the documented 2083-character limit.');
        }
    }

    private function assertRefNum(string $refNum): void
    {
        if ($refNum === '' || strlen($refNum) > 50) {
            throw new InvalidConfigurationException('SEP RefNum must be non-empty and at most 50 characters.');
        }
    }
}
