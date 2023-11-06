<?php

use Bitrix\Highloadblock\HighloadBlockTable,
    Bitrix\Main\Web\HttpClient,
    Bitrix\Main\Engine\ActionFilter,
    Bitrix\Main\Engine\Contract\Controllerable,
    Bitrix\Main\Errorable,
    Bitrix\Main\ErrorCollection,
    Bitrix\Main\Error,
    Bitrix\Main\Loader;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

/**
 *  класс компонента, отвечает за проверку IP пользователя. Делает запрос в HLBlock, елси информации не найдено -
 *  запрашивает у сервиса SypexGeo
 */
class IPCheck extends \CBitrixComponent implements Controllerable, Errorable
{
    protected ErrorCollection $errorCollection;

    const SYPEX_URI = 'https://api.sypexgeo.net/json/';
    const HLBLOCK_NAME = 'IPCheckGeo';
    const HIGHLOAD_BLOCK_CACHE_TTL = 60 * 60 * 24 * 30 * 12; // кеш выборки данных из хлблока

    private $httpClientOptions = [
        'socketTimeout' => 2,
    ];

    public function onPrepareComponentParams($arParams)
    {
        $this->errorCollection = new ErrorCollection();
        return $arParams;
    }

    public function getErrors(): array
    {
        return $this->errorCollection->toArray();
    }

    public function getErrorByCode($code): Error
    {
        return $this->errorCollection->getErrorByCode($code);
    }

    /**
     * конфигурационный метод, описывающий экшены для аякса
     * @return array[][]
     */
    public function configureActions(): array
    {
        return [
            'check' => [
                'prefilters' => [
                    new ActionFilter\Authentication(),
                    new ActionFilter\HttpMethod(['POST']),
                ]
            ]
        ];
    }

    /**
     * метод аякса, принимающий на вход IP, введеный пользователем
     * @param string $ip
     * @return array|bool
     * @throws Exception
     */
    public function checkAction(string $ip): array
    {
        if ($this->validateIP($ip)) {
            try {
                $arDataIP = $this->checkIPInHLBlock($ip);
            } catch (Exception $e) {
                $this->errorCollection[] = new Error($e->getMessage());
                return false;
            }

            if (!$arDataIP) {
                $arDataIP = $this->sendIP($ip);
            }

            return $arDataIP;
        }

        return [];
    }

    private function validateIP(string $IP): bool
    {
        if (filter_var($IP, FILTER_VALIDATE_IP)) {
            return true;
        } else {
            $this->errorCollection[] = new Error('IP is not valid!');
//            throw new Exception('IP is not valid!');
        }
        return false;
    }

    /**
     * метод получает сущность HLBlock для дальнейшей работы с ней
     * @return \Bitrix\Main\ORM\Data\DataManager|string
     */
    private function getHLBlockEntity(): \Bitrix\Main\ORM\Data\DataManager | string
    {
        try {
            Loader::includeModule('highloadblock');
            $hlBlock = HighloadBlockTable::getRow(
                [
                    'filter' => [
                        '=NAME' => self::HLBLOCK_NAME,
                    ],
                ]
            );
            $entity = HighloadBlockTable::compileEntity($hlBlock);
        } catch (Exception $e) {
            $this->errorCollection[] = new Error($e->getMessage());
        }

        return $entity->getDataClass();
    }

    /**
     *
     * метод чекает наличие IP в HLBlock, если есть, то возвращает инфу по нему
     *
     * @param string $IP
     * @return bool|array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    private function checkIPInHLBlock(string $IP): bool|array
    {
        $arGeoData = [];

        $HLBlockEntity = $this->getHLBlockEntity();

        $obIPGeoData = $HLBlockEntity::getList(
            [
                'select' => [
                    'UF_REGION',
                    'UF_CITY',
                    'UF_OKATO',
                    'UF_POST',
                    'UF_LAT',
                    'UF_LONG'
                ],
                'filter' => [
                    '=UF_IP' => $IP
                ],
                'cache' => [
                    'ttl' => self::HIGHLOAD_BLOCK_CACHE_TTL
                ]
            ]
        );

        if ($obIPGeoData->getSelectedRowsCount()) {
            while ($arIPGeoData = $obIPGeoData->fetch()) {
                $arGeoData['IP'] = $IP;
                $arGeoData['region'] = $arIPGeoData['UF_REGION'];
                $arGeoData['city'] = $arIPGeoData['UF_CITY'];
                $arGeoData['okato'] = $arIPGeoData['UF_OKATO'];
                $arGeoData['post'] = $arIPGeoData['UF_POST'];
                $arGeoData['coords'] = $arIPGeoData['UF_LAT'].' '.$arIPGeoData['UF_LONG'];
            }
            return $arGeoData;
        }

        return false;
    }

    /**
     * Метод добавляет запись об IP в HLBlock
     * @param array $geoData
     * @return bool
     * @throws Exception
     */
    private function insertIPGeoDataInHLBlock(array $geoData): void
    {
        try {
            $HLBlockEntity = $this->getHLBlockEntity();
            $HLBlockEntity::add($geoData);
        } catch (Exception $e) {
            $this->errorCollection[] = new Error($e->getMessage());
        }
    }

    /**
     * Метод делает запрос в сервис SypexGeo для получения информации об IP
     *
     * @param string $IP
     * @return array
     * @throws Exception
     */
    private function sendIP(string $IP): array
    {
        $endpoint = self::SYPEX_URI.$IP;

        $httpClient = new HttpClient($this->httpClientOptions);
        $httpClientResponse = $httpClient->get($endpoint);

        $arGeoData = $this->prepareGeoData(json_decode($httpClientResponse, true));

        $this->insertIPGeoDataInHLBlock($arGeoData);

        return $arGeoData;
    }

    /**
     * Метод подготоваливает данные, возвращаемые из SypexGeo, в нужный нам формат
     *
     * @param array $geoData
     * @return array
     */
    private function prepareGeoData(array $geoData): array
    {
        $arGeoData = [];

        $arGeoData['UF_IP'] = $geoData['ip'];
        $arGeoData['UF_REGION'] = $geoData['region']['name_ru'];
        $arGeoData['UF_CITY'] = $geoData['city']['name_ru'];
        $arGeoData['UF_OKATO'] = (integer)$geoData['region']['okato'];
        $arGeoData['UF_POST'] = $geoData['city']['post'];
        $arGeoData['UF_LAT'] = $geoData['city']['lat'];
        $arGeoData['UF_LONG'] = $geoData['city']['lon'];

        return $arGeoData;
    }

    /**
     * метод генерирует форму, для проверки IP
     * @return mixed|void|null
     */
    public function executeComponent()
    {
        $componentPage = 'form';
        $this->includeComponentTemplate($componentPage);
    }
}