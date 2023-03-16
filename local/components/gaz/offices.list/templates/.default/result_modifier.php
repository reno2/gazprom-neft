<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}


/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */



if(!$arResult['ELEMENTS']){
    return;
}

$arResult['MAP_ELEMENTS']['MAP_ID'] = $arResult['MAP_ID'];
$arResult['MAP_ELEMENTS']['CENTER'] = $arParams['CENTER'] ?? '';
$arResult['MAP_ELEMENTS']['ZOOM'] = $arParams['ZOOM'] ?? '';
$arResult['MAP_ELEMENTS']['MARKER'] = '/local/components/gaz/offices.list/templates/.default/images/market.png';

foreach ($arResult['ELEMENTS'] as $key => $item){
    if(!$item['COORDS']){
        continue;
    }
    $tmp = [];
    $tmp['COORDS'] = $item['COORDS'];
    $tmp['ID'] = $item['ID'];
    $balloon =  '<p class="balloon__title">' . $item["NAME"] . '</p>';
    if($item['PHONE']){
        $balloon .=  '<p class="balloon__phone"><a href="tel:' . $item['PHONE'] . '">' .  $item['PHONE'] . '</a></p>';
    }
    if($item['CITY']){
        $balloon .=  '<p class="balloon__city">'.  $item['CITY'] . '</p>';
    }
    if($item['EMAIL']){
        $balloon .=  '<p class="balloon__email">'.  $item['EMAIL'] . '</p>';
    }
    $tmp['BALLOON'] = $balloon;
    $arResult['MAP_ELEMENTS']['ITEMS'][] = $tmp;
}

$arResult['MAP_DATA'] = json_encode($arResult['MAP_ELEMENTS'], JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG);


