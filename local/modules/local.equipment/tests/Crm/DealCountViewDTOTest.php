<?php

declare(strict_types=1);

namespace Local\Equipment\Tests\Crm;

use Local\Equipment\Crm\DealCountViewDTO;
use PHPUnit\Framework\TestCase;

final class DealCountViewDTOTest extends TestCase
{
    public function testZeroCount(): void
    {
        self::assertSame(
            ['dealId' => 7, 'count' => 0, 'highlight' => false],
            (new DealCountViewDTO(7, 0, false))->jsonSerialize()
        );
    }

    public function testHighlightedCount(): void
    {
        self::assertSame(
            ['dealId' => 3, 'count' => 5, 'highlight' => true],
            (new DealCountViewDTO(3, 5, true))->jsonSerialize()
        );
    }

    // Контракт с JS (BX.Local.Equipment.DealCount.init): ровно эти ключи и типы.
    public function testJsonContract(): void
    {
        self::assertSame(
            '{"dealId":3,"count":5,"highlight":true}',
            json_encode(new DealCountViewDTO(3, 5, true), JSON_THROW_ON_ERROR)
        );
    }
}
