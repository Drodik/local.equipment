<?php

declare(strict_types=1);

use Bitrix\Main\EventManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Local\Equipment\Crm\DealCountHandler;
use Local\Equipment\Crm\DetailTabHandler;
use Local\Equipment\Migration\ConfigHandler;

Loc::loadMessages(__FILE__);

// Имя класса = ID модуля с заменой точки на подчёркивание (требование ядра Битрикса).
class local_equipment extends CModule
{
    public $MODULE_ID = 'local.equipment';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $PARTNER_NAME;
    public $PARTNER_URI;

    public function __construct()
    {
        $arModuleVersion = [];
        include __DIR__ . '/version.php';

        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = Loc::getMessage('LOCAL_EQUIPMENT_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('LOCAL_EQUIPMENT_MODULE_DESCRIPTION');
        $this->PARTNER_NAME = Loc::getMessage('LOCAL_EQUIPMENT_PARTNER_NAME');
        $this->PARTNER_URI = '';
    }

    public function doInstall(): void
    {
        ModuleManager::registerModule($this->MODULE_ID);
        $this->installEvents();
        $this->installFiles();
    }

    public function doUninstall(): void
    {
        // Данные (HL-блок, смарт-процесс, группа) удаляет только down миграций, не деинсталляция модуля.
        $this->unInstallFiles();
        $this->unInstallEvents();
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }

    public function installEvents(): bool
    {
        $eventManager = EventManager::getInstance();

        // sprint.migration вызывает обработчик через GetModuleEvents/ExecuteModuleEventEx.
        $eventManager->registerEventHandlerCompatible(
            'sprint.migration',
            'OnSearchConfigFiles',
            $this->MODULE_ID,
            ConfigHandler::class,
            'onSearchConfigFiles'
        );
        $eventManager->registerEventHandler(
            'crm',
            'onEntityDetailsTabsInitialized',
            $this->MODULE_ID,
            DetailTabHandler::class,
            'onTabsInitialized'
        );
        $eventManager->registerEventHandlerCompatible(
            'main',
            'OnEpilog',
            $this->MODULE_ID,
            DealCountHandler::class,
            'onEpilog'
        );

        return true;
    }

    public function unInstallEvents(): bool
    {
        $eventManager = EventManager::getInstance();

        $eventManager->unRegisterEventHandler(
            'sprint.migration',
            'OnSearchConfigFiles',
            $this->MODULE_ID,
            ConfigHandler::class,
            'onSearchConfigFiles'
        );
        $eventManager->unRegisterEventHandler(
            'crm',
            'onEntityDetailsTabsInitialized',
            $this->MODULE_ID,
            DetailTabHandler::class,
            'onTabsInitialized'
        );
        $eventManager->unRegisterEventHandler(
            'main',
            'OnEpilog',
            $this->MODULE_ID,
            DealCountHandler::class,
            'onEpilog'
        );

        return true;
    }

    public function installFiles(): bool
    {
        CopyDirFiles(__DIR__ . '/components', $_SERVER['DOCUMENT_ROOT'] . '/local/components', true, true);
        CopyDirFiles(__DIR__ . '/js', $_SERVER['DOCUMENT_ROOT'] . '/local/js', true, true);

        return true;
    }

    public function unInstallFiles(): bool
    {
        DeleteDirFilesEx('/local/components/local/equipment.grid');
        DeleteDirFilesEx('/local/js/local/equipment');

        return true;
    }
}
