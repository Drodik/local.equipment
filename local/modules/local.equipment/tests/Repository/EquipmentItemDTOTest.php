<?php

declare(strict_types=1);

namespace Local\Equipment\Tests\Repository;

use Local\Equipment\Repository\EquipmentItemDTO;
use PHPUnit\Framework\TestCase;

final class EquipmentItemDTOTest extends TestCase
{
    // fetchAll отдаёт числа строками.
    public function testFromRowCastsTypes(): void
    {
        $item = EquipmentItemDTO::fromRow([
            'ID' => '12',
            'UF_NAME' => 'Роутер Cisco RV340',
            'UF_TYPE' => '3',
            'UF_CREATED_TIME' => null,
            'UF_DEAL_ID' => '7',
        ]);

        self::assertSame(12, $item->id);
        self::assertSame('Роутер Cisco RV340', $item->name);
        self::assertSame(3, $item->typeId);
        self::assertNull($item->createdTime);
        self::assertSame(7, $item->dealId);
    }

    // Пустые поля HL-блока приходят как null или '', а не как отсутствующие значения.
    public function testFromRowEmptyValues(): void
    {
        $item = EquipmentItemDTO::fromRow([
            'ID' => '1',
            'UF_NAME' => null,
            'UF_TYPE' => null,
            'UF_CREATED_TIME' => '',
            'UF_DEAL_ID' => '7',
        ]);

        self::assertSame('', $item->name);
        self::assertSame(0, $item->typeId);
        self::assertNull($item->createdTime);
    }
}
