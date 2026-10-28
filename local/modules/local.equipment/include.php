<?php

declare(strict_types=1);

use Bitrix\Main\Loader;

// Классы модуля лежат в lib/ в PascalCase (PSR-4), штатный автозагрузчик ищет файлы в нижнем регистре.
Loader::registerNamespace('Local\\Equipment', __DIR__ . '/lib');
