<?php
declare(strict_types=1);


namespace OrangeShadow\ElasticSearch\Config;

use OrangeShadow\ElasticSearch\Contracts\Mapable;

class IndexMappingElement implements Mapable
{
    use MapableToArray;


    /**
     * Название свойства для маппинга
     * @var string
     */
    private $name;

    /**
     * Название свойства на русском
     * @var string
     */
    private $title;

    /**
     * Подсказка для фильтра
     * @var string|null
     */
    private $hint;

    /**
     * Тип свойства для индексации
     * @var string
     */
    private $type;

    /**
     * @var bool
     */
    private $enabled=true;

    /**
     * Тип свойства для отображения
     * @var string
     */
    private $showingType;

    /**
     *
     * @var array|null
     */
    private $properties;

    /**
     * @var string|null
     */
    private $normalizer;

    /**
     * @var string|null
     */
    private $analyzer;


    /**
     * Инфоблок для выборки
     * @var int|null
     */
    private $bitrixIblockId;

    /**
     * полное название свойсва в битриксе PROPERTY_TEST
     * @var string|null
     */
    private $bitrixIblockField;

    /**
     * Тип свойства
     * @var string|null
     */
    private $bitrixIblockPropertyLink;

    /**
     * Тип значения в Битрикс
     * @var string|null
     */
    private $bitrixIblockFieldType;

    /**
     * Название hlтаблицы
     * @var string|null
     */
    private $bitrixHL;

    /**
     * @var bool
     */
    private $hasInFilter = false;

    /**
     * @var bool
     */
    private $hasInSearch = false;

    /**
     * @var array|null
     */
    private $showInSection = null;

    /**
     * Property Sort
     * @var int
     */
    private $sort = 0;

    /**
     * Property type
     * @var null
     */
    private $propertyType = null;

    /**
     * @var $propertyId
     */
    private $propertyId = null;

    /**
     * @var callable|null
     */
    private $function = null;

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
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return self
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getHint(): ?string
    {
        return $this->hint;
    }

    /**
     * @param ?string $hint
     * @return self
     */
    public function setHint(?string $hint): self
    {
        $this->hint = $hint;

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
    public function getShowingType(): ?string
    {
        return $this->showingType;
    }

    /**
     * @param string $type
     * @return self
     */
    public function setShowingType(string $type): self
    {
        $this->showingType = $type;

        return $this;
    }

    /**
     * @return array
     */
    public function getProperties(): ?array
    {
        return $this->properties;
    }

    /**
     * @param Mapable[] $properties
     * @return self
     */
    public function setProperties(array $properties): self
    {
        $this->properties = $properties;

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
     * @param mixed $normalizer
     * @return self
     */
    public function setNormalizer($normalizer): self
    {
        $this->normalizer = $normalizer;

        return $this;
    }

    /**
     * @return string
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


    /**
     * @return int
     */
    public function getBitrixIblockId(): ?int
    {
        return $this->bitrixIblockId;
    }

    /**
     * @param int $bitrixIblockId
     * @return self
     */
    public function setBitrixIblockId(int $bitrixIblockId): self
    {
        $this->bitrixIblockId = $bitrixIblockId;

        return $this;
    }

    /**
     * @return string
     */
    public function getBitrixIblockField(): ?string
    {
        return $this->bitrixIblockField;
    }

    /**
     * @param string $bitrixIblockField
     * @return self
     */
    public function setBitrixIblockField(string $bitrixIblockField): self
    {
        $this->bitrixIblockField = $bitrixIblockField;

        return $this;
    }


    /**
     * @return string
     */
    public function getTargetClass(): ?string
    {
        return $this->targetClass;
    }

    /**
     * @param string $targetClass
     * @return self
     */
    public function setTargetClass(string $targetClass): self
    {
        $this->targetClass = $targetClass;

        return $this;
    }

    /**
     * @return string
     */
    public function getTargetMethod(): ?string
    {
        return $this->targetMethod;
    }

    /**
     * @param string $targetMethod
     * @return self
     */
    public function setTargetMethod(string $targetMethod): self
    {
        $this->targetMethod = $targetMethod;

        return $this;
    }

    /**
     * @return bool
     */
    public function isHasInFilter(): bool
    {
        return $this->hasInFilter ?? false;
    }

    /**
     * @param bool $hasInFilter
     * @return self
     */
    public function setHasInFilter(bool $hasInFilter): self
    {
        $this->hasInFilter = $hasInFilter;

        return $this;
    }

    /**
     * @return bool
     */
    public function isHasInSearch(): bool
    {
        return $this->hasInSearch ?? false;
    }

    /**
     * @param bool $hasInSearch
     * @return self
     */
    public function setHasInSearch(bool $hasInSearch): self
    {
        $this->hasInSearch = $hasInSearch;

        return $this;
    }

    /**
     * @return array|null
     */
    public function getShowInSection(): ?array
    {
        return $this->showInSection;
    }

    /**
     * @param array|null $showInSection
     * @return self
     */
    public function setShowInSection(?array $showInSection): self
    {
        $this->showInSection = $showInSection;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getBitrixIblockPropertyLink(): ?int
    {
        return $this->bitrixIblockPropertyLink;
    }

    /**
     * @param string|null $bitrixIblockPropertyLink
     * @return self
     */
    public function setBitrixIblockPropertyLink(?int $bitrixIblockPropertyLink): self
    {
        $this->bitrixIblockPropertyLink = $bitrixIblockPropertyLink;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getBitrixIblockFieldType(): ?string
    {
        return $this->bitrixIblockFieldType;
    }

    /**
     * @param string|null $bitrixIblockFieldType
     * @return self
     */
    public function setBitrixIblockFieldType(?string $bitrixIblockFieldType): self
    {
        $this->bitrixIblockFieldType = $bitrixIblockFieldType;

        return $this;
    }

    /**
     * @return ?string
     */
    public function getBitrixHL(): ?string
    {
        return $this->bitrixHL;
    }

    /**
     * @param string $bitrixHL
     */
    public function setBitrixHL(string $bitrixHL): void
    {
        $this->bitrixHL = $bitrixHL;
    }

    /**
     * @return int
     */
    public function getSort(): int
    {
        return $this->sort;
    }

    /**
     * @param int $sort
     */
    public function setSort(int $sort): void
    {
        $this->sort = $sort;
    }

    /**
     * @return null
     */
    public function getPropertyType()
    {
        return $this->propertyType;
    }

    /**
     * @param null $propertyType
     */
    public function setPropertyType($propertyType): void
    {
        $this->propertyType = $propertyType;
    }

    /**
     * @return mixed
     */
    public function getPropertyId()
    {
        return $this->propertyId;
    }

    /**
     * @param mixed $propertyId
     */
    public function setPropertyId($propertyId): void
    {
        $this->propertyId = $propertyId;
    }

    /**
     * @param callable $callbable
     */
    public function setFunction(callable $callable): void
    {
        $this->function = $callable;
    }

    /**
     * @return callable $callable
     */
    public function getFunction(): callable
    {
        return $this->function;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     */
    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }


}
