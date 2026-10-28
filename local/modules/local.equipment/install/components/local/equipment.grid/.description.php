<?php

use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$arComponentDescription = [
    'NAME' => Loc::getMessage('LOCAL_EQUIPMENT_GRID_DESC_NAME'),
    'DESCRIPTION' => Loc::getMessage('LOCAL_EQUIPMENT_GRID_DESC_DESCRIPTION'),
    'PATH' => [
        'ID' => 'local',
        'NAME' => 'Local',
    ],
];
