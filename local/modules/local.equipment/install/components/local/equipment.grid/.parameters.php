<?php

use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$arComponentParameters = [
    'PARAMETERS' => [
        'DEAL_ID' => [
            'PARENT' => 'BASE',
            'NAME' => Loc::getMessage('LOCAL_EQUIPMENT_GRID_PARAM_DEAL_ID'),
            'TYPE' => 'STRING',
        ],
        'GRID_ID' => [
            'PARENT' => 'BASE',
            'NAME' => Loc::getMessage('LOCAL_EQUIPMENT_GRID_PARAM_GRID_ID'),
            'TYPE' => 'STRING',
            'DEFAULT' => 'local_equipment_grid',
        ],
    ],
];
