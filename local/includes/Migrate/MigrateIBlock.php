<?php

use Bitrix\Iblock\IblockTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use CIBlock;
use CIBlockType;
use Bitrix\Main\Loader;
use stringEncode\Exception;
use Throwable;

class MigrateIBlock
{

    private array $typeParams;
    private array $iBlockParams;
    private array $propsParams;

    private array $propsErrors = [];
    private array $propsSuccess = [];

    const IBLOCK_TYPE_DEFAULTS = [
        'ID' => '',
        'SECTIONS' => 'Y',
        'IN_RSS' => 'N',
        'SORT' => 100,
        'LANG' => [
            'ru' => [
                'NAME' => 'Офисы',
                'SECTION_NAME' => 'Sections',
                'ELEMENT_NAME' => 'Elements',
            ],
            'en' => [
                'NAME' => 'Offices',
                'SECTION_NAME' => 'Sections',
                'ELEMENT_NAME' => 'Elements',
            ],
        ],
    ];

    const IBLOCK_DEFAULTS = [
        'ACTIVE' => 'Y',
        'NAME' => '',
        'CODE' => '',
        'LIST_PAGE_URL' => '',
        'DETAIL_PAGE_URL' => '',
        'SECTION_PAGE_URL' => '',
        'IBLOCK_TYPE_ID' => 'main',
        'LID' => ['s1'],
        'SORT' => 500,
        'GROUP_ID' => ['2' => 'R'],
        'VERSION' => 2,
        'BIZPROC' => 'N',
        'WORKFLOW' => 'N',
        'INDEX_ELEMENT' => 'N',
        'INDEX_SECTION' => 'N',
        "FIELDS" => [
            'CODE' => [
                "IS_REQUIRED" => "Y", // Обязательное
                "DEFAULT_VALUE" => [
                    "UNIQUE" => "Y", // Проверять на уникальность
                    "TRANSLITERATION" => "Y", // Транслитерировать
                    "TRANS_LEN" => "30", // Максмальная длина транслитерации
                    "TRANS_CASE" => "L", // Приводить к нижнему регистру
                    "TRANS_SPACE" => "-", // Символы для замены
                    "TRANS_OTHER" => "-",
                    "TRANS_EAT" => "Y",
                    "USE_GOOGLE" => "N",
                ]
            ]
        ]
    ];

    const IBLOCK_PROPERTY_DEFAULTS = [
        'NAME' => '',
        'ACTIVE' => 'Y',
        'SORT' => '500',
        'CODE' => '',
        'PROPERTY_TYPE' => 'S',
        'USER_TYPE' => '',
        'ROW_COUNT' => '1',
        'COL_COUNT' => '30',
        'LIST_TYPE' => 'L',
        'MULTIPLE' => 'N',
        'IS_REQUIRED' => 'N',
        'FILTRABLE' => 'Y',
    ];

    public function __construct($typeParams, $iBlockParams, $propsParams)
    {
        $this->typeParams = $typeParams;
        $this->iBlockParams = $iBlockParams;
        $this->propsParams = $propsParams;
    }


    public function up()
    {
        try {
            // Содаём если нет тип инфоблока
            $iBlockType = $this->createIBlockType();
            // Создаём если нет инфоблок
            $iblockId = $this->createIBlock();
            // Создаём если нет свойства инфоблока
            $this->addProperty($iblockId);

            return [$iBlockType, $iblockId, $this->propsSuccess];
        } catch (Throwable $e) {
            throw $e;
        }
    }

    /**
     * Создаём тип инфоблока с проверкой на существование
     * @return mixed|string
     * @throws LoaderException
     * @throws Exception
     */
    private function createIBlockType()
    {
        Loader::includeModule("iblock");

        $dbIbt = \CIBlockType::GetByID($this->typeParams['ID']);
        $arIbt = $dbIbt->GetNext();

        if ($arIbt) {
            return $arIbt['ID'];
        }

        $fields = array_replace_recursive(self::IBLOCK_TYPE_DEFAULTS, $this->typeParams);

        $ib = new CIBlockType;
        if ($ib->Add($fields)) {
            return $fields['ID'];
        }

        throw new Exception($ib->LAST_ERROR);
    }


    /**
     * @throws LoaderException
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws Exception
     */
    private function createIBlock()
    {
        Loader::includeModule("iblock");

        if ($iBlock = $this->getIBlockData($this->iBlockParams['CODE'])) {
            return $iBlock['ID'];
        }

        $fields = array_replace_recursive(self::IBLOCK_DEFAULTS, $this->iBlockParams);
        $ib = new CIBlock;

        if ($iBlockId = $ib->Add($fields)) {
            return $iBlockId;
        }

        throw new Exception($ib->LAST_ERROR);
    }

    /**
     * @throws LoaderException|Exception
     */
    private function addProperty($iblockId): void
    {
        Loader::includeModule("iblock");

        $this->getIBlockProps($iblockId);

        if (!$this->propsParams) {
            return;
        }

        $ib = new \CIBlockProperty;
        foreach ($this->propsParams as $prop) {
            $newProp = array_merge(self::IBLOCK_PROPERTY_DEFAULTS, $prop);

            $newProp['IBLOCK_ID'] = $iblockId;
            $propertyId = $ib->Add($newProp);

            if ($propertyId) {
                $this->propsSuccess[$newProp['NAME']] = $propertyId;
            } else {
                $this->propsErrors[$newProp['NAME']] = $ib->LAST_ERROR;
            }
        }
        if (count($this->propsErrors)) {
            throw new Exception(json_encode($this->propsErrors));
        }
    }


    /**
     * Создаём инфоблок с проверкой на существование
     * @param $iBlockCode
     * @return array
     * @throws LoaderException
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function getIBlockData($iBlockCode): array
    {
        Loader::includeModule('iblock');
        $parameters = [
            'filter' => [
                'CODE' => [
                    $iBlockCode
                ]
            ]
        ];
        $iBlockObject = IblockTable::getList($parameters)->fetchObject();
        if (empty($iBlockObject)) {
            return [];
        }
        return [
            'ID' => $iBlockObject->getId(),
            'CODE' => $iBlockObject->getCode()
        ];
    }

    /**
     * @throws LoaderException
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function getIBlockProps($iBlockId): void
    {
        Loader::includeModule("iblock");

        $props = \Bitrix\Iblock\PropertyTable::getList(
            [
                "select" => ["ID", 'CODE'],
                "filter" => ["IBLOCK_ID" => $iBlockId, "CODE" => array_column($this->propsParams, 'CODE')],
            ]
        )->fetchAll();

        if (!$props) {
            return;
        }

        $codes = array_column($props, 'CODE');
        foreach ($this->propsParams as $key => $prop) {
            if (in_array($prop['CODE'], $codes)) {
                $this->propsSuccess[$prop['NAME']] = 'exists';
                unset($this->propsParams[$key]);
            }
        }
    }

}