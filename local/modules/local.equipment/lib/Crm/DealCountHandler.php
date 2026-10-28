<?php

declare(strict_types=1);

namespace Local\Equipment\Crm;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Application;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\SystemException;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;
use Local\Equipment\Access\HighlightGroup;
use Local\Equipment\Access\HighlightPolicy;
use Local\Equipment\Repository\EquipmentRepository;

/**
 * Выводит в карточке сделки количество оборудования под полем «Клиент» (main:OnEpilog).
 * Число и решение о выделении считает сервер, JS только рисует блок.
 */
final class DealCountHandler
{
    public const EXTENSION = 'local.equipment.deal-count';

    public static function onEpilog(): void
    {
        $request = Application::getInstance()->getContext()->getRequest();
        if ($request->isAdminSection() || $request->isAjaxRequest()) {
            return;
        }

        $dealId = DealPage::extractDealId((string)$request->getRequestUri());
        if ($dealId === null) {
            return;
        }

        if (!Loader::includeModule('crm') || !Loader::includeModule('highloadblock')) {
            return;
        }

        if (!Container::getInstance()->getUserPermissions()->checkReadPermissions(\CCrmOwnerType::Deal, $dealId)) {
            return;
        }

        try {
            $view = new DealCountViewDTO(
                $dealId,
                (new EquipmentRepository())->countByDeal($dealId),
                HighlightPolicy::isHighlighted(
                    HighlightGroup::getUserGroupIds((int)CurrentUser::get()->getId()),
                    HighlightGroup::getId()
                )
            );
        } catch (SystemException $e) {
            // Нет HL-блока или ошибка БД: карточка сделки должна открыться и без счётчика.
            \CEventLog::Log('ERROR', 'LOCAL_EQUIPMENT_DEAL_COUNT', 'local.equipment', (string)$dealId, $e->getMessage());

            return;
        }

        Extension::load(self::EXTENSION);
        Asset::getInstance()->addString(
            '<script>BX.ready(function(){BX.Local.Equipment.DealCount.init(' . Json::encode($view) . ');});</script>'
        );
    }
}
