<?php

namespace Sprint\Migration;

use Bitrix\Main\UserGroupTable;
use Bitrix\Main\UserTable;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\MigrationException;

class Version20261028000004 extends Version
{
    protected $description = 'Группа «Выделение счётчика оборудования» и тестовый пользователь';

    protected $moduleVersion = '5.16.1';

    private const GROUP_CODE = 'EQUIPMENT_COUNT_HIGHLIGHT';

    // Пользователь из ТЗ. Единственное место, где фигурирует ID 5: код выделения смотрит только на группу.
    private const TEST_USER_ID = 5;

    /**
     * @throws HelperException
     * @throws MigrationException
     */
    public function up()
    {
        $groupId = $this->getHelperManager()->UserGroup()->saveGroup(self::GROUP_CODE, [
            'NAME' => 'Выделение счётчика оборудования',
            'ACTIVE' => 'Y',
            'C_SORT' => 500,
        ]);

        $users = UserTable::query()
            ->setSelect(['ID'])
            ->where('ID', self::TEST_USER_ID)
            ->fetchAll();
        if (!$users) {
            $this->outWarning('Пользователь с ID %d не найден, добавьте его в группу вручную', self::TEST_USER_ID);

            return;
        }

        if ($this->isMember($groupId)) {
            return;
        }

        $result = UserGroupTable::add(['USER_ID' => self::TEST_USER_ID, 'GROUP_ID' => $groupId]);
        if (!$result->isSuccess()) {
            throw new MigrationException(implode(PHP_EOL, $result->getErrorMessages()));
        }
    }

    public function down()
    {
        $groupHelper = $this->getHelperManager()->UserGroup();
        $groupId = (int)$groupHelper->getGroupId(self::GROUP_CODE);

        if ($groupId > 0 && $this->isMember($groupId)) {
            UserGroupTable::delete(['USER_ID' => self::TEST_USER_ID, 'GROUP_ID' => $groupId]);
        }

        $groupHelper->deleteGroup(self::GROUP_CODE);
    }

    private function isMember(int $groupId): bool
    {
        return (bool)UserGroupTable::query()
            ->setSelect(['USER_ID'])
            ->where('USER_ID', self::TEST_USER_ID)
            ->where('GROUP_ID', $groupId)
            ->fetchAll();
    }
}
