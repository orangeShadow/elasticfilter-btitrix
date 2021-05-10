<?php
declare(strict_types=1);


namespace OrangeShadow\ElasticSearch\Builders;

use OrangeShadow\ElasticSearch\Config\IndexConfig;
use OrangeShadow\ElasticSearch\Contracts\Mapable;
use OrangeShadow\ElasticSearch\TypeEnum;
use ONGR\ElasticsearchDSL\BuilderInterface;
use ONGR\ElasticsearchDSL\Query as Query;
use ONGR\ElasticsearchDSL\Search;
use OrangeShadow\ElasticSearch\Config\IndexMappingElement;


class SearchBuilder
{
    /**
     * @var IndexConfig
     */
    protected $config;

    /**
     * Список доступных полей по имени
     * @var IndexMappingElement[]
     */
    protected $fieldsArray = [];

    /**
     * @var array
     */
    protected $offersFieldsArray = [];

    /**
     * SearchBuilder constructor.
     * @param IndexConfig $config
     */
    public function __construct(IndexConfig $config)
    {
        $this->config = $config;

        foreach ($this->config->getMapping() as $item) {
            $this->fieldsArray[ $item->getName() ] = $item;

            if ($item->getName() === 'offers') {
                foreach ($item->getProperties() as $subItem) {
                    $this->offersFieldsArray[ $subItem->getName() ] = $subItem;
                }
            }
        }
    }

    /**
     * @param array $queryParams
     *
     * @return array
     */
    public function build(array $queryParams): array
    {
        $search = new Search();
        $search->addQuery($this->getBoolQuery($queryParams));

        return $search->toArray();
    }

    public function getBoolQuery(array $queryParams): BuilderInterface
    {
        $bool = new Query\Compound\BoolQuery();

        foreach ($queryParams as $key => $value) {

            $range = null;

            if ($this->isRange($key)) {
                $range = $this->getBottom($key);
            }

            if (strpos($key, 'offers_') !== false) {
                $bool->add(
                    $this->addQueryOfferByType($this->offersFieldsArray[ str_replace('offers_', '', $key) ], $key, $value),
                    Query\Compound\BoolQuery::MUST
                );

            }


            $key = $this->cleanKey($key);

            if(!empty($_REQUEST["sort"]) && $key == "sort") continue;

            if (!isset($this->fieldsArray[ $key ])) {
                continue;
            }

            $item = $this->fieldsArray[ $key ];

            $bool->add(
                $this->addQueryByType($item, $key, $value,$range),
                Query\Compound\BoolQuery::MUST);
        }

        //TODO::create negative query
        //$bool->add(new Query\TermLevel\TermQuery('',1),Query\Compound\BoolQuery::MUST_NOT);

        return $bool;
    }

    /**
     * @param BuilderInterface $bool
     * @param Mapable $item
     * @param string $key
     * @param $value
     */
    public function addQueryOfferByType(Mapable $item, string $key, $value)
    {
        return new Query\Joining\NestedQuery('offers',
            $this->addQueryByType($item, str_replace('offers_','offers.',$key), $value)
        );
    }

    /**
     * @param BuilderInterface $bool
     * @param Mapable $item
     * @param string $key
     * @param $value
     */
    public function addQueryByType(Mapable $item, string $key, $value, $range = null)
    {
        switch ($item->getType()) {
            case TypeEnum::BOOL:
                return new Query\TermLevel\TermQuery($key, !empty($value));
            case TypeEnum::INT:
                if (!empty($range)) {
                    return new Query\TermLevel\RangeQuery($key, [$range => (int)$value]);
                }

                return new Query\TermLevel\TermQuery($key, (int)$value);
            case TypeEnum::FLOAT:
                if (!empty($range)) {
                    return new Query\TermLevel\RangeQuery($key, [$range => (float)$value]);
                }

                return new Query\TermLevel\TermQuery($key, (float)$value);
            case TypeEnum::KEYWORD:
                if (is_array($value)) {
                    return new Query\TermLevel\TermsQuery($key, $value);
                }

                return new Query\TermLevel\TermQuery($key, $value);
            case TypeEnum::NESTED:
                if (is_array($value)) {
                    return new Query\Joining\NestedQuery($key, new Query\TermLevel\TermsQuery($key . '.value', $value));
                }

                return new Query\Joining\NestedQuery($key, new Query\TermLevel\TermQuery($key . '.value', $value));
        }

        return null;
    }

    /**
     *
     */
    protected function isRange(string $key): bool
    {
        return (bool)preg_match('/_from$|_to$/', $key);
    }

    /**
     * Получаем границы для range
     */
    protected function getBottom(string $key): string
    {
        if (preg_match('/_from$/', $key)) {
            return 'gte';
        }

        return 'lte';
    }

    /**
     * Убираем лишнее из ключа
     */
    protected function cleanKey(string $key): string
    {
        return preg_replace('/_from$|_to$/', '', $key);
    }
}
