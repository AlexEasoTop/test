<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = [
    'NAME'        => GetMessage('COMPONENT_NAME'),
    'DESCRIPTION' => GetMessage('COMPONENT_DESC'),
    'CACHE_PATH'  => 'Y',
    'PATH'        => [
        'ID'   => 'ipcheck',
        'NAME' => 'Тестовые компоненты'
    ]
];
