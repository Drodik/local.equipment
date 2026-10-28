<?php

declare(strict_types=1);

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Grid\Options as GridOptions;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UI\Filter\Options as FilterOptions;
use Bitrix\Main\UI\PageNavigation;
use Local\Equipment\Grid\FilterMapper;
use Local\Equipment\Repository\EquipmentItemDTO;
use Local\Equipment\Repository\EquipmentRepository;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * Грид оборудования одной сделки (main.ui.grid + main.ui.filter), только чтение.
 */
class EquipmentGridComponent extends CBitrixComponent
{
    private const DEFAULT_GRID_ID = 'local_equipment_grid';
    private const PAGE_SIZE = 20;

    public function onPrepareComponentParams($arParams): array
    {
        $arParams['DEAL_ID'] = (int)($arParams['DEAL_ID'] ?? 0);
        $arParams['GRID_ID'] = preg_replace('/[^a-z0-9_]/i', '', (string)($arParams['GRID_ID'] ?? ''))
            ?: self::DEFAULT_GRID_ID;

        return $arParams;
    }

    public function executeComponent(): void
    {
        if (!Loader::includeModule('highloadblock') || !Loader::includeModule('crm') || !Loader::includeModule('local.equipment')) {
            ShowError(Loc::getMessage('LOCAL_EQUIPMENT_GRID_MODULES'));

            return;
        }

        $dealId = $this->arParams['DEAL_ID'];
        if ($dealId <= 0) {
            // Без сделки грид не строим: пустой фильтр показал бы всё оборудование.
            ShowError(Loc::getMessage('LOCAL_EQUIPMENT_GRID_NO_DEAL'));

            return;
        }

        if (!Container::getInstance()->getUserPermissions()->checkReadPermissions(\CCrmOwnerType::Deal, $dealId)) {
            ShowError(Loc::getMessage('LOCAL_EQUIPMENT_GRID_ACCESS_DENIED'));

            return;
        }

        $repository = new EquipmentRepository();
        $gridId = $this->arParams['GRID_ID'];
        $filterId = $gridId . '_filter';
        try {
            $types = $repository->getTypeEnum();
        } catch (SystemException) {
            // HL-блока нет (миграция 1 не применена) — сообщаем, а не роняем карточку.
            ShowError(Loc::getMessage('LOCAL_EQUIPMENT_GRID_NO_HLBLOCK'));

            return;
        }
        $filterFields = $this->getFilterFields($types);

        $uiFilter = (new FilterOptions($filterId))->getFilter($filterFields);
        $conditions = FilterMapper::toConditions($uiFilter, static fn(string $value): DateTime => new DateTime($value));

        $gridOptions = new GridOptions($gridId);
        $sorting = $gridOptions->GetSorting(['sort' => ['ID' => 'DESC']]);
        $navParams = $gridOptions->GetNavParams(['nPageSize' => self::PAGE_SIZE]);

        $nav = new PageNavigation('nav-' . $gridId);
        $nav->allowAllRecords(false)
            ->setPageSize((int)($navParams['nPageSize'] ?? self::PAGE_SIZE))
            ->initFromUri();

        $rows = $repository->getList($dealId, $conditions, $sorting['sort'], $nav);

        $this->arResult = [
            'GRID_ID' => $gridId,
            'FILTER_ID' => $filterId,
            'FILTER_FIELDS' => $filterFields,
            'COLUMNS' => $this->getColumns(),
            'ROWS' => array_map(fn(EquipmentItemDTO $item): array => $this->makeGridRow($item, $types), $rows),
            'NAV_OBJECT' => $nav,
            'TOTAL_ROWS_COUNT' => $nav->getRecordCount(),
        ];

        $this->includeComponentTemplate();
    }

    private function getFilterFields(array $types): array
    {
        return [
            [
                'id' => 'UF_NAME',
                'name' => Loc::getMessage('LOCAL_EQUIPMENT_GRID_COL_NAME'),
                'type' => 'string',
                'default' => true,
            ],
            [
                'id' => 'UF_TYPE',
                'name' => Loc::getMessage('LOCAL_EQUIPMENT_GRID_COL_TYPE'),
                'type' => 'list',
                'items' => $types,
                'params' => ['multiple' => 'Y'],
                'default' => true,
            ],
            [
                'id' => 'UF_CREATED_TIME',
                'name' => Loc::getMessage('LOCAL_EQUIPMENT_GRID_COL_CREATED_TIME'),
                'type' => 'date',
                'default' => true,
            ],
        ];
    }

    private function getColumns(): array
    {
        return [
            ['id' => 'ID', 'name' => 'ID', 'sort' => 'ID', 'default' => true],
            ['id' => 'UF_NAME', 'name' => Loc::getMessage('LOCAL_EQUIPMENT_GRID_COL_NAME'), 'sort' => 'UF_NAME', 'default' => true],
            ['id' => 'UF_TYPE', 'name' => Loc::getMessage('LOCAL_EQUIPMENT_GRID_COL_TYPE'), 'sort' => 'UF_TYPE', 'default' => true],
            ['id' => 'UF_CREATED_TIME', 'name' => Loc::getMessage('LOCAL_EQUIPMENT_GRID_COL_CREATED_TIME'), 'sort' => 'UF_CREATED_TIME', 'default' => true],
            ['id' => 'UF_DEAL_ID', 'name' => Loc::getMessage('LOCAL_EQUIPMENT_GRID_COL_DEAL_ID'), 'default' => true],
        ];
    }

    private function makeGridRow(EquipmentItemDTO $item, array $types): array
    {
        $columns = [
            'ID' => $item->id,
            'UF_NAME' => htmlspecialcharsbx($item->name),
            'UF_TYPE' => htmlspecialcharsbx($types[$item->typeId] ?? ''),
            'UF_CREATED_TIME' => $item->createdTime ? htmlspecialcharsbx($item->createdTime->toString()) : '',
            'UF_DEAL_ID' => $item->dealId,
        ];

        return [
            'id' => $item->id,
            'data' => $columns,
            'columns' => $columns,
        ];
    }
}
