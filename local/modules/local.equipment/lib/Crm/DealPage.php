<?php

declare(strict_types=1);

namespace Local\Equipment\Crm;

/**
 * Разбор URL карточки сделки. Путь /crm/deal/details/ — значение SEF по умолчанию.
 */
final class DealPage
{
    private const DETAILS_PATTERN = '#^/crm/deal/details/(\d+)/#';

    public static function extractDealId(string $requestUri): ?int
    {
        if (!preg_match(self::DETAILS_PATTERN, $requestUri, $matches)) {
            return null;
        }

        $dealId = (int)$matches[1];

        return $dealId > 0 ? $dealId : null;
    }
}
