<?php

declare(strict_types=1);

namespace Local\Equipment\Repository;

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\SystemException;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Main\UserFieldTable;

/**
 * Чтение HL-блока «Оборудование». Каждый запрос ограничен сделкой.
 */
final class EquipmentRepository
{
    public const HLBLOCK_NAME = 'Equipment';

    private const SORTABLE = ['ID', 'UF_NAME', 'UF_TYPE', 'UF_CREATED_TIME'];

    private ?array $hlblock = null;

    /** @var class-string<DataManager>|null */
    private ?string $dataClass = null;

    /**
     * @param list<array{0: string, 1: string, 2: mixed}> $conditions условия из FilterMapper::toConditions()
     * @return list<EquipmentItemDTO>
     */
    public function getList(int $dealId, array $conditions, array $order, PageNavigation $nav): array
    {
        $query = $this->getDataClass()::query()
            ->setSelect(['ID', 'UF_NAME', 'UF_TYPE', 'UF_CREATED_TIME', 'UF_DEAL_ID'])
            ->where('UF_DEAL_ID', $dealId)
            ->setOrder($this->sanitizeOrder($order))
            ->setOffset($nav->getOffset())
            ->setLimit($nav->getLimit())
            ->countTotal(true);
        if ($conditions) {
            $query->where($conditions);
        }

        $result = $query->exec();
        $nav->setRecordCount($result->getCount());

        return array_map(EquipmentItemDTO::fromRow(...), $result->fetchAll());
    }

    public function countByDeal(int $dealId): int
    {
        return (int)$this->getDataClass()::query()
            ->where('UF_DEAL_ID', $dealId)
            ->queryCountTotal();
    }

    /**
     * @return array<int, string> ID значения списка => подпись
     */
    public function getTypeEnum(): array
    {
        $fields = UserFieldTable::query()
            ->setSelect(['ID'])
            ->where('ENTITY_ID', 'HLBLOCK_' . $this->getHlblock()['ID'])
            ->where('FIELD_NAME', 'UF_TYPE')
            ->fetchAll();
        if (!$fields) {
            return [];
        }

        // D7-таблицы значений списка в main нет, поэтому значения берём через CUserFieldEnum.
        $items = [];
        $enum = \CUserFieldEnum::GetList(['SORT' => 'ASC'], ['USER_FIELD_ID' => $fields[0]['ID']]);
        while ($value = $enum->Fetch()) {
            $items[(int)$value['ID']] = (string)$value['VALUE'];
        }

        return $items;
    }

    private function sanitizeOrder(array $order): array
    {
        $result = [];
        foreach ($order as $field => $direction) {
            $field = strtoupper((string)$field);
            if (in_array($field, self::SORTABLE, true)) {
                $result[$field] = strtoupper((string)$direction) === 'ASC' ? 'ASC' : 'DESC';
            }
        }

        return $result ?: ['ID' => 'DESC'];
    }

    /**
     * @return class-string<DataManager>
     */
    private function getDataClass(): string
    {
        return $this->dataClass ??= HighloadBlockTable::compileEntity($this->getHlblock())->getDataClass();
    }

    private function getHlblock(): array
    {
        if ($this->hlblock === null) {
            $rows = HighloadBlockTable::query()
                ->setSelect(['*'])
                ->where('NAME', self::HLBLOCK_NAME)
                ->fetchAll();
            if (!$rows) {
                throw new SystemException(sprintf('HL-блок %s не найден, примените миграции', self::HLBLOCK_NAME));
            }
            $this->hlblock = $rows[0];
        }

        return $this->hlblock;
    }
}
