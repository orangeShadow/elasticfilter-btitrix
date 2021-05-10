<?php
declare(strict_types=1);

namespace OrangeShadow\ElasticSearch;

use OrangeShadow\ElasticSearch\Builders\AggregationBuilder;
use OrangeShadow\ElasticSearch\Builders\SearchBuilder;
use OrangeShadow\ElasticSearch\Contracts\Resourceable;
use OrangeShadow\ElasticSearch\Properties\SearchProperty;
use Elasticsearch\ClientBuilder;
use Elasticsearch\Client;
use OrangeShadow\ElasticSearch\Config\IndexConfig;

class ElasticManager
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var IndexConfig $config
     */
    protected $config;

    /**
     * @var SearchBuilder
     */
    protected $searchBuilder;


    /**
     * AbstractElasticManager constructor.
     * @param IndexConfig $config
     * @param Client|null $client
     */
    public function __construct(IndexConfig $config, Client $client = null)
    {
        $this->client = $client;

        if (is_null($client)) {
            $elasticIp = getenv('ELASTIC_IP') ? getenv('ELASTIC_IP'):'';
            $elasticUser = getenv('ELASTIC_NAME') ? getenv('ELASTIC_NAME') : '';
            $elasticPass = getenv('ELASTIC_PASSWORD') ? getenv('ELASTIC_PASSWORD') : '';
            $this->client = ClientBuilder::create()
                ->setHosts([$elasticIp])
                ->setBasicAuthentication($elasticUser, $elasticPass)
                ->build();
        }

        $this->config = $config;

        $this->searchBuilder = new SearchBuilder($config);
        $this->aggregationBuilder = new AggregationBuilder($config);
    }

    /**
     * @return IndexConfig
     */
    public function getConfig(): IndexConfig
    {
        return $this->config;
    }

    /**
     * @param IndexConfig $config
     */
    public function setConfig(IndexConfig $config)
    {
        $this->config = $config;
    }


    /**
     * Создает индекс (удаляет старый если был)
     * @param string|null $indexName
     * @return string|null IndexName
     */
    public function createIndex(?string $indexName = null): ?string
    {
        if ($indexName === null) {
            $indexName = $this->getConfig()->getName();
        }

        $tempIndexName = $indexName . '_' . date('Y_m_d_H_i_s');

        $this->config->setName($tempIndexName);

        try {
            $params = [
                'index' => $tempIndexName,
                'body'  => [
                    'settings' => $this->getConfig()->getSettings(),
                    'mappings' => [
                        'properties' => $this->getConfig()->getMappingForIndex()
                    ]
                ]
            ];

            $this->client->indices()->create($params);
        } catch (\Throwable $e) {
            var_dump($e->getMessage(), $e->getFile(), $e->getLine());
            $this->deleteIndexByName($tempIndexName);
            return null;
        }

        return $indexName;
    }

    public function setNewIndexAlias(string $indexName)
    {
        $this->deleteIndexByName($indexName);
        $this->addAliasToIndex($this->config->getName(), $indexName);
        $this->config->setName($indexName);
    }

    /**
     * @param string $indexName
     * @param string $alias
     */
    public function addAliasToIndex(string $indexName, string $alias)
    {
        $params = [];
        $params['body'] = [
            'actions' => [
                [
                    'add' => [
                        'index' => $indexName,
                        'alias' => $alias
                    ]
                ]
            ]
        ];

        $this->client->indices()->updateAliases($params);

    }

    /**
     * @param string $indexName
     * @return bool
     */
    public function deleteIndexByName(string $indexName): bool
    {
        try {
            $indexes = $this->client->indices()->getAlias(['index' => $indexName]);

            if (empty($indexes)) {
                $this->client->indices()->delete(['index' => $indexName]);
            }

            foreach ($indexes as $trueIndexName => $aliases) {
                $this->client->indices()->delete(['index' => $trueIndexName]);
            }

            return true;
        } catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * Проверка наличия индекса
     * @param string $indexName
     * @return bool
     */
    public function checkIndexExist(string $indexName): bool
    {
        return $this->client->indices()->exists(['index' => $indexName]);
    }

    /**
     * @param string $id
     * @param array $source
     */
    public function addElement(string $id, array $source): void
    {
        $this->client->create([
            'index' => $this->config->getName(),
            'id'    => $id,
            'body'  => $source
        ]);
    }

    /**
     * @param string $id
     * @param array $source
     */
    public function updateElement(string $id, array $source): void
    {
        try {
            $this->deleteElement($id);
        } catch (\Exception $e) {
            //Ничего не делаем, может падать ошибка если нет в индексе
        } finally {
            $this->addElement($id, $source);
        }
    }

    /**
     * Заменяет разницу между тем что в базе и тем, что пришло
     * @param string $id
     * @param array $diff
     */
    public function updateDiffElement(string $id, array $diff): void
    {
        $element = $this->getElement($id);

        foreach ($diff as $key => $item) {
            $element[ $key ] = $item;
        }
        $this->deleteElement($id);

        $this->addElement($id, $element);
    }

    /**
     * @param string $id
     */
    public function deleteElement(string $id): void
    {
        $this->client->delete([
            'index' => $this->config->getName(),
            'id'    => $id
        ]);
    }

    /**
     * @param string $id
     * @return array| null
     */
    public function getElement(string $id): ?array
    {
        $res = $this->client->get([
            'index' => $this->config->getName(),
            'id'    => $id
        ]);

        return $res["_source"] ?? null;
    }

    /**
     * @param SearchProperty $searchProperty
     * @return mixed|null
     */
    public function search(SearchProperty $searchProperty)
    {
        $body = [];

        $body['from'] = $searchProperty->getFrom();
        $body['size'] = $searchProperty->getSize();

        if (!empty($searchProperty->getSortArray())) {
            $body['sort'] = $searchProperty->getSortArray();
        }

        $body['_source'] = $searchProperty->getSelect();
        $body = array_merge($body, $this->searchBuilder->build($searchProperty->getQueryParams()));

        $result = $this->client->search([
            'index' => $this->config->getName(),
            'body'  => $body
        ]);

        if (empty($result['hits']['hits'])) {
            return null;
        }

        return $result['hits']['hits'];
    }

    /**
     * @param array $queryParams
     * @param Resourceable|null $resource
     * @return array|callable
     */
    public function aggregation(array $queryParams, ?Resourceable $resource = null)
    {
        $body = array_merge(
            ['size' => 0],
            $this->searchBuilder->build($queryParams),
            $this->aggregationBuilder->build($queryParams)
        );

        $results = $this->client->search([
            'index' => $this->getConfig()->getName(),
            'body'  => $body
        ]);

        if (is_null($resource)) {
            return $results;
        }

        return $resource->toArray($results);
    }

    /**
     * Получить кол-во элементов
     *
     * @param SearchProperty $searchProperty
     * @return int
     */
    public function count(SearchProperty $searchProperty): int
    {
        $param = [
            'index' => $this->getConfig()->getName(),
            'body'  => $this->searchBuilder->build($searchProperty->getQueryParams())
        ];

        $result = $this->client->count($param);

        if (empty($result['count'])) {
            return 0;
        }

        return $result['count'];
    }
}
