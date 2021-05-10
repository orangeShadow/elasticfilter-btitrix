<?php
declare(strict_types=1);


namespace OrangeShadow\ElasticSearch\Config;

use OrangeShadow\ElasticSearch\Contracts\Mapable;

class IndexMappingProperty implements Mapable
{
    use MapableToArray;

    /**
     * Название свойства для маппинга
     * @var string
     */
    private $name;

    /**
     * Тип свойства
     * @var string
     */
    private $type;

    /**
     * @var bool
     */
    private $enabeld = true;

    /**
     * Нормализация токена
     * @var string|null
     */
    private $normalizer;

    /**
     *
     * @var array|null
     */
    private $properties;

    /**
     * @var string|null
     */
    private $analyzer;


    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return self
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return self
     */
    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getNormalizer(): ?string
    {
        return $this->normalizer;
    }

    /**
     * @param string $normalizer
     */
    public function setNormalizer(string $normalizer): self
    {
        $this->normalizer = $normalizer;

        return $this;
    }

    /**
     * @return Mapable[]|null
     */
    public function getProperties(): ?array
    {
        return $this->properties;
    }

    /**
     * @param array $properties
     */
    public function setProperties(array $properties): self
    {
        $this->properties = $properties;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAnalyzer(): ?string
    {
        return $this->analyzer;
    }

    /**
     * @param string $analyzer
     * @return self
     */
    public function setAnalyzer(string $analyzer): self
    {
        $this->analyzer = $analyzer;

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabeld;
    }
}
