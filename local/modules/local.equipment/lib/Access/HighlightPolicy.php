<?php

declare(strict_types=1);

namespace Local\Equipment\Access;

/**
 * Решает, выделять ли счётчик оборудования: пользователь состоит в группе выделения.
 */
final class HighlightPolicy
{
    /**
     * @param array<int|string> $userGroupIds ID групп пользователя (CurrentUser::getUserGroups() отдаёт строки)
     */
    public static function isHighlighted(array $userGroupIds, ?int $highlightGroupId): bool
    {
        if ($highlightGroupId === null) {
            return false;
        }

        return in_array($highlightGroupId, array_map('intval', $userGroupIds), true);
    }
}
