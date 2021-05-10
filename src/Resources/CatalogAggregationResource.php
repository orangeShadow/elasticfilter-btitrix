<?php
declare(strict_types=1);

namespace OrangeShadow\ElasticSearch\Resources;

use OrangeShadow\ElasticSearch\Config\IndexConfig;
use OrangeShadow\ElasticSearch\Contracts\Resourceable;

/**
 * Class CatalogAggregationResource
 * @package OrangeShadow\ElasticSearch\Resources
 *
 */
class CatalogAggregationResource implements Resourceable
{
    /**
     * @var array $properties ;
     */
    private $properties;

    /**
     * @var IndexConfig
     */
    private $config;

    /**
     * CatalogAggregationResource constructor.
     * @param array $properties
     * @param IndexConfig $config
     */
    public function __construct(array $properties, IndexConfig $config)
    {
        $this->properties = $properties;
        $this->config = $config;
    }

    /**
     * @param array $sources
     * @return array
     */
    public function toArray(array $sources): array
    {
        if (empty($sources['aggregations']['all_products'])) {
            return [];
        }

        $aggregatedData = $sources['aggregations']['all_products'];

        $result = array_map(function ($property) use ($aggregatedData) {
            $propertyCode = strtolower($property["CODE"]);

            //Range и не является свойством офера
            if ($property["PROPERTY_TYPE"] === "N" && !$this->isOfferProperty($property)) {
                return $this->parseRangeResult($property, $propertyCode, $aggregatedData);
            }

            //Свойства нет вообще и оно не оффер
            if (empty($aggregatedData[ $propertyCode ]) && !$this->isOfferProperty($property)) {
                return $property;
            }

            //Свойства является свойством офера
            if ($this->isOfferProperty($property)) {
                $propertyCode = 'offers.' . $propertyCode;
                $aggregatedValues = $aggregatedData[ $propertyCode ][ $propertyCode ][ $propertyCode ];
                return  $this->parseNestedResult($property, $propertyCode, $aggregatedValues);
            }

            $aggregatedValues = $aggregatedData[ $propertyCode ][ $propertyCode ];

            return $this->parseNestedResult($property, $propertyCode, $aggregatedValues);

        }, $this->properties);


        return ['count' => $sources['hits']['total']['value'] ?? 0, 'items' => $result];
    }

    /**
     * Свойство офера
     * @param array $property
     * @return bool
     */
    protected function isOfferProperty($property): bool
    {
        return (int)$property["IBLOCK_ID"] === (int)$this->config->getOfferIBlockId();
    }

    /**
     * Обрабатываем офер
     * @param $property
     * @param $propertyCode
     * @param $aggregatedValues
     * @return array
     */
    protected function parseNestedResult(array $property, string $propertyCode, array $aggregatedValues): array
    {

        $valueBuckets = [];
        $titleBuckets = [];
        $countArray = [];
        foreach ($aggregatedValues[$propertyCode.'.computed']['buckets'] as $key=>$item) {
            $keyStr = explode('||',$item['key']);
            $countArray[$key] = $item['doc_count'];
            $valueBuckets[$key] = $keyStr[0];
            $titleBuckets[$key] = $keyStr[1];
        }

        $property = $this->getDataByBuckets($property, $propertyCode,$countArray, $valueBuckets, $titleBuckets);
        uasort($property["VALUES"], function ($a, $b){
            if($a['VALUE'] === $b['VALUE']) {
                return 0;
            }

            return strnatcasecmp(strtolower($a['VALUE']), strtolower($b['VALUE']));
        });
        return $property;
    }

    /**
     * @param array $property
     * @param string $propertyCode
     * @param array $countArray
     * @param array $valueBuckets
     * @param array|null $titleBuckets
     * @return array
     */
    protected function getDataByBuckets(array $property, string $propertyCode, array $countArray, array $valueBuckets, ?array $titleBuckets = null): array
    {
        $sort = 2;
        foreach ($valueBuckets as $key => $value) {

            if (isset($titleBuckets[$key])) {
                $title = $titleBuckets[ $key ];
            } else {
                $title = $value;
            }

            if (isset($countArray[$key])) {
                $docCount = $countArray[$key];
            }

            $controlName = 'catalogFilter_' . $property['ID'];

            $item = [
                'CONTROL_ID'       => $controlName . '_' . $value,
                'CONTROL_NAME'     => $propertyCode . '[]',
                'CONTROL_NAME_ALT' => $propertyCode,
                'HTML_VALUE_ALT'   => '',
                'HTML_VALUE'       => $value,
                'VALUE'            => $title ? ucfirst($title):null,
                'SORT'             => $sort += 2,
                'UPPER'            => $title ? strtoupper($title):null,
                'FLAG'             => 1,
                'URL_ID'           => $value,
                'ELEMENT_COUNT'    => $docCount
            ];
            $property["VALUES"][ $value ] = $item;
        }

        return $property;
    }

    /**
     * Парсим результат агрегации по интервалу
     *
     * @param $property
     * @param $propertyCode
     * @param $aggregatedData
     * @return array
     */
    protected function parseRangeResult($property, $propertyCode, $aggregatedData): array
    {
        $controlName = 'catalogFilter_' . $property['ID'];

        if (isset($aggregatedData[ $propertyCode . "_to" ])) {
            $value = array_pop($aggregatedData[ $propertyCode . "_to" ])['value'];

            $item = [
                'CONTROL_ID'     => $controlName . '_' . $value,
                'CONTROL_NAME'   => $propertyCode . "_to",
                'HTML_VALUE'     => $value,
                'HTML_VALUE_ALT' => $propertyCode . "_to",
                'VALUE'          => $value
            ];
            $property['VALUES']["MAX"] = $item;
        }

        if (isset($aggregatedData[ $propertyCode . "_from" ])) {
            $value = array_pop($aggregatedData[ $propertyCode . "_from" ])['value'];
            $item = [
                'CONTROL_ID'     => $controlName . '_' . $value,
                'CONTROL_NAME'   => $propertyCode . "_from",
                'HTML_VALUE'     => $value,
                'HTML_VALUE_ALT' => $propertyCode . "_from",
                'VALUE'          => $value
            ];
            $property['VALUES']["MIN"] = $item;
        }

        return $property;
    }
}
