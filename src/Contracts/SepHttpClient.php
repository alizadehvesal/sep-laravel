<?php

declare(strict_types=1);

namespace Vestra\Sep\Contracts;

interface SepHttpClient
{
    /** @return array<string,mixed> */
    public function postJson(string $url, array $payload): array;
}
