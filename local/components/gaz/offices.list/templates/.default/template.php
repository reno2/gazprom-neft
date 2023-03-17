<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var array $templateData */
/** @var CBitrixComponent $component */
CJSCore::Init(["jquery"]);
?>
<?php
if (!$arResult['ELEMENTS']) {
    return;
}

?>


<div class="container">
    <div class="offices-block js_map__<?= $arResult["MAP_ID"] ?>" data-openhandler=".js_balloon__open">
        <div class="offices-block__list">
            <?
            foreach ($arResult['ELEMENTS'] as $office) : ?>
                <div class="offices-block__item js_balloon__open" data-placemarkid='<?="{$arResult["MAP_ID"]}_{$office['ID']}" ?>'>
                    <div class="offices-block__inner">
                        <h5 class="offices-block__title"><?= $office['NAME'] ?></h5>
                        <div class="offices-block__phone"><?= $office['PHONE'] ?></div>
                        <div class="offices-block__city"><?= $office['CITY'] ?></div>
                        <div class="offices-block__email"><?= $office['EMAIL'] ?></div>
                    </div>
                </div>
            <?
            endforeach; ?>
        </div>
        <div class="offices-block__map">
            <div class="offices-block__ymap js_mapInit" data-mapid="<?= $arResult["MAP_ID"] ?>"></div>
        </div>
    </div>


</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const yaMap = new YaMaps(<?=$arResult['MAP_DATA'];?>)
        yaMap.init()
    })
</script>



