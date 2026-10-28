<?php

namespace Sprint\Migration;

use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use Local\Equipment\Crm\SmartProcess;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\MigrationException;

class Version20261028000003 extends Version
{
    protected $description = 'Тестовые данные: сделки, элемент смарт-процесса, оборудование';

    protected $moduleVersion = '5.16.1';

    private const HLBLOCK = 'Equipment';

    /**
     * @throws MigrationException
     * @throws HelperException
     */
    public function up()
    {
        $this->checkRequiredVersions(['Version20261028000001', 'Version20261028000002']);
        $this->includeModules();

        $storage = $this->getStorageManager();
        $dealFactory = $this->getFactory(\CCrmOwnerType::Deal);
        $itemFactory = $this->getFactory((int)SmartProcess::getEntityTypeId());

        // Повторный up не плодит сделки: ID берутся из storage, если уже созданы.
        $dealA = (int)$storage->getSavedData('deal_a')
            ?: $this->addItem($dealFactory, ['TITLE' => 'Тестовая сделка: оборудование офиса']);
        $storage->saveData('deal_a', $dealA);

        $dealB = (int)$storage->getSavedData('deal_b')
            ?: $this->addItem($dealFactory, ['TITLE' => 'Тестовая сделка: склад']);
        $storage->saveData('deal_b', $dealB);

        $itemId = (int)$storage->getSavedData('item_id')
            ?: $this->addItem($itemFactory, [
                'TITLE' => 'Заявка на оборудование №1',
                'CATEGORY_ID' => $itemFactory->createDefaultCategoryIfNotExist()->getId(),
                'PARENT_ID_' . \CCrmOwnerType::Deal => $dealA,
            ]);
        $storage->saveData('item_id', $itemId);

        $hlHelper = $this->getHelperManager()->Hlblock();
        $hlblockId = $hlHelper->getHlblockIdIfExists(self::HLBLOCK);
        $typeIds = [];
        foreach (['router', 'laptop', 'monitor'] as $xmlId) {
            $typeIds[$xmlId] = (int)$hlHelper->getFieldEnumIdByXmlId($hlblockId, 'UF_TYPE', $xmlId);
        }

        $equipment = [
            [$dealA, 'Роутер Cisco RV340', 'router', '01.10.2026 09:15:00'],
            [$dealA, 'Роутер MikroTik hAP ax3', 'router', '03.10.2026 11:40:00'],
            [$dealA, 'Ноутбук Lenovo ThinkPad T14', 'laptop', '07.10.2026 14:05:00'],
            [$dealA, 'Ноутбук Dell Latitude 5440', 'laptop', '12.10.2026 10:30:00'],
            [$dealA, 'Монитор LG 27UP850', 'monitor', '20.10.2026 16:20:00'],
            [$dealB, 'Ноутбук HP ProBook 450', 'laptop', '05.10.2026 12:00:00'],
            [$dealB, 'Монитор Samsung S24C450', 'monitor', '15.10.2026 08:45:00'],
        ];

        foreach ($equipment as [$dealId, $name, $type, $createdTime]) {
            $hlHelper->saveElementWithEqualKeys(self::HLBLOCK, [
                'UF_NAME' => $name,
                'UF_TYPE' => $typeIds[$type],
                'UF_CREATED_TIME' => new DateTime($createdTime, 'd.m.Y H:i:s'),
                'UF_DEAL_ID' => $dealId,
            ], ['UF_NAME', 'UF_DEAL_ID']);
        }

        $this->outSuccess('Сделки: %d, %d; элемент смарт-процесса: %d', $dealA, $dealB, $itemId);
    }

    /**
     * @throws MigrationException
     * @throws HelperException
     */
    public function down()
    {
        $this->includeModules();

        $storage = $this->getStorageManager();
        $dealIds = array_values(array_filter([
            (int)$storage->getSavedData('deal_a'),
            (int)$storage->getSavedData('deal_b'),
        ]));
        $itemId = (int)$storage->getSavedData('item_id');

        $hlHelper = $this->getHelperManager()->Hlblock();
        if ($dealIds && $hlHelper->getHlblockId(self::HLBLOCK)) {
            $rows = $hlHelper->getElements(self::HLBLOCK, [
                'filter' => ['@UF_DEAL_ID' => $dealIds],
                'select' => ['ID'],
            ]);
            foreach ($rows as $row) {
                $hlHelper->deleteElement(self::HLBLOCK, (int)$row['ID']);
            }
        }

        $entityTypeId = SmartProcess::getEntityTypeId();
        if ($itemId > 0 && $entityTypeId !== null) {
            $this->deleteItem($this->getFactory($entityTypeId), $itemId);
        }

        $dealFactory = $this->getFactory(\CCrmOwnerType::Deal);
        foreach ($dealIds as $dealId) {
            $this->deleteItem($dealFactory, $dealId);
        }

        $storage->deleteSavedData();
    }

    /**
     * @throws MigrationException
     */
    private function includeModules(): void
    {
        if (!Loader::includeModule('crm') || !Loader::includeModule('local.equipment')) {
            throw new MigrationException('Не подключены модули crm и local.equipment');
        }
    }

    /**
     * @throws MigrationException
     */
    private function getFactory(int $entityTypeId): Factory
    {
        $factory = Container::getInstance()->getFactory($entityTypeId);
        if (!$factory) {
            throw new MigrationException(sprintf('Нет фабрики CRM для entityTypeId=%d', $entityTypeId));
        }

        return $factory;
    }

    /**
     * @throws MigrationException
     */
    private function addItem(Factory $factory, array $fields): int
    {
        $item = $factory->createItem($fields);
        $result = $factory->getAddOperation($item)->disableAllChecks()->launch();
        if (!$result->isSuccess()) {
            throw new MigrationException(implode(PHP_EOL, $result->getErrorMessages()));
        }

        return (int)$item->getId();
    }

    /**
     * @throws MigrationException
     */
    private function deleteItem(Factory $factory, int $id): void
    {
        $item = $factory->getItem($id);
        if (!$item instanceof Item) {
            return;
        }

        $result = $factory->getDeleteOperation($item)->disableAllChecks()->launch();
        if (!$result->isSuccess()) {
            throw new MigrationException(implode(PHP_EOL, $result->getErrorMessages()));
        }
    }
}
