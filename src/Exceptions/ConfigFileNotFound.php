<?php
declare(strict_types=1);

namespace OrangeShadow\ElasticSearch\Exceptions;

class ConfigFileNotFound extends AbstractElasticSearchException
{
    /**
     * ConfigFileNotFound constructor.
     * @param string $filePath
     * @param string $message
     */
    public function __construct(string $filePath, $message = "Файл для создания индекса не найден")
    {
        parent::__construct($message . ':' . $filePath);
    }
}
