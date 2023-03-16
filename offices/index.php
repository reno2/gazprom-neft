<?

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Новости компании");
?>

<?php
/** @global CMain $APPLICATION */

$APPLICATION->IncludeComponent(
    'gaz:offices.list',
    '.default',
    [
        'CACHE_TIME' => 3600,
        'ZOOM' => 12,
        'CENTER' => '59.967515,30.274616'
    ],
    false
);
?>


<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php"); ?>