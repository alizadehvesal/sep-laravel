<?php

declare(strict_types=1);

namespace Vestra\Sep\Services;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Vestra\Sep\Contracts\SepHttpClient;
use Vestra\Sep\Exceptions\SepTransportException;

final class SepHttpClientImpl implements SepHttpClient
{
    public function __construct(
        private readonly ClientInterface $client,
        private readonly int $timeout,
        private readonly int $connectTimeout,
        private readonly string $userAgent,
    ) {}

    public function postJson(string $url, array $payload): array
    {
        try {
            $response = $this->client->request('POST', $url, [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'User-Agent' => $this->userAgent,
                ],
                'json' => $payload,
                'timeout' => $this->timeout,
                'connect_timeout' => $this->connectTimeout,
                'http_errors' => false,
            ]);
        } catch (ConnectException|RequestException $e) {
            throw new SepTransportException('SEP request failed before a valid response was received: '.$e->getMessage(), 0, $e);
        }

        $body = (string) $response->getBody();
        $decoded = json_decode($body, true);

        if (!is_array($decoded)) {
            throw new SepTransportException('SEP returned a non-JSON or malformed JSON response.');
        }

        return $decoded;
    }
}
