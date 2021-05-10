<?php

namespace OrangeShadow\ElasticSearch\Services;

use OrangeShadow\ElasticSearch\ElasticManager;
use OrangeShadow\ElasticSearch\BitrixExport;

class Indexer
{
    protected $catalogManager;
    protected $bitrixExport;

    /**
     * Indexer constructor.
     * @param string $uniqueField
     */
    public function __construct(string $uniqueField, int $productIBlockId, ?int $offerIBlockId = null)
    {
        \Bitrix\Main\Application::getInstance()->getManagedCache()->clean('elasticConfig');
        $catalogConfig = self::getElasticConfig($productIBlockId, $offerIBlockId);

        $this->catalogManager = new ElasticManager($catalogConfig);
        $this->bitrixExport = new BitrixExport($catalogConfig, $uniqueField,true);
    }


    /**
     * Запуск индексации
     */
    public function run()
    {
        $tempIndexName = $this->catalogManager->createIndex();
        if ($tempIndexName !== null) {
            $this->bitrixExport->run();
            $this->catalogManager->setNewIndexAlias($tempIndexName);
        }
    }

    /**
     * @param int $productIBlockId
     * @param int|null $offerIBlockId
     * @param array $defaultField
     * @return \OrangeShadow\ElasticSearch\Config\IndexConfig
     */
    public static function getElasticConfig(int $productIBlockId, ?int $offerIBlockId, array $defaultField=[])
    {
        $cacheId = 'elasticConfig';
        $cache = \Bitrix\Main\Application::getInstance()->getManagedCache();
        if ($cache->read(60 * 60 * 24, $cacheId)) {
            return $cache->get($cacheId);
        } else {
            $config = new \OrangeShadow\ElasticSearch\BitrixConfigGenerator('catalog',
                $productIBlockId,
                $offerIBlockId,
                $defaultField,
            );
            $catalogConfig = $config->generateConfig();
            $cache->set($cacheId, $catalogConfig); // записываем в кеш

            return $catalogConfig;
        }
    }

}
