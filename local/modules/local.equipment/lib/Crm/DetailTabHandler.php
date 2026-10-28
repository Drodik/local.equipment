<?php

declare(strict_types=1);

namespace Local\Equipment\Crm;

use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Localization\Loc;

/**
 * Добавляет вкладку «Оборудование» в карточку элемента смарт-процесса (crm:onEntityDetailsTabsInitialized).
 *
 * Компонент рендерится сразу в 'html' вкладки (как штатная вкладка товаров сделки): AJAX-перезагрузка
 * main.ui.grid/main.ui.filter запрашивает страницу карточки, и компонент должен быть в её выдаче.
 * DEAL_ID определяется на сервере и с клиента не приходит.
 */
final class DetailTabHandler
{
    public const TAB_ID = 'tab_local_equipment';
    public const GRID_ID = 'local_equipment_sp_grid';
    private const COMPONENT = 'local:equipment.grid';

    public static function onTabsInitialized(Event $event): EventResult
    {
        $tabs = (array)$event->getParameter('tabs');
        $entityTypeId = (int)$event->getParameter('entityTypeID');
        $entityId = (int)$event->getParameter('entityID');

        // entityID = 0 — форма создания элемента, сделки у него ещё нет.
        if ($entityId <= 0 || $entityTypeId !== SmartProcess::getEntityTypeId()) {
            return new EventResult(EventResult::SUCCESS, ['tabs' => $tabs]);
        }

        Loc::loadMessages(__FILE__);

        $tabs[] = [
            'id' => self::TAB_ID,
            'name' => Loc::getMessage('LOCAL_EQUIPMENT_TAB_NAME'),
            'html' => self::renderGrid($entityId),
        ];

        return new EventResult(EventResult::SUCCESS, ['tabs' => $tabs]);
    }

    private static function renderGrid(int $entityId): string
    {
        global $APPLICATION;

        // Ошибка вкладки не должна ломать всю карточку CRM.
        try {
            // Если сделки нет, DEAL_ID = 0 и компонент сам покажет «Сделка не привязана».
            $dealId = (int)SmartProcess::getDealIdByItemId($entityId);
        } catch (\Throwable $e) {
            \CEventLog::Log('ERROR', 'LOCAL_EQUIPMENT_TAB', SmartProcess::MODULE_ID, (string)$entityId, $e->getMessage());
            $dealId = 0;
        }

        ob_start();
        $APPLICATION->IncludeComponent(self::COMPONENT, '', [
            'DEAL_ID' => $dealId,
            'GRID_ID' => self::GRID_ID,
        ]);

        return (string)ob_get_clean();
    }
}
