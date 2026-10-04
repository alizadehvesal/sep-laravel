<?php

declare(strict_types=1);

namespace Vestra\Sep\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Vestra\Sep\Services\SepGateway;
use Vestra\Sep\Services\SepPaymentService;

final class SepCallbackController
{
    public function __construct(
        private readonly SepGateway $gateway,
        private readonly SepPaymentService $payments,
    ) {}

    public function __invoke(Request $request): Response
    {
        $callback = $this->gateway->callbackFromArray($request->all());
        $result = $this->payments->processCallback($callback);

        return response()->view('sep::callback-result', ['result' => $result]);
    }
}
