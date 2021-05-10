<?php
declare(strict_types=1);


namespace OrangeShadow\ElasticSearch\Contracts;


interface Resourceable
{
    /**
     * @param array $sources
     * @return mixed
     */
    public function toArray(array $sources): array;
}
