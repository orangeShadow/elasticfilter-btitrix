<?php
declare(strict_types=1);


namespace OrangeShadow\ElasticSearch;

use OrangeShadow\ElasticSearch\Config\IndexConfig;
use OrangeShadow\ElasticSearch\Config\IndexMappingElement;
use OrangeShadow\ElasticSearch\Config\IndexMappingProperty;
use Tightenco\Collect\Support\Collection;
use Opis\Closure\SerializableClosure;

/**
 * Берем основные данные данные из продукта и офера,
 * но заполняем из последнего и только в случае отсутствия из продукта.
 * Так же создается и маппинг он складирует общие поля в Коллекции IndexMapping, но передает последние пройденные
 * В Данном случае оффер всегда последний, возможно это нужно будет менять для других случаев
 *
 * Class BitrixConfigGenerator
 * @package OrangeShadow\ElasticSearch
 */
class BitrixConfigGenerator
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var int
     */
    protected $iBlockId;

    /**
     * @var int
     */
    protected $iBlockOfferId;

    /**
     * @var array
     */
    protected $defaultFields;


    /**
     * @var array[]
     */
    protected function defaultFields()
    {
        if (!empty($this->defaultFields)) {
            return $this->defaultFields;
        }

        return [
            [
                'name'                  => 'bitrixId',
                'title'                 => 'id',
                'type'                  => TypeEnum::INT,
                'bitrixIblockField'     => 'ID',
                'bitrixIblockFieldType' => BitrixFieldTypeEnum::ELEMENT_FIELD
            ],
            [
                'name'                  => 'active',
                'title'                 => 'активность',
                'type'                  => TypeEnum::BOOL,
                'bitrixIblockField'     => 'ACTIVE',
                'bitrixIblockFieldType' => BitrixFieldTypeEnum::ELEMENT_FIELD
            ],
            [
                'name'                  => 'name',
                'title'                 => 'название',
                'type'                  => TypeEnum::TEXT,
                'bitrixIblockField'     => 'NAME',
                'analyzer'              => 'all_text',
                'bitrixIblockFieldType' => BitrixFieldTypeEnum::ELEMENT_FIELD
            ],
            [
                'name'                  => 'preview_text',
                'title'                 => 'короткий текст',
                'type'                  => TypeEnum::TEXT,
                'bitrixIblockField'     => 'PREVIEW_TEXT',
                'analyzer'              => 'all_text',
                'bitrixIblockFieldType' => BitrixFieldTypeEnum::ELEMENT_FIELD
            ],
            [
                'name'                  => 'detail_text',
                'title'                 => 'детальный текст',
                'type'                  => TypeEnum::TEXT,
                'bitrixIblockField'     => 'DETAIL_TEXT',
                'analyzer'              => 'all_text',
                'bitrixIblockFieldType' => BitrixFieldTypeEnum::ELEMENT_FIELD
            ],
            [
                'name'                  => 'preview_picture',
                'title'                 => 'детальная картина',
                'type'                  => TypeEnum::KEYWORD,
                'enabled'               => false,
                'bitrixIblockField'     => 'PREVIEW_PICTURE',
                'targetClass'           => 'CFile',
                'targetMethod'          => 'GetPath',
                'bitrixIblockFieldType' => BitrixFieldTypeEnum::FILE_FIELD
            ],
            [
                'name'                  => 'detail_picture',
                'title'                 => 'детальная картина',
                'type'                  => TypeEnum::KEYWORD,
                'enabled'               => false,
                'bitrixIblockField'     => 'DETAIL_PICTURE',
                'targetClass'           => 'CFile',
                'targetMethod'          => 'GetPath',
                'bitrixIblockFieldType' => BitrixFieldTypeEnum::FILE_FIELD
            ],
            [
                'name'                  => 'code',
                'title'                 => 'slug',
                'type'                  => TypeEnum::KEYWORD,
                'normalizer'            => 'lowercase',
                'bitrixIblockField'     => 'CODE',
                'bitrixIblockFieldType' => BitrixFieldTypeEnum::ELEMENT_FIELD
            ],
            [
                'name'                  => 'xml_id',
                'title'                 => 'XML_ID',
                'type'                  => TypeEnum::KEYWORD,
                'normalizer'            => 'lowercase',
                'bitrixIblockField'     => 'XML_ID',
                'bitrixIblockFieldType' => BitrixFieldTypeEnum::ELEMENT_FIELD
            ],
            [
                'name'                  => 'sort',
                'title'                 => 'SORT',
                'type'                  => TypeEnum::INT,
                'bitrixIblockField'     => 'SORT',
                'bitrixIblockFieldType' => BitrixFieldTypeEnum::ELEMENT_FIELD
            ],
            [
                'name'                  => 'section',
                'title'                 => 'SECTION',
                'type'                  => TypeEnum::KEYWORD,
                'normalizer'            => 'lowercase',
                'bitrixIblockField'     => 'SECTION_ID',
                'bitrixIblockFieldType' => BitrixFieldTypeEnum::SECTION_FIELD
            ],
            [
                'name'                  => 'show_counter',
                'title'                 => 'show_counter',
                'type'                  => TypeEnum::INT,
                'bitrixIblockFieldType' => BitrixFieldTypeEnum::ELEMENT_FIELD,
                'bitrixIblockField'     => 'SHOW_COUNTER',
            ],
            /*
            [
                'name'                  => 'SOME_FIELD',
                'title'                 => 'SOME_FIELD',
                'type'                  => TypeEnum::OBJECT,
                'enabled'               => false,
                'bitrixIblockFieldType' => BitrixFieldTypeEnum::ANONYMOUS_FUNCTION,
                'function'              => new SerializableClosure(function ($id) {
                    //TODO::какой то метод для значения
                }),
            ],
            */
        ];

    }

    /**
     * Дефолтный настройки для индексации
     */
    protected $defaultSettings = [
        'analysis' => [
            'normalizer' => [
                'lowercase' => [
                    'type'   => 'custom',
                    'filter' => [
                        "lowercase"
                    ]
                ]
            ],
            'filter'     => [
                'russian_stop'     => [
                    'type'      => 'stop',
                    'stopwords' => '_russian_'
                ],
                'russian_keywords' => [
                    'type'     => 'keyword_marker',
                    'keywords' => []
                ],
                'russian_stemmer'  => [
                    'type'     => 'stemmer',
                    'language' => 'russian'
                ],
                'english_stemmer'  => [
                    'type'     => 'stemmer',
                    'language' => 'english'
                ],
                'english_stop'     => [
                    'type'      => 'stop',
                    'stopwords' => '_english_'
                ],
            ],
            'analyzer'   => [
                'russian_text' => [
                    'type'      => 'custom',
                    'tokenizer' => 'standard',
                    'filter'    => [
                        'lowercase',
                        'russian_stop',
                        'russian_stemmer'
                    ]
                ],
                'english_text' => [
                    'type'      => 'custom',
                    'tokenizer' => 'standard',
                    'filter'    => [
                        'lowercase',
                        'english_stop',
                        'english_stemmer'
                    ]
                ],
                'all_text'     => [
                    'type'      => 'custom',
                    'tokenizer' => 'standard',
                    'filter'    => [
                        'lowercase',
                        'english_stop',
                        'russian_stop',
                        'english_stemmer',
                        'russian_stemmer'
                    ]
                ]
            ]
        ]
    ];

    /**
     * Наличие свойства в умном фильтре
     * Свойство для ленивой загрузки
     * @var array
     */
    protected $smartFilterSettings = [];

    /**
     * BitrixConfigGenerator constructor.
     * @param string $name
     * @param int $iBlockId
     * @param int|null $iBlockOfferId
     */
    public function __construct(string $name, int $iBlockId, ?int $iBlockOfferId = null, array $defaultFields = [])
    {
        $this->name = $name;
        $this->iBlockId = $iBlockId;
        $this->iBlockOfferId = $iBlockOfferId;
        $this->defaultFields = $defaultFields;
    }

    /**
     * Создание конфига для Битрикса
     */
    public function generateConfig(): IndexConfig
    {
        $collection = new Collection();

        $this->getIblockProperties($this->iBlockId, $collection);

        if ($this->hasOffers()) {
            $this->getIblockProperties($this->iBlockOfferId, $collection, true);
        }

        $indexConfig = new IndexConfig();
        $indexConfig->setIBlockId($this->iBlockId);
        $indexConfig->setOfferIBlockId($this->iBlockOfferId);
        $indexConfig->setName($this->name);
        $indexConfig->setMapping($collection);
        $indexConfig->setSettings($this->defaultSettings);

        return $indexConfig;
    }

    /**
     * @return bool
     */
    protected function hasOffers(): bool
    {
        return !empty($this->iBlockOfferId);
    }

    /**
     * Заполнение стандартных
     * @param Collection $collection
     */
    protected function getDefaultParams(Collection $collection, int $iBlockId)
    {

        foreach ($this->defaultFields() as $item) {
            $indexMappingElement = new IndexMappingElement();
            $indexMappingElement->setName($item['name']);
            $indexMappingElement->setType($item['type']);
            $indexMappingElement->setTitle($item['title']);
            $indexMappingElement->setBitrixIblockId($iBlockId);

            if (isset($item['enabled']) && $item['enabled'] === false) {
                $indexMappingElement->setEnabled(false);
            }

            if (isset($item['bitrixIblockField'])) {
                $indexMappingElement->setBitrixIblockField($item['bitrixIblockField']);
            }

            if (isset($item['bitrixIblockFieldType'])) {
                $indexMappingElement->setBitrixIblockFieldType($item['bitrixIblockFieldType']);
            }

            if (isset($item['normalizer'])) {
                $indexMappingElement->setNormalizer($item['normalizer']);
            }

            if (isset($item['analyzer'])) {
                $indexMappingElement->setAnalyzer($item['analyzer']);
            }

            if (isset($item['targetClass'])) {
                $indexMappingElement->setTargetClass($item['targetClass']);
            }

            if (isset($item['targetMethod'])) {
                $indexMappingElement->setTargetMethod($item['targetMethod']);
            }

            if (isset($item['function'])) {
                $indexMappingElement->setFunction($item['function']);
            }

            $collection->add($indexMappingElement);
        }
    }


    /**
     * Заполняем свойства инфоблока
     *
     * @param int $iBlockId
     * @param Collection $collection
     * @param bool $isOffer = false
     */
    protected function getIblockProperties(int $iBlockId, Collection $collection, bool $isOffer = false): void
    {
        \CModule::IncludeModule('iblock');

        if ($isOffer) {
            //Офер имеет свою подструктуру элементов
            $offerMappingElement = new IndexMappingElement();
            $offerMappingElement->setTitle('Торговое предложение');
            $offerMappingElement->setType(TypeEnum::NESTED);
            $offerMappingElement->setName('offers');
            $offerMappingElement->setBitrixIblockId($iBlockId);
            $offerMappingElement->setBitrixIblockFieldType(BitrixFieldTypeEnum::OFFER);
            $propertiesCollection = new Collection();

            $this->getDefaultParams($propertiesCollection, $iBlockId);

            $indexMappingElement = new IndexMappingElement();
            $indexMappingElement->setName('catalog_available');
            $indexMappingElement->setType(TypeEnum::BOOL);
            $indexMappingElement->setTitle('Доступность');
            $indexMappingElement->setBitrixIblockId($iBlockId);
            $indexMappingElement->setBitrixIblockField('STORE_QUANTITY');
            $indexMappingElement->setBitrixIblockFieldType(BitrixFieldTypeEnum::ELEMENT_FIELD);

            $propertiesCollection->add($indexMappingElement);

        } else {
            $this->getDefaultParams($collection, $iBlockId);
        }

        $iSectionProperties = $this->getSmartFilterSettings($iBlockId);

        $res = \CIBlockProperty::GetList([], ["IBLOCK_ID" => $iBlockId]);
        while ($row = $res->Fetch()) {
            $indexMappingElement = new IndexMappingElement();
            $smartFilter = false;
            $indexMappingElement->setTitle($row["NAME"]);
            $indexMappingElement->setName(strtolower($row['CODE']));
            $indexMappingElement->setBitrixIblockId($iBlockId);
            $indexMappingElement->setBitrixIblockField($row["CODE"]);

            if ($this->hasInSmartFilter((int)$row["ID"], $iBlockId)) {
                $iSecProp = $iSectionProperties[ $row['ID'] ];
                $smartFilter = $iSecProp["SMART_FILTER"] === "Y";
                $indexMappingElement->setPropertyId($row["ID"]);
                $indexMappingElement->setSort((int)$iSecProp['SORT']);
                $indexMappingElement->setPropertyType($iSecProp['PROPERTY_TYPE']);
                $indexMappingElement->setShowingType($iSecProp['DISPLAY_TYPE']);
                $indexMappingElement->setHint($iSecProp['FILTER_HINT']);
                $indexMappingElement->setShowInSection($iSecProp["SECTIONS"]);
                $indexMappingElement->setHasInFilter($smartFilter);
            }

            //Устанавливаем тип и доп настройки к свойству
            $this->setBitrixIblockFieldType($indexMappingElement, $row);

            $type = $this->getTypeForElastic($row['PROPERTY_TYPE'], $smartFilter);

            $indexMappingElement->setType($type);

            //Все вложенные на этом этапе являются элементами для фильтра и имеют структуру {title:"",value:""}
            if ($type === TypeEnum::NESTED) {
                $properties = [];
                $properties[] = (new IndexMappingProperty())
                    ->setType(TypeEnum::KEYWORD)
                    ->setName('title');
                $properties[] = (new IndexMappingProperty())
                    ->setType(TypeEnum::KEYWORD)
                    ->setNormalizer('lowercase')
                    ->setName('value');
                $properties[] = (new IndexMappingProperty())
                    ->setType(TypeEnum::KEYWORD)
                    ->setNormalizer('lowercase')
                    ->setName('computed');
                $properties[] = (new IndexMappingProperty())
                    ->setType(TypeEnum::KEYWORD)
                    ->setNormalizer('lowercase')
                    ->setName('keyId');
                $indexMappingElement->setProperties($properties);
            }

            if ($isOffer) {
                $propertiesCollection->add($indexMappingElement);
            } else {
                $collection->add($indexMappingElement);
            }
        }

        if ($isOffer) {
            $offerMappingElement->setProperties($propertiesCollection->toArray());
            $collection->add($offerMappingElement);
        }
    }

    /**
     * Есть в настройках умного фильтра
     * @param int $id
     * @param int $iBlockId
     * @return bool
     */
    protected function hasInSmartFilter(int $id, int $iBlockId)
    {
        return isset($this->getSmartFilterSettings($iBlockId)[ $id ]);
    }

    /**
     * @param IndexMappingElement $indexMappingElement
     * @param array $row
     */
    protected function setBitrixIblockFieldType(IndexMappingElement $indexMappingElement, array $row)
    {
        if ($row["PROPERTY_TYPE"] === "S" && $row["USER_TYPE"] === 'directory') {
            $indexMappingElement->setBitrixIblockFieldType(BitrixFieldTypeEnum::HL_PROPERTY);
            $indexMappingElement->setBitrixHL($row["USER_TYPE_SETTINGS"]["TABLE_NAME"]);
        } else if ($row["PROPERTY_TYPE"] === "L") {
            $indexMappingElement->setBitrixIblockFieldType(BitrixFieldTypeEnum::ENUM_PROPERTY);
        } else {
            $indexMappingElement->setBitrixIblockFieldType(BitrixFieldTypeEnum::SIMPLE_PROPERTY);
        }


        if (!empty($row['LINK_IBLOCK_ID'])) {
            $indexMappingElement->setBitrixIblockPropertyLink((int)$row['LINK_IBLOCK_ID']);
            $indexMappingElement->setBitrixIblockFieldType(BitrixFieldTypeEnum::LINKED_PROPERTY);
        }

        if ($row["PROPERTY_TYPE"] === "G") {
            $indexMappingElement->setBitrixIblockFieldType(BitrixFieldTypeEnum::CATEGORY_PROPERTY);
        }
    }

    /**
     * Настройки умного фильтра по секциям
     * @param int $iBlockId
     * @return array|null
     */
    protected function getSmartFilterSettings(int $iBlockId): ?array
    {
        if (!empty($this->smartFilterSettings[ $iBlockId ])) {
            return $this->smartFilterSettings[ $iBlockId ];
        }

        $sections = $this->getIblockSections($iBlockId);

        //Учитываем что свойство может быть настроено для разных категорий
        foreach (\CIBlockSectionPropertyLink::GetArray($iBlockId) as $row) {
            if (!isset($iblockSectionProperty[ $row['PROPERTY_ID'] ])) {
                $iSectionProperty[ $row["PROPERTY_ID"] ] = $row;
                $iSectionProperty[ $row["PROPERTY_ID"] ]['SECTIONS'] = [];
            }
            if (!empty($row['SECTION_ID'])) {
                $iSectionProperty[ $row["PROPERTY_ID"] ]['SECTIONS'][] = $sections[ $row['SECTION_ID'] ];
            }
        }

        $this->smartFilterSettings[ $iBlockId ] = $iSectionProperty;

        return $this->smartFilterSettings[ $iBlockId ];
    }

    /**
     * @param int $iBlockId
     * @return array
     */
    protected function getIblockSections(int $iBlockId): array
    {
        $sections = [];
        $res = \CIBlockSection::GetList([], ['IBLOCK_ID' => $iBlockId]);
        while ($row = $res->Fetch()) {
            $sections[ $row["ID"] ] = $row["CODE"];
        }

        return $sections;
    }

    /**
     * Получаем тип в эластике
     * @param string $bitrixType
     * @param bool $hasInSmartFilter
     * @return string
     */
    protected function getTypeForElastic(string $bitrixType, bool $hasInSmartFilter = false): string
    {
        if ($bitrixType === "N") {
            return TypeEnum::FLOAT;
        }

        if ($hasInSmartFilter) {
            return TypeEnum::NESTED;
        }

        return TypeEnum::KEYWORD;

    }
}
