<?php

declare(strict_types=1);

namespace Local\Equipment\Repository;

use Bitrix\Main\Type\DateTime;

/**
 * Запись HL-блока «Оборудование».
 */
final readonly class EquipmentItemDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public int $typeId,
        public ?DateTime $createdTime,
        public int $dealId,
    ) {
    }

    /**
     * @param array{ID: mixed, UF_NAME: mixed, UF_TYPE: mixed, UF_CREATED_TIME: mixed, UF_DEAL_ID: mixed} $row строка fetchAll()
     */
    public static function fromRow(array $row): self
    {
        return new self(
            (int)$row['ID'],
            (string)$row['UF_NAME'],
            (int)$row['UF_TYPE'],
            $row['UF_CREATED_TIME'] instanceof DateTime ? $row['UF_CREATED_TIME'] : null,
            (int)$row['UF_DEAL_ID'],
        );
    }
}
