<?php

declare(strict_types=1);

namespace Local\Equipment\Access;

use Bitrix\Main\GroupTable;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UserGroupTable;

/**
 * Группа пользователей, для которых счётчик оборудования выделяется. Создаётся миграцией 4.
 */
final class HighlightGroup
{
    public const STRING_ID = 'EQUIPMENT_COUNT_HIGHLIGHT';

    // Без кеша: группу создаёт миграция, и закешированный «группы нет» скрыл бы выделение.
    public static function getId(): ?int
    {
        $rows = GroupTable::query()
            ->setSelect(['ID'])
            ->where('STRING_ID', self::STRING_ID)
            ->where('ACTIVE', 'Y')
            ->fetchAll();

        return $rows ? (int)$rows[0]['ID'] : null;
    }

    /**
     * Действующие группы пользователя из БД, а не из сессии: членство, выданное после входа,
     * учитывается без перелогина.
     *
     * @return list<int>
     */
    public static function getUserGroupIds(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        $now = new DateTime();
        $rows = UserGroupTable::query()
            ->setSelect(['GROUP_ID'])
            ->where('USER_ID', $userId)
            ->where(Query::filter()
                ->logic('or')
                ->whereNull('DATE_ACTIVE_FROM')
                ->where('DATE_ACTIVE_FROM', '<=', $now))
            ->where(Query::filter()
                ->logic('or')
                ->whereNull('DATE_ACTIVE_TO')
                ->where('DATE_ACTIVE_TO', '>=', $now))
            ->fetchAll();

        return array_map(static fn(array $row): int => (int)$row['GROUP_ID'], $rows);
    }
}
