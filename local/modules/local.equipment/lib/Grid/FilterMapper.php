<?php

declare(strict_types=1);

namespace Local\Equipment\Grid;

/**
 * Переводит значения main.ui.filter (Filter\Options::getFilter) в условия ORM Query
 * в формате [column, operator, value] для ConditionTree::where().
 * Условие по сделке сюда не входит: его всегда добавляет репозиторий.
 */
final class FilterMapper
{
    /**
     * @param \Closure(string): mixed $toDateTime превращает строку даты фильтра в значение для ORM
     * @return list<array{0: string, 1: string, 2: mixed}>
     */
    public static function toConditions(array $uiFilter, \Closure $toDateTime): array
    {
        $conditions = [];

        $name = self::nonEmptyString($uiFilter['UF_NAME'] ?? null) ?? self::nonEmptyString($uiFilter['FIND'] ?? null);
        if ($name !== null) {
            $conditions[] = ['UF_NAME', 'like', '%' . addcslashes($name, '\\%_') . '%'];
        }

        $types = array_values(array_map(
            'intval',
            array_filter((array)($uiFilter['UF_TYPE'] ?? []), static fn($v): bool => (int)$v > 0)
        ));
        if ($types) {
            $conditions[] = ['UF_TYPE', 'in', $types];
        }

        $from = self::nonEmptyString($uiFilter['UF_CREATED_TIME_from'] ?? null);
        if ($from !== null) {
            $conditions[] = ['UF_CREATED_TIME', '>=', $toDateTime($from)];
        }

        $to = self::nonEmptyString($uiFilter['UF_CREATED_TIME_to'] ?? null);
        if ($to !== null) {
            $conditions[] = ['UF_CREATED_TIME', '<=', $toDateTime($to)];
        }

        return $conditions;
    }

    private static function nonEmptyString(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string)$value);

        return $value === '' ? null : $value;
    }
}
