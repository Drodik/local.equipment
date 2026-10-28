<?php

declare(strict_types=1);

namespace Local\Equipment\Crm;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Config\Option;

/**
 * Смарт-процесс «Заявка на оборудование», созданный миграцией 2.
 * У типа нет CODE, поэтому entityTypeId хранится в опции модуля.
 */
final class SmartProcess
{
    public const MODULE_ID = 'local.equipment';
    public const OPTION_ENTITY_TYPE_ID = 'smart_process_entity_type_id';

    public static function getEntityTypeId(): ?int
    {
        $entityTypeId = (int)Option::get(self::MODULE_ID, self::OPTION_ENTITY_TYPE_ID, '0');

        return $entityTypeId > 0 ? $entityTypeId : null;
    }

    public static function getDealIdByItemId(int $itemId): ?int
    {
        $entityTypeId = self::getEntityTypeId();
        if ($entityTypeId === null || $itemId <= 0) {
            return null;
        }

        $factory = Container::getInstance()->getFactory($entityTypeId);
        if ($factory === null) {
            return null;
        }

        $parentField = 'PARENT_ID_' . \CCrmOwnerType::Deal;
        $rows = $factory->getDataClass()::query()
            ->setSelect([$parentField])
            ->where('ID', $itemId)
            ->fetchAll();
        $dealId = (int)($rows[0][$parentField] ?? 0);

        return $dealId > 0 ? $dealId : null;
    }
}
