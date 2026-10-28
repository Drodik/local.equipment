<?php

namespace Sprint\Migration;

use Bitrix\Crm\Model\Dynamic\TypeTable;
use Bitrix\Crm\Relation;
use Bitrix\Crm\RelationIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Local\Equipment\Crm\SmartProcess;
use Sprint\Migration\Exceptions\MigrationException;

class Version20261028000002 extends Version
{
    protected $description = 'Смарт-процесс «Заявка на оборудование» со связью со сделкой';

    protected $moduleVersion = '5.16.1';

    private const TITLE = 'Заявка на оборудование';

    /**
     * @throws MigrationException
     */
    public function up()
    {
        $this->includeModules();

        $entityTypeId = SmartProcess::getEntityTypeId();
        if ($entityTypeId !== null && $this->findTypeId($entityTypeId) !== null) {
            $this->outInfo('Смарт-процесс уже создан, entityTypeId=%d', $entityTypeId);

            return;
        }

        $entityTypeId = TypeTable::getNextAvailableEntityTypeId();

        // Как в ядре (Bitrix\Crm\Controller\Type::addAction): data class из контейнера, NAME из generateName.
        $dataClass = Container::getInstance()->getDynamicTypeDataClass();
        $result = $dataClass::add([
            'TITLE' => self::TITLE,
            'NAME' => $dataClass::generateName(self::TITLE),
            'ENTITY_TYPE_ID' => $entityTypeId,
            'IS_STAGES_ENABLED' => 'Y',
            'IS_CATEGORIES_ENABLED' => 'Y',
        ]);
        if (!$result->isSuccess()) {
            throw new MigrationException(implode(PHP_EOL, $result->getErrorMessages()));
        }

        // Сделка — родитель смарт-процесса, список дочерних элементов виден в карточке сделки.
        $relation = new Relation(
            new RelationIdentifier(\CCrmOwnerType::Deal, $entityTypeId),
            (new Relation\Settings())->setIsChildrenListEnabled(true)
        );
        $bindResult = Container::getInstance()->getRelationManager()->bindTypes($relation);
        if (!$bindResult->isSuccess()) {
            throw new MigrationException(implode(PHP_EOL, $bindResult->getErrorMessages()));
        }

        Option::set(SmartProcess::MODULE_ID, SmartProcess::OPTION_ENTITY_TYPE_ID, (string)$entityTypeId);
        $this->outSuccess('Создан смарт-процесс entityTypeId=%d', $entityTypeId);
    }

    /**
     * @throws MigrationException
     */
    public function down()
    {
        $this->includeModules();

        $entityTypeId = SmartProcess::getEntityTypeId();
        $typeId = $entityTypeId !== null ? $this->findTypeId($entityTypeId) : null;

        if ($typeId !== null) {
            // Удаление возможно только без элементов: миграция 3 откатывается раньше.
            $result = TypeTable::delete($typeId);
            if (!$result->isSuccess()) {
                throw new MigrationException(implode(PHP_EOL, $result->getErrorMessages()));
            }
        }

        Option::delete(SmartProcess::MODULE_ID, ['name' => SmartProcess::OPTION_ENTITY_TYPE_ID]);
    }

    private function findTypeId(int $entityTypeId): ?int
    {
        $rows = TypeTable::query()
            ->setSelect(['ID'])
            ->where('ENTITY_TYPE_ID', $entityTypeId)
            ->fetchAll();

        return $rows ? (int)$rows[0]['ID'] : null;
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
}
