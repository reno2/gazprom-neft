<?php

use Bitrix\Iblock\Component\Tools;
use Bitrix\Iblock\IblockTable;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;

class OfficesList extends CBitrixComponent
{

    private const IBLOCK_CODE = 'offices';

    /** @var Cache $cache */
    protected Cache $cache;

    /** @var Bitrix\Main\Data\TaggedCache */
    protected \Bitrix\Main\Data\TaggedCache $taggedCache;

    /**
     * @throws LoaderException|SystemException
     */
    public function onPrepareComponentParams($arParams): array
    {
        $this->cache = Cache::createInstance();
        $this->taggedCache = Application::getInstance()->getTaggedCache();

        $this->arParams['CACHE_TIME'] = intval($arParams['CACHE_TIME']) ?? 3600;
        $this->arParams['IBLOCK'] = $this->getIBlockData();

        return array_merge($this->arParams, $arParams);
    }

    /**
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws ArgumentException
     */
    public function executeComponent()
    {
        // Подготавливаем данные
        $this->getFromCache();
        $this->includeComponentTemplate();
    }

    /**
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws ArgumentException
     */
    public function getFromCache()
    {
        if ($this->arParams["CACHE_TYPE"] == "N") {
            $this->getIBlockElements();
            return;
        }

        $cachePath = SITE_ID.'/'.$this->getRelativePath();
        $cacheKey = md5(serialize([static::class, $this->arParams]));
        $cacheTtl = $this->arParams["CACHE_TIME"];

        if ($this->cache->initCache($cacheTtl, $cacheKey, $cachePath)) {
            // Получаем из кеша
            $this->arResult = $this->cache->getVars();

        } elseif ($this->cache->startDataCache()) {
            $this->getIBlockElements();
            $this->taggedCache->startTagCache($cachePath);
            $this->taggedCache->registerTag("iblock_id_".$this->arParams['IBLOCK']['ID']);
            $this->taggedCache->endTagCache();

            // Если элементов нет сбрасываем кеш
            if (!$this->arResult['ELEMENTS']) {
                $this->cache->abortDataCache();
            }
            // Добавляем в кеш
            $this->cache->endDataCache([
                'ELEMENTS' => $this->arResult['ELEMENTS'],
                'MAP_ID'   => $this->arResult['MAP_ID']]
            );
        }
    }

    /**
     * @return void
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function getIBlockElements(): void
    {
        $iBlock = \Bitrix\Iblock\Iblock::wakeUp($this->arParams['IBLOCK']['ID']);
        $now = new \Bitrix\Main\Type\DateTime();

        $parameters = [
            'filter' => [
                '=ACTIVE' => 'Y',
                'LOGIC' => 'AND',
                [
                    'LOGIC' => 'OR',
                    '>=ACTIVE_TO' => $now,
                    'ACTIVE_TO' => null,
                ],
                [
                    'LOGIC' => 'OR',
                    '<=ACTIVE_FROM' => $now,
                    'ACTIVE_FROM' => null,
                ]
            ],
            'order' => [
                'SORT' => 'DESC',
            ],
            'select' => [
                'ID',
                'NAME',
                'ACTIVE',
                'SORT',
                'CITY',
                'PHONE',
                'EMAIL',
                'COORDS'
            ],
        ];

        $elementObjects = $iBlock->getEntityDataClass()::getList($parameters)
            ->fetchCollection()
            ->getAll();

        if (!$elementObjects) {
            $this->show404();
        }

        foreach ($elementObjects as $object) {
            $id = $object->getId();
            $this->arResult['ELEMENTS'][$id] = [
                'ID' => $id,
                'NAME' => $object->get('NAME'),
                'PHONE' => $object->getPhone()->getValue(),
                'EMAIL' => $object->getEmail()->getValue(),
                'COORDS' => $object->getCoords()->getValue(),
                'CITY' => $object->getCity()->getValue()
            ];
        }
        $this->arResult['MAP_ID'] = uniqid();
    }


    /**
     * @throws LoaderException
     * @throws SystemException
     */
    private function getIBlockData(): array
    {
        if (!Loader::includeModule('iblock')) {
            return [];
        }
        $parameters = [
            'filter' => [
                'CODE' => [
                    self::IBLOCK_CODE
                ]
            ],
            'select' => [
                'ID',
                'NAME',
                'CODE',
            ]
        ];
        $iBlockObject = IblockTable::getList($parameters)->fetchObject();

        if ($iBlockObject === null) {
            $this->show404();
        }
        return [
            'CODE' => $iBlockObject->getCode(),
            'ID' => $iBlockObject->getId(),
            'NAME' => $iBlockObject->getName(),
        ];
    }


    public function show404(): void
    {
        Tools::process404(
            '',
            true,
            true,
            true,
        );
    }
}