<?php

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

require($_SERVER['DOCUMENT_ROOT'].'/local/includes/Migrate/MigrateIBlock.php');
require($_SERVER['DOCUMENT_ROOT'].'/local/includes/Migrate/MigrateElements.php');



$iBlockTypeParams = [
    'ID' => 'examples_type',
];
$iBlockParams = [
    'IBLOCK_TYPE_ID' => 'examples_type',
    'LID' =>
        [
            0 => 's1',
        ],
    'CODE' => 'offices',
    'API_CODE' => 'officesApi',
    'NAME' => 'Оффисы для публичной части',
    'ACTIVE' => 'Y',
    'LIST_PAGE_URL' => '#SITE_DIR#/test/',
    'DETAIL_PAGE_URL' => '#SITE_DIR#/test/#CODE#/',
    'SECTION_PAGE_URL' => '#SITE_DIR#/test/#CODE#/',
];

$iBlockPropsParams = [
    [
        "NAME" => "Телефон",
        "CODE" => "PHONE",
        'SORT' => 500
    ],
    [
        "NAME" => "Почта",
        "CODE" => "EMAIL",
        'SORT' => 510
    ],
    [
        "NAME" => "Координаты",
        "CODE" => "COORDS",
        'USER_TYPE' => 'map_yandex',
        'SORT' => 530
    ],
    [
        "NAME" => "Город",
        "CODE" => "CITY",
        'SORT' => 540
    ],
];

$migrateBlockType = new MigrateIBlock($iBlockTypeParams, $iBlockParams, $iBlockPropsParams);
try{
    $resultIBlocKType = $migrateBlockType->up();
    var_dump($resultIBlocKType);
}catch (Throwable $exception){
    var_dump($exception->getMessage());
}


$migrationElements = new MigrateElements('offices', $iBlockParams['CODE']);
try {
    $resultAdded = $migrationElements->up();
    var_dump($resultAdded);
}catch (Throwable $exception){
    var_dump($exception->getMessage());
}