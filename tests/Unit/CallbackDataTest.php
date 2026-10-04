<?php

declare(strict_types=1);

namespace Vestra\Sep\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Vestra\Sep\DTO\CallbackData;

final class CallbackDataTest extends TestCase
{
    public function test_callback_is_parsed_without_mutating_field_names(): void
    {
        $data = CallbackData::fromArray([
            'Token' => 'TOKEN',
            'ResNum' => 'ORDER-1',
            'RefNum' => 'REF-1',
            'TraceNo' => 'TRACE-1',
            'State' => 'OK',
            'Status' => '1',
            'TerminalId' => '2015',
            'MID' => '2015',
            'RRN' => 'RRN-1',
            'Amount' => '12000',
            'Wage' => '0',
        ]);

        self::assertSame('ORDER-1', $data->resNum);
        self::assertSame('REF-1', $data->refNum);
        self::assertTrue($data->successfulState());
        self::assertSame(12000, $data->amount);
    }
}
