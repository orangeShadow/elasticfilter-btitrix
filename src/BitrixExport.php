<?php
declare(strict_types=1);


namespace OrangeShadow\ElasticSearch;

use OrangeShadow\ElasticSearch\Config\IndexConfig;
use OrangeShadow\ElasticSearch\Config\IndexMappingElement;
use OrangeShadow\ElasticSearch\Contracts\Mapable;
use OrangeShadow\ElasticSearch\Properties\SearchProperty;
use Tightenco\Collect\Support\Collection;
use Bitrix\Main\Loader;
use Bitrix\Highloadblock\HighloadBlockTable as HL;

class BitrixExport
{
    /**
     * @var IndexConfig
     */
    private $config;

    /**
     * @var ElasticManager
     */
    private $manager;

    /**
     * Поле по которому создаем ключ в эластике
     * @var string
     */
    private $uniqueIdField;


    /**
     * Проперти свойства ссылающееся на товар
     * @var string
     */
    protected $offerLinkedProperty = "PROPERTY_CML2_LINK_VALUE";

    /**
     * @var bool
     */
    private $onlyActive = false;

    /**
     * Ленивая загрузка HL данных
     * @var array
     */
    private $hlData = [];

    /**
     * @var array ["IBLOCK_ID"=>["PROPERTY_CODE"=>[...]]]
     */
    private $enumProperty = [];

    /**
     * @var array
     */
    private $parentsSectionById;

    /**
     * @var array
     */
    private $sectionCodeById;


    /**
     * Цепочка секций
     * @var array
     */
    private $sectionsChain;

    /**
     * Разделитель для транслита
     */
    public const TR_REPLACE = '_';

    /**
     * Параметры транслитерации
     */
    public const TR_PARAMS = [
        'max_len'               => 255,
        'change_case'           => 'L', // 'L' - toLower, 'U' - toUpper, false - do not change
        'replace_space'         => self::TR_REPLACE,
        'replace_other'         => self::TR_REPLACE,
        'delete_repeat_replace' => true,
        'safe_chars'            => '',
    ];

    /**
     * @return string
     */
    public function getOfferLinkedProperty(): string
    {
        return $this->offerLinkedProperty;
    }

    /**
     * @param string $offerLinkedProperty
     */
    public function setOfferLinkedProperty(string $offerLinkedProperty): void
    {
        $this->offerLinkedProperty = $offerLinkedProperty;
    }

    /**
     * BitrixExport constructor.
     * @param IndexConfig $config
     * @param string $uniqueIdField
     * @param bool $onlyActive
     */
    public function __construct(IndexConfig $config, string $uniqueIdField, bool $onlyActive = false)
    {
        $this->config = $config;

        $this->manager = new ElasticManager($config);

        $this->uniqueIdField = $uniqueIdField;
        $this->onlyActive = $onlyActive;
    }

    /**
     * @param int $elementId
     * @param bool $offer
     * @param IndexConfig $config
     * @param string $uniqueIdField
     * @param bool $onlyActive
     */
    public static function updateSingleElement(
        int $elementId,
        bool $offer,
        IndexConfig $config,
        string $uniqueIdField,
        bool $onlyActive = false
    ) {
        $export = new static($config, $uniqueIdField, $onlyActive);

        $export->loadHlData();
        $export->loadEnumData();

        if ($offer) {
            $export->loadOfferData($elementId);
        } else {
            $export->loadMainData($elementId);
        }
    }

    /**
     * Запуск экспорта
     */
    public function run(): void
    {
        if (!\CModule::IncludeModule('iblock')) {
            return;
        }

        $this->loadHlData();
        $this->loadEnumData();

        if (empty($this->config->getIBlockId())) {
            return;
        }

        $this->loadMainData();

        if (empty($this->config->getOfferIBlockId())) {
            return;
        }

        $this->loadOfferData();
    }

    /**
     * Загружаем данные с HL
     * По умолчанию UF_XML_ID , UF_NAME
     * @return void
     */
    protected function loadHlData(): void
    {
        Loader::includeModule("highloadblock");

        $hlTables = $this->config->getMapping()->filter(function (IndexMappingElement $element) {
            return $element->getBitrixHL() !== null;
        })->map(function (IndexMappingElement $element) {
            return $element->getBitrixHL();
        });

        $allHlTables = [];
        $hlTablesRes = HL::getList();
        while ($hlTable = $hlTablesRes->fetch()) {
            $allHlTables[ $hlTable['TABLE_NAME'] ] = $hlTable;
        }

        foreach ($hlTables as $tableName) {
            if (empty($allHlTables[ $tableName ])) {
                continue;
            }
            $obEntity = HL::compileEntity($allHlTables[ $tableName ]);
            $sEntityDataClass = $obEntity->getDataClass();

            $obRes = $sEntityDataClass::getList([]);
            while ($arRes = $obRes->fetch()) {
                if (empty($arRes['UF_XML_ID']) && !isset($arRes['UF_NAME'])) {
                    continue;
                }
                $this->hlData[ $tableName ][ $arRes['UF_XML_ID'] ] = $arRes['UF_NAME'];
            }
        }
    }

    /**
     * Загружаем данные с HL
     * По умолчанию UF_XML_ID , UF_NAME
     * @return void
     */
    protected function loadEnumData(): void
    {
        if (!empty($this->config->getIBlockId())) {
            $this->getEnumPropertyValues($this->config->getIBlockId());
        }

        if (!empty($this->config->getOfferIBlockId())) {
            $this->getEnumPropertyValues($this->config->getOfferIBlockId());
        }
    }

    /**
     * @param $iBlockId
     */
    protected function getEnumPropertyValues($iBlockId)
    {
        $res = \CIBlockProperty::GetList([], ['IBLOCK_ID' => $iBlockId, 'PROPERTY_TYPE' => "L"]);
        $result = [];
        while ($row = $res->Fetch()) {
            $resProp = \CIBlockProperty::GetPropertyEnum($row["ID"]);
            $values = [];
            while ($propVal = $resProp->Fetch()) {
                $values[ $propVal["ID"] ] = [
                    'title'    => $propVal["VALUE"],
                    'keyId'    => strval($propVal["ID"]),
                    'value'    => $this->prepareValue($propVal["XML_ID"]),
                    'computed' => $this->prepareComputed($propVal["XML_ID"], $propVal["VALUE"]),
                ];
            }
            $result[ $row["CODE"] ] = $values;
        }
        $this->enumProperty[ $iBlockId ] = $result;
    }

    /**
     * Прогрузка основных данных
     * @param int $id
     */
    protected function loadMainData(int $id = 0): void
    {
        $arFilter = ($id > 0) ? ['ID' => $id] : [];

        $arFilter['IBLOCK_ID'] = $this->config->getIBlockId();

        if ($this->onlyActive) {
            $arFilter['ACTIVE'] = 'Y';
        }

        $arSelect = $this->getSelectArray($this->config->getMapping());

        $arSelect[] = 'IBLOCK_SECTION_ID';

        $res = \CIblockElement::GetList(["ID" => "DESC"],
            $arFilter,
            false,
            false,
            $arSelect
        );

        while ($elem = $res->Fetch()) {

            //Если нет уникального унификатора уходим
            if (empty($elem[ $this->uniqueIdField ])) {
                continue;
            }

            //Cобираем данные для добавления в Elastic
            try {
                $source = $this->collectElementSourceForElastic($elem, $this->config->getMapping());

                if (isset($this->sectionsChain[ $elem['IBLOCK_SECTION_ID'] ])) {
                    $source['section_chain_ru'] = $this->sectionsChain[ $elem['IBLOCK_SECTION_ID'] ];
                }

                if ($id > 0)
                    $this->manager->updateElement($elem[ $this->uniqueIdField ], $source);
                else
                    $this->manager->addElement($elem[ $this->uniqueIdField ], $source);
            } catch (\Throwable $e) {
                //TODO::Добавить логирование
            }
        }
    }

    /**
     * Прогрузка офферов данных
     */
    protected function loadOfferData(int $id = 0): void
    {
        $arFilter = ($id > 0) ? [str_replace('_VALUE', '', $this->offerLinkedProperty) => $id] : [];

        $arFilter['IBLOCK_ID'] = $this->config->getOfferIBlockId();
        $arFilter['ACTIVE'] = "Y";

        $offerIndexMapperElement = $this->config->getMapping()->filter(function ($item) {
            return $item->getName() === 'offers';
        })->first();

        if (empty($offerIndexMapperElement)) {
            return;
        }

        $mapping = new Collection($offerIndexMapperElement->getProperties());

        $arSelect = $this->getSelectArray($mapping);

        $res = \CIBlockElement::GetList(
            [
                str_replace('_VALUE', '', $this->offerLinkedProperty) => "DESC"
            ],
            $arFilter,
            false,
            false,
            $arSelect
        );

        if (empty($id)) {
            $stores = $this->getStoreAmountByProductId($this->config->getOfferIBlockId());
        }


        $elLink = null;
        $elArr = [];
        while ($elem = $res->Fetch()) {
            //Если нет ссылки на товар уходим
            if (empty($elem[ $this->offerLinkedProperty ])) {
                continue;
            }

            if (!empty($id)) {
                $stores = $this->getStoreAmountByProductId($this->config->getOfferIBlockId(), [$elem['ID']]);
            }

            if (isset($stores[ $elem['ID'] ]) && $stores[ $elem['ID'] ] > 0) {
                $elem['STORE_QUANTITY'] = $stores[ $elem['ID'] ] > 0;
            } else {
                continue;
                //$elem['STORE_QUANTITY'] = false;
            }


            //Собираем данные для обновления имеющейся записи в Elastic
            try {
                if ($elLink !== null && $elLink !== $elem[ $this->offerLinkedProperty ]) {
                    if (!empty($elArr)) {
                        //TODO: Поиск по BitrixID, обновляю offer
                        $searchProperty = new SearchProperty(['bitrixId' => (int)$elLink]);
                        $main = $this->manager->search($searchProperty);
                        if (!is_null($main)) {
                            $first = $main[0];
                            $this->manager->updateDiffElement($first['_id'], ['offers' => $elArr]);
                        }
                    }
                    $elLink = $elem[ $this->offerLinkedProperty ];
                    $elArr = [];
                }

                if ($elLink === null) {
                    $elLink = $elem[ $this->offerLinkedProperty ];
                }

                $source = $this->collectElementSourceForElastic($elem, $mapping);
                $elArr[] = $source;
            } catch (\Exception $e) {
                //TODO::Добавить логирование
            }
        }

        if (empty($elArr)) {
            return;
        }

        //TODO: Поиск по BitrixID, обновляю offer
        $searchProperty = new SearchProperty(['bitrixId' => (int)$elLink]);
        $main = $this->manager->search($searchProperty);
        if (!is_null($main)) {
            $first = $main[0];
            $this->manager->updateDiffElement($first['_id'], ['offers' => $elArr]);
        }
    }


    /**
     * Получить фильтр по складам
     * @return array
     */
    protected function getStoresArray(): array
    {
        $res = \CCatalogStore::GetList(
            [],
            [
                'ACTIVE'     => 'Y',
                '!UF_ONLINE' => false
            ],
            false,
            false,
            ['ID']
        );

        $storeFilter = [];

        while ($row = $res->fetch()) {
            $storeFilter[] = ['>CATALOG_STORE_AMOUNT_' . $row['ID'] => 0];
        }
        $storeFilter = [
            array_merge(['LOGIC' => 'OR'], $storeFilter)
        ];

        return $storeFilter;
    }

    /**
     * Получаем настройки выборки
     * @param Collection $mapping
     * @return array
     */
    protected function getSelectArray(Collection $mapping): array
    {
        $arSelect = [];
        /**
         * @var IndexMappingElement $item
         */
        foreach ($mapping as $item) {
            if ($item->getBitrixIblockPropertyLink() !== null && $item->getBitrixIblockField() !== 'CML2_LINK') {
                $arSelect[] = "PROPERTY_{$item->getBitrixIblockField()}";
                $arSelect[] = "PROPERTY_{$item->getBitrixIblockField()}.NAME";
                $arSelect[] = "PROPERTY_{$item->getBitrixIblockField()}.CODE";
            } elseif (in_array($item->getBitrixIblockFieldType(), [
                BitrixFieldTypeEnum::ENUM_PROPERTY,
                BitrixFieldTypeEnum::HL_PROPERTY,
                BitrixFieldTypeEnum::SIMPLE_PROPERTY,
            ], true)) {
                $arSelect[] = "PROPERTY_" . $item->getBitrixIblockField();
            } elseif (!empty($item->getBitrixIblockField())) {
                $arSelect[] = $item->getBitrixIblockField();
            }
        }

        return $arSelect;
    }

    /**
     * Собираем данные для загрузки в эластику
     * @param array $elem Выборка из базы
     * @param Collection[IndexMappingElement] $mapping
     * @param array $sectionsCodeById
     * @return array
     */
    private function collectElementSourceForElastic(array $elem, Collection $mapping, $sectionsCodeById = []): array
    {
        $source = [];

        /**
         * @var IndexMappingElement $item
         */
        foreach ($mapping as $item) {
            if ($item->getName() === 'offers') {
                continue;
            }

            //Получаем данные из выборки
            $data = $this->getData($item, $elem, $sectionsCodeById);
            if ($data !== false && $this->dataIsEmpty($data)) {
                continue;
            }

            //Преобразуем данные для добавления в эластик
            $source[ $item->getName() ] = $this->modifyDataForElastic($item, $data);
        }

        return $source;
    }

    /**
     * @param $data
     * @return bool
     */
    protected function dataIsEmpty($data): bool
    {
        if (empty($data)) {
            return true;
        }

        if (!is_array($data)) {
            return false;
        }

        $collection = new Collection($data);

        return $collection->filter(function ($item) {
                return !is_null($item);
            })->count() === 0;
    }

    /**
     * @param IndexMappingElement $item
     * @param array $elem
     * @return mixed
     */
    private function getData(IndexMappingElement $item, array $elem)
    {
        $propertyName = $item->getBitrixIblockField();

        switch ($item->getBitrixIblockFieldType()) {
            case BitrixFieldTypeEnum::PROPERTY_VALUE:
                $fieldId = $item->getBitrixIblockField() . "_VALUE";

                return $elem[ $fieldId ];

            case BitrixFieldTypeEnum::NESTED_PROPERTY_VALUE:
                $fieldId = str_replace('.', '_', $item->getBitrixIblockField()) . "_VALUE";

                return $elem[ $fieldId ];

            case BitrixFieldTypeEnum::NESTED_PROPERTY_FIELD:
                $fieldId = str_replace('.', '_', $item->getBitrixIblockField());

                return $elem[ $fieldId ];

            case BitrixFieldTypeEnum::NESTED_PROPERTY_FILE:
                $fieldId = str_replace('.', '_', $item->getBitrixIblockField());

                return \CFile::GetPath($elem[ $fieldId ]);

            case BitrixFieldTypeEnum::CATEGORY_PROPERTY:
                if (!empty($this->sectionCodeById[ $item->getBitrixIblockPropertyLink() ][ $elem["PROPERTY_{$propertyName}_VALUE"] ])) {
                    return $this->sectionCodeById[ $item->getBitrixIblockPropertyLink() ][ $elem["PROPERTY_{$propertyName}_VALUE"] ];
                }

                return null;
            case BitrixFieldTypeEnum::ELEMENT_FIELD:
                return $elem[ $item->getBitrixIblockField() ];
            case BitrixFieldTypeEnum::FILE_FIELD:
                if (!empty($elem[ $item->getBitrixIblockField() ])) {
                    return \CFile::GetPath($elem[ $item->getBitrixIblockField() ]);
                }

                return null;
            case BitrixFieldTypeEnum::ENUM_PROPERTY:
                if (!empty($elem["PROPERTY_{$propertyName}_ENUM_ID"])
                    && isset($this->enumProperty[ $item->getBitrixIblockId() ][ $propertyName ][ $elem["PROPERTY_{$propertyName}_ENUM_ID"] ])) {
                    $values = $this->enumProperty[ $item->getBitrixIblockId() ][ $propertyName ][ $elem["PROPERTY_{$propertyName}_ENUM_ID"] ];
                    if ($item->isHasInFilter()) {
                        return $values;
                    } else {
                        return $values['title'];
                    }
                }

                if (!empty($elem["PROPERTY_{$propertyName}_VALUE"]) && is_array($elem["PROPERTY_{$propertyName}_VALUE"])) {
                    $values = [];
                    foreach ($elem["PROPERTY_{$propertyName}_VALUE"] as $key => $value) {
                        if (!isset($this->enumProperty[ $item->getBitrixIblockId() ][ $propertyName ][ $key ])) {
                            continue;
                        }
                        if ($item->isHasInFilter()) {
                            $values[] = $this->enumProperty[ $item->getBitrixIblockId() ][ $propertyName ][ $key ];
                        } else {
                            $values[] = $this->enumProperty[ $item->getBitrixIblockId() ][ $propertyName ][ $key ]['title'];

                        }
                    }

                    return $values;
                }

                return null;
            case BitrixFieldTypeEnum::HL_PROPERTY:

                if (is_array($elem["PROPERTY_{$propertyName}_VALUE"])) {
                    $result = [];
                    foreach ($elem["PROPERTY_{$propertyName}_VALUE"] as $xmlId) {
                        if (!empty($this->hlData[ $item->getBitrixHL() ][ $xmlId ])) {
                            $title = $this->hlData[ $item->getBitrixHL() ][ $xmlId ];
                            $value = $xmlId ?? $this->translit($this->hlData[ $item->getBitrixHL() ][ $xmlId ]);
                            $result[] = [
                                'title'    => $title,
                                'value'    => $this->prepareValue($value),
                                'keyId'    => '1001',
                                'computed' => $this->prepareComputed($value, $title)
                            ];
                        }
                    }

                    return $result;
                } else if (!empty($this->hlData[ $item->getBitrixHL() ][ $elem["PROPERTY_{$propertyName}_VALUE"] ])) {
                    if (empty($elem["PROPERTY_{$propertyName}_VALUE"])) {
                        return null;
                    }
                    $title = $this->hlData[ $item->getBitrixHL() ][ $elem["PROPERTY_{$propertyName}_VALUE"] ];
                    $value = $elem["PROPERTY_{$propertyName}_VALUE"];

                    return [
                        'title'    => $title,
                        'value'    => $this->prepareValue($value),
                        'keyId'    => '1002',
                        'computed' => $this->prepareComputed($value, $title)
                    ];

                }

                return null;
            case BitrixFieldTypeEnum::SIMPLE_PROPERTY:
                return $elem["PROPERTY_{$propertyName}_VALUE"];

            case BitrixFieldTypeEnum::LINKED_PROPERTY:
                if (empty($elem["PROPERTY_{$propertyName}_NAME"])) {
                    return null;
                }
                $title = $elem["PROPERTY_{$propertyName}_NAME"];
                $id = $elem["PROPERTY_{$propertyName}_VALUE"];
                $value = !empty($elem["PROPERTY_{$propertyName}_CODE"]) ?  $elem["PROPERTY_{$propertyName}_CODE"]:$this->translit($elem["PROPERTY_{$propertyName}_NAME"]);

                return [
                    'title'    => $title,
                    'value'    => $this->prepareValue($value),
                    'keyId'    => strval(intval($id) > 0 ? $id : 0),
                    'computed' => $this->prepareComputed($value, $title)
                ];
            case BitrixFieldTypeEnum::SECTION_FIELD:
                $sections = $this->getSections((int)$item->getBitrixIblockId(), (int)$elem["ID"]);

                if (empty($sections)) {
                    return null;
                }

                return array_unique($sections);

            case BitrixFieldTypeEnum::ANONYMOUS_FUNCTION:
                return $item->getFunction()($elem["ID"]);
        }

        return null;
    }

    /**
     * Модифицируем данные
     * @param Mapable $item
     * @param $data
     */
    private function modifyDataForElastic(Mapable $item, $data)
    {
        if ($item->getType() === TypeEnum::BOOL) {
            return $data === 'Y' || $data === true;
        }

        if ($item->getType() === TypeEnum::FLOAT) {
            return (float)$data;
        }

        if ($item->getType() === TypeEnum::INT) {
            return (int)$data;
        }

        if ($item->getType() === 'nested') {
            if (is_array($data) && empty($data['title'])) {
                $results = [];
                foreach ($data as $subData) {
                    if (empty($subData)) {
                        continue;
                    }
                    $result = [];
                    foreach ($item->getProperties() as $subItem) {
                        if (isset($subData[ $subItem->getName() ])) {
                            $result[ $subItem->getName() ] = $subData[ $subItem->getName() ];
                        }
                    }
                    $results[] = $result;
                }

                return $results;
            }

            $result = [];

            //Берем как выглядит поле из настроек config: например: title, value, keyId, computed
            foreach ($item->getProperties() as $subItem) {
                if (isset($data[ $subItem->getName() ])) {
                    $result[ $subItem->getName() ] = $data[ $subItem->getName() ];
                } else if ($subItem->getName() === 'computed') {
                    $result[ $subItem->getName() ] = $this->prepareComputed($data, $data);
                } else if ($subItem->getName() === 'value') {
                    $result[ $subItem->getName() ] = $this->prepareValue($data);
                } else {
                    $result[ $subItem->getName() ] = $data;
                }
            }

            return $result;
        }

        if (is_array($data) && empty($data['title'])) {
            $data = array_values(array_map(function ($item) {
                if (is_array($item) && isset($item['title'])) {
                    return $item['title'];
                }

                return $item;
            }, $data));

            return $data;
        }

        if (!empty($data['title'])) {
            return $data['title'];
        }

        return $data;
    }

    /**
     * Транслит значение для value
     * @param string $str
     * @return string
     */
    private function translit(string $str): string
    {
        return \CUtil::translit($str, 'ru', self::TR_PARAMS);
    }

    /**
     * Получаем список слуга секций для товара
     *
     * @param int $iBlockId
     * @param int $elementId
     *
     */
    private function getSections(int $iBlockId, int $elementId)
    {
        $elSectionsRes = \CIBlockElement::GetElementGroups($elementId);

        $sections = [];

        $sectionParents = $this->getParentsSections($iBlockId);

        while ($section = $elSectionsRes->Fetch()) {
            if (!empty($sectionParents[ $section["ID"] ])) {
                foreach ($sectionParents[ $section["ID"] ] as $code) {
                    if (!in_array($code, $sections, true)) {
                        $sections[] = $code;
                    }
                }
            }
        }

        return $sections;
    }

    /**
     * Получаем связку вложенности секций
     *
     * @param int $iBlockId
     * @return array
     */
    protected function getParentsSections(int $iBlockId): array
    {
        if (!is_null($this->parentsSectionById[ $iBlockId ])) {
            return $this->parentsSectionById[ $iBlockId ];
        }

        $this->fillSections($iBlockId);

        return $this->parentsSectionById[ $iBlockId ];
    }

    /**
     * Получаем
     * @param int $iBlockId
     * @return array
     */
    protected function getSectionsCode(int $iBlockId): array
    {
        if (!is_null($this->sectionCodeById[ $iBlockId ])) {
            return $this->sectionCodeById[ $iBlockId ];
        }
        $this->fillSections($iBlockId);
        $this->sectionCodeById[ $iBlockId ];
    }


    /**
     * Заполняем все по секциям
     * @param int $iBlockId
     * @return void
     */
    protected function fillSections(int $iBlockId): void
    {
        $res = \CIBlockSection::GetList(array("left_margin" => "asc"), ['IBLOCK_ID' => $iBlockId], false, ['ID', 'DEPTH_LEVEL', 'NAME', 'CODE', 'IBLOCK_SECTION_ID']);

        $result = [];
        $resultSectionCode = [];
        $sectionsChain = [];

        while ($row = $res->Fetch()) {
            if (empty($row["IBLOCK_SECTION_ID"])) {
                $result[ $row["ID"] ][] = $row["CODE"];
            } else {
                $result[ $row["ID"] ] = array_merge($result[ $row["IBLOCK_SECTION_ID"] ], [$row["CODE"]]);
            }

            $resultSectionCode[ $row["ID"] ] = [
                'title'    => $row["NAME"],
                'value'    => $row["CODE"],
                'keyId'    => strval($row['ID']),
                'computed' => $this->prepareComputed($row["CODE"], $row["NAME"])
            ];

            if (empty($sectionsChain[ $row['IBLOCK_SECTION_ID'] ])) {
                $sectionsChain[ $row['ID'] ] = $row['NAME'];
            } else {
                $sectionsChain[ $row['ID'] ] = $sectionsChain[ $row['IBLOCK_SECTION_ID'] ] . '/' . $row['NAME'];
            }
        }

        $this->sectionsChain = $sectionsChain;
        $this->parentsSectionById[ $iBlockId ] = $result;
        $this->sectionCodeById[ $iBlockId ] = $resultSectionCode;
    }

    /**
     * @param int $iblockId
     * @param array $ids = []
     * @return array
     */
    protected function getStoreAmountByProductId(int $iblockId, array $ids = []): array
    {
        if (!empty($ids)) {
            $str = implode(',', $ids);
            $sql = "SELECT SUM(AMOUNT) as amount, product_id 
FROM b_catalog_store_product c
WHERE product_id in ($str)
GROUP BY product_id;";
        } else {
            $sql = "SELECT SUM(AMOUNT) as amount, product_id 
FROM b_catalog_store_product c
JOIN b_iblock_element as ie on ie.ID = c.PRODUCT_ID and ie.ACTIVE=\"Y\" and ie.IBLOCK_ID = $iblockId 
GROUP BY PRODUCT_ID;";
        }
        $result = [];
        $connection = \Bitrix\Main\Application::getConnection();

        $res = $connection->query($sql);
        while ($row = $res->Fetch()) {
            $result[ $row['product_id'] ] = (int)$row['amount'];
        }

        return $result;
    }

    /**
     * @param $value
     */
    protected function prepareValue($value)
    {
        return preg_replace('/\//', '_', $value);
    }

    /**
     * @param $value
     * @param $title
     * @return string
     */
    protected function prepareComputed($value, $title): string
    {
        return preg_replace('/\//', '_', $value) . "||" . $title;
    }
}
