<?php

declare(strict_types=1);

namespace Vestra\Sep\Tests\Unit;

use Illuminate\Routing\UrlGenerator;
use PHPUnit\Framework\TestCase;
use Vestra\Sep\Contracts\SepHttpClient;
use Vestra\Sep\DTO\TokenRequest;
use Vestra\Sep\Exceptions\AmountMismatchException;
use Vestra\Sep\Services\SepGateway;

final class SepGatewayTest extends TestCase
{
    public function test_token_request_uses_documented_fields(): void
    {
        $client = new class implements SepHttpClient {
            public array $payload = [];
            public function postJson(string $url, array $payload): array
            {
                $this->payload = $payload;
                return ['status' => 1, 'token' => 'TOKEN-123'];
            }
        };

        $url = $this->createMock(UrlGenerator::class);
        $gateway = new SepGateway(
            $client,
            $url,
            '2015',
            ['token' => 'https://sep.shaparak.ir/onlinepg/onlinepg', 'payment' => 'https://sep.shaparak.ir/OnlinePG/OnlinePG', 'send_token' => 'https://sep.shaparak.ir/OnlinePG/SendToken', 'verify' => 'verify', 'reverse' => 'reverse'],
        );

        $result = $gateway->requestToken(new TokenRequest(
            amount: 12000,
            resNum: 'ORDER-1',
            redirectUrl: 'https://example.test/payment/callback',
            cellNumber: '9120000000',
        ));

        self::assertSame('TOKEN-123', $result->token);
        self::assertSame('Token', $client->payload['Action']);
        self::assertSame(12000, $client->payload['Amount']);
        self::assertSame('2015', $client->payload['TerminalId']);
        self::assertSame(20, $client->payload['TokenExpiryInMin']);
    }

    public function test_verify_parses_transaction_detail(): void
    {
        $client = new class implements SepHttpClient {
            public function postJson(string $url, array $payload): array
            {
                return [
                    'TransactionDetail' => [
                        'RRN' => '14226761817',
                        'RefNum' => '50',
                        'OrginalAmount' => 1000,
                        'StraceNo' => '100428',
                    ],
                    'ResultCode' => 0,
                    'ResultDescription' => 'OK',
                    'Success' => true,
                ];
            }
        };

        $gateway = new SepGateway($client, $this->createMock(UrlGenerator::class), '2001', [
            'token' => 'token', 'payment' => 'payment', 'send_token' => 'send', 'verify' => 'verify', 'reverse' => 'reverse',
        ]);

        $result = $gateway->verify('REF-1');

        self::assertTrue($result->successful());
        self::assertSame(1000, $result->detail->originalAmount);
        self::assertSame('100428', $result->detail->straceNo);
    }

    public function test_amount_mismatch_is_rejected(): void
    {
        $client = new class implements SepHttpClient {
            public function postJson(string $url, array $payload): array
            {
                return [
                    'TransactionDetail' => ['OrginalAmount' => 900],
                    'ResultCode' => 0,
                    'Success' => true,
                ];
            }
        };

        $gateway = new SepGateway($client, $this->createMock(UrlGenerator::class), '2001', [
            'token' => 'token', 'payment' => 'payment', 'send_token' => 'send', 'verify' => 'verify', 'reverse' => 'reverse',
        ]);

        $this->expectException(AmountMismatchException::class);
        $gateway->assertAmountMatches(1000, $gateway->verify('REF-1'));
    }
}
