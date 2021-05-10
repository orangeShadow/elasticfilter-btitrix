<?php
declare(strict_types=1);

namespace OrangeShadow\ElasticSearch\Config;

use OrangeShadow\ElasticSearch\Exceptions\BadConfigFileException;
use OrangeShadow\ElasticSearch\Exceptions\ParseConfigException;
use Tightenco\Collect\Support\Collection;
use OrangeShadow\ElasticSearch\Exceptions\ConfigFileNotFound;


class IndexConfig
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var IndexMappingElement[]
     */
    private $mapping;

    /**
     * @var array
     */
    private $settings;

    /**
     * @var int
     */
    private $iBlockId;

    /**
     * @var int
     */
    private $offerIBlockId;

    /**
     * Создание конфига из файла настроек
     *
     * @param string $filePath
     * @throws ConfigFileNotFound
     * @throws ParseConfigException
     * @throws BadConfigFileException
     */
    public static function create(string $filePath): IndexConfig
    {
        if (!file_exists($filePath)) {
            throw new ConfigFileNotFound($filePath);
        }

        $content = json_decode(file_get_contents($filePath), true);

        if (json_last_error()) {
            throw new ParseConfigException(json_last_error_msg());
        }

        $config = new static();

        if (empty($config['name'])) {
            throw new BadConfigFileException('Отсутствует обязательный параметр название индекса');
        }

        $config->setName($content['name']);

        if (!empty($conten['mapping']) && is_array($content['mapping'])) {
            $mapping = new Collection();
            foreach ($content->mapping as $item) {
                $element = new IndexMappingElement;
                foreach ((array)$item as $field => $value) {
                    try {
                        $funcName = 'set' . $field;
                        $element->$funcName($value);
                    } catch (\Throwable $exception) {
                        new ParseConfigException('Неизвестный параметр в файле конфигурации');
                    }
                }
                $mapping->add($item);
            }
        }
    }

    /**
     * Сохранить Текущую конфигурацию в файл
     * @param string $filepath
     */
    public function saveToFile(string $filepath): bool
    {
        return file_put_contents($filepath, json_decode($this->toArray())) ? true : false;
    }

    /**
     * Получить имя индекса
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * @return Collection
     */
    public function getMapping(): Collection
    {
        return $this->mapping;
    }

    /**
     * @return array
     */
    public function getMappingForIndex(): array
    {
        $map = [];
        //TODO:сделать массив для mapping
        foreach ($this->mapping as $item) {
            $map[ $item->getName() ] = $item->toArray();
        }

        return $map;
    }

    /**
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * @param Collection $mapping
     */
    public function setMapping(Collection $mapping)
    {
        $this->mapping = $mapping;
    }

    /**
     * @param array $settings
     */
    public function setSettings(array $settings)
    {
        $this->settings = $settings;
    }

    /**
     * @return int
     */
    public function getIBlockId(): int
    {
        return $this->iBlockId;
    }

    /**
     * @param int $iBlockId
     */
    public function setIBlockId(int $iBlockId): void
    {
        $this->iBlockId = $iBlockId;
    }

    /**
     * @return int
     */
    public function getOfferIBlockId(): int
    {
        return $this->offerIBlockId;
    }

    /**
     * @param int $offerIBlockId
     */
    public function setOfferIBlockId(int $offerIBlockId): void
    {
        $this->offerIBlockId = $offerIBlockId;
    }


}
