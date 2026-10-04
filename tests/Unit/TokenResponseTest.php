<?php

declare(strict_types=1);

namespace Vestra\Sep\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Vestra\Sep\DTO\TokenResponse;
use Vestra\Sep\Exceptions\SepApiException;

final class TokenResponseTest extends TestCase
{
    public function test_success_response(): void
    {
        $response = TokenResponse::fromArray(['status' => 1, 'token' => 'abc']);
        self::assertTrue($response->successful());
    }

    public function test_error_response_throws(): void
    {
        $response = TokenResponse::fromArray(['status' => -1, 'errorCode' => '5', 'errorDesc' => 'invalid']);
        $this->expectException(SepApiException::class);
        $response->throwIfFailed();
    }
}
