<?php
declare(strict_types=1);

namespace OrangeShadow\ElasticSearch\Properties;

use Tightenco\Collect\Support\Collection;

/**
 * Класс для передачи параметров поиска по эластику
 *
 * Class SearchProperties
 * @package OrangeShadow\ElasticSearch\Properties
 */
class SearchProperty
{
    /**
     * Массив условий поиска
     *
     * @var array $queryParams
     */
    private $queryParams;

    /**
     * Номер страницы
     *
     * @var int $page
     */
    private $page = 1;

    /**
     * Кол-во выдаваемых элементов
     *
     * @var int $size
     */
    private $size = 10000;

    /**
     * Коллекция сортировок [$sort => $direction]
     *
     * @var Collection $sortList
     */
    private $sortList;

    /**
     * Список возвращаемых данных
     *
     * @var array
     */
    private $select = ['bitrixId'];

    /**
     * @return array
     */
    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    /**
     * @param array $queryParams
     * @param self
     */
    public function setQueryParams(array $queryParams): self
    {
        $this->queryParams = $queryParams;

        return $this;
    }

    /**
     * @return int
     */
    public function getPage(): int
    {
        return $this->page;
    }

    /**
     * @param int $page
     * @param self
     */
    public function setPage(int $page): self
    {
        $this->page = $page;

        return $this;
    }

    /**
     * @return int
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * @param int $size
     * @param self
     */
    public function setSize(int $size): self
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Добавить сортировку
     *
     * @param string $sort
     * @param string $direction asc|direction
     *
     * @return self
     */
    public function addSort(string $sort, string $direction): self
    {
        $this->sortList->add([$sort => strtolower($direction)]);

        return $this;
    }

    /**
     * Добавить сортировку из строки запроса
     *
     * @param string $sortString
     *
     * @return self
     */
    public function addSortByRequest(string $sortString): self
    {
        switch ($sortString) {
            case 'popular':
                return $this->addSort('show_counter', 'desc');
            case 'price_asc':
                return $this->addSort('min_price', 'asc');
            case 'price_desc':
                return $this->addSort('min_price', 'desc');
            case 'discount_asc':
                $this->addSort('skidka', 'asc');
                $this->addSort('sort', 'asc');

                return $this;
            case 'discount_desc':
                $this->addSort('skidka', 'desc');
                $this->addSort('sort', 'asc');

                return $this;
            case 'new':
                $this->addSort('first_activation', 'desc');
                return $this;
            default:
                $this->addSort('sort', 'asc');
                $this->addSort('bitrixId', 'desc');
                return $this;
        }
    }

    /**
     * Очистить сортировку
     *
     * @return self
     */
    public function clearSortList(): self
    {
        $this->sortList = new Collection();

        return $this;
    }


    /**
     * @return array
     */
    public function getSelect(): array
    {
        return $this->select;
    }

    /**
     * @param array $select
     * @param self
     */
    public function setSource(array $select): self
    {
        $this->select = $select;

        return $this;
    }

    /**
     * SearchProperty constructor.
     * @param array $queryParams
     */
    public function __construct(array $queryParams)
    {
        $this->setQueryParams($queryParams);
        $this->sortList = new Collection();
    }

    /**
     * Подготовка сортировки
     *
     * @return array
     */
    public function getSortArray(): array
    {
        return $this->sortList->toArray();
    }

    /**
     * Сдвиг при поиске
     *
     * @return int
     */
    public function getFrom(): int
    {
        return ($this->getPage() - 1) * $this->getSize();
    }

    /**
     * @return array
     */
    public function __serialize(): array
    {
        return array_merge(
            ['page' => $this->getPage()],
            $this->getQueryParams(),
            $this->getSortArray(),
            ['size' => $this->getSize()],
            ['select' => $this->getSelect()]
        );
    }
}
