# elasticfilter-btitrix

Пока что еще сырой прототип.

1) Достает настройки умных фильтров из битрикс и складывает их в эластик
2) Экземпляр Эластик менеджер может искать по Keyword и Аггрегировать данные
3) Запускается индексация при помощи экземпляра Services/Indexer
   (3-й параметр позволяет засунуть в эластик "левые" данные, например для отображения на странице,
   по которым не нужно аггрегировать пример в классе BitrixConfigGenerator
   );

Пример поиска элемента по коду   

```
$config = Indexer::getElasticConfig($productId);

$this->manager = new ElasticManager($config);


$queryParams = [
    "code" => $code
];

$searchProperty = new SearchProperty($queryParams);
$searchProperty->setSource([]);

try {
    $items = $this->manager->search($searchProperty);
} catch (\Exception $e) {
    return false;
}
```

Пример аггрегации
```
$queryParams = [
    "section" => $sectionId
];

$searchProperty = new SearchProperty($queryParams);
$searchProperty->setSource([]);

try {
    $items = $this->manager->aggregatino($searchProperty, new \OrangeShadow\ElasticSearch\Resources\CatalogAggregationResource($items, $config));
} catch (\Exception $e) {
    return false;
}
```
