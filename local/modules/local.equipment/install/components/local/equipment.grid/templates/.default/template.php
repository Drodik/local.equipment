<?php

/**
 * @var array $arResult
 * @var CMain $APPLICATION
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$APPLICATION->IncludeComponent('bitrix:main.ui.filter', '', [
    'FILTER_ID' => $arResult['FILTER_ID'],
    'GRID_ID' => $arResult['GRID_ID'],
    'FILTER' => $arResult['FILTER_FIELDS'],
    'ENABLE_LIVE_SEARCH' => true,
    'ENABLE_LABEL' => true,
]);

$APPLICATION->IncludeComponent('bitrix:main.ui.grid', '', [
    'GRID_ID' => $arResult['GRID_ID'],
    'COLUMNS' => $arResult['COLUMNS'],
    'ROWS' => $arResult['ROWS'],
    'NAV_OBJECT' => $arResult['NAV_OBJECT'],
    'TOTAL_ROWS_COUNT' => $arResult['TOTAL_ROWS_COUNT'],
    'AJAX_MODE' => 'Y',
    'AJAX_ID' => CAjax::GetComponentID('bitrix:main.ui.grid', '.default', ''),
    'AJAX_OPTION_JUMP' => 'N',
    'AJAX_OPTION_HISTORY' => 'N',
    'SHOW_ROW_CHECKBOXES' => false,
    'SHOW_CHECK_ALL_CHECKBOXES' => false,
    'SHOW_ROW_ACTIONS_MENU' => false,
    'SHOW_GRID_SETTINGS_MENU' => true,
    'SHOW_NAVIGATION_PANEL' => true,
    'SHOW_PAGINATION' => true,
    'SHOW_SELECTED_COUNTER' => false,
    'SHOW_TOTAL_COUNTER' => true,
    'SHOW_PAGESIZE' => true,
    'SHOW_ACTION_PANEL' => false,
    'ALLOW_SORT' => true,
    'ALLOW_COLUMNS_SORT' => true,
    'ALLOW_COLUMNS_RESIZE' => true,
    'PAGE_SIZES' => [
        ['NAME' => '10', 'VALUE' => '10'],
        ['NAME' => '20', 'VALUE' => '20'],
        ['NAME' => '50', 'VALUE' => '50'],
    ],
]);
