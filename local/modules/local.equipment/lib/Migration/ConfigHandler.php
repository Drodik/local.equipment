<?php

declare(strict_types=1);

namespace Local\Equipment\Migration;

/**
 * Отдаёт sprint.migration каталог с конфигом миграций модуля (событие sprint.migration:OnSearchConfigFiles).
 */
final class ConfigHandler
{
    public static function onSearchConfigFiles(): string
    {
        return dirname(__DIR__, 2) . '/config';
    }
}
