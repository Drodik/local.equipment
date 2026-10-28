<?php

namespace Sprint\Migration;

class Version20261028000001 extends Version
{
    protected $description = 'HL-блок «Оборудование»';

    protected $moduleVersion = '5.16.1';

    /**
     * @throws Exceptions\HelperException
     */
    public function up()
    {
        $helper = $this->getHelperManager();

        $hlblockId = $helper->Hlblock()->saveHlblock([
            'NAME' => 'Equipment',
            'TABLE_NAME' => 'b_hlbd_equipment',
            'LANG' => [
                'ru' => ['NAME' => 'Оборудование'],
            ],
        ]);

        $helper->Hlblock()->saveField($hlblockId, $this->makeField('UF_NAME', 'string', 100, 'Y', 'Название'));

        $helper->Hlblock()->saveField($hlblockId, $this->makeField('UF_TYPE', 'enumeration', 200, 'Y', 'Тип оборудования') + [
            'ENUM_VALUES' => [
                ['VALUE' => 'Роутер', 'DEF' => 'N', 'SORT' => 100, 'XML_ID' => 'router'],
                ['VALUE' => 'Ноутбук', 'DEF' => 'N', 'SORT' => 200, 'XML_ID' => 'laptop'],
                ['VALUE' => 'Монитор', 'DEF' => 'N', 'SORT' => 300, 'XML_ID' => 'monitor'],
            ],
        ]);

        $helper->Hlblock()->saveField($hlblockId, $this->makeField('UF_CREATED_TIME', 'datetime', 300, 'N', 'Дата создания'));

        $helper->Hlblock()->saveField($hlblockId, $this->makeField('UF_DEAL_ID', 'integer', 400, 'Y', 'ID сделки'));
    }

    /**
     * @throws Exceptions\HelperException
     */
    public function down()
    {
        $this->getHelperManager()->Hlblock()->deleteHlblockIfExists('Equipment');
    }

    private function makeField(string $name, string $userTypeId, int $sort, string $mandatory, string $label): array
    {
        return [
            'FIELD_NAME' => $name,
            'USER_TYPE_ID' => $userTypeId,
            'XML_ID' => $name,
            'SORT' => $sort,
            'MULTIPLE' => 'N',
            'MANDATORY' => $mandatory,
            'SHOW_FILTER' => 'I',
            'SHOW_IN_LIST' => 'Y',
            'EDIT_IN_LIST' => 'Y',
            'IS_SEARCHABLE' => 'N',
            'EDIT_FORM_LABEL' => ['ru' => $label],
            'LIST_COLUMN_LABEL' => ['ru' => $label],
            'LIST_FILTER_LABEL' => ['ru' => $label],
        ];
    }
}
