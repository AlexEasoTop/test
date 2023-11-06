<?php

require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");

\Bitrix\Main\Loader::includeModule("highloadblock");
use Bitrix\Highloadblock\HighloadBlockTable;

// добавляем ХЛ блок
$obResult = HighloadBlockTable::add(array(
    'NAME' => 'IPCheckGeo',
    'TABLE_NAME' => 'test_ip_check_geo'
));

if ($obResult->isSuccess()) {

    $idHLBlock = $obResult->getId();

    // перечень филдов для блока, можно расширить список до всех полей, который возвращает геодата
    $arFieldsCodeIp = array(
        "IP" => "string",
        "REGION" => "string",
        "CITY" => "string",
        "OKATO" => "integer",
        "POST" => "string",
        "LAT" => "string",
        "LONG" => "string",
    );

    $userTypeEntity = new CUserTypeEntity();

    foreach ($arFieldsCodeIp as $fieldName => $fieldType) {
        $fieldParams = [
            'ENTITY_ID' => "HLBLOCK_".$idHLBlock,
            'FIELD_NAME' => "UF_".$fieldName,
            'USER_TYPE_ID' => $fieldType,
            'MANDATORY' => 'N',
            'SHOW_FILTER' => 'S',
            'IS_SEARCHABLE' => 'N',
            'EDIT_FORM_LABEL'   => array(
                'ru'    => $fieldType,
                'en'    => $fieldType,
            )
        ];

        $userTypeEntity->Add($fieldParams);
    }
    echo 'Migration completed';
} else {
    // если ошибка, то выводим ее в консоль или на страницу (смотря, где выполняется миграция)
    $errorMessages = $obResult->getErrorMessages();

    if (is_array($errorMessages)) {
        foreach ($errorMessages as $errorMessage) {
            echo $errorMessage.'/n';
        }
    } else {
        echo $errorMessages;
    }
}