<?php
declare(strict_types=1);


namespace OrangeShadow\ElasticSearch\Contracts;


interface Mapable
{
    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @return string
     */
    public function getType(): string;

    /**
     * @return string|null
     */
    public function getNormalizer(): ?string;

    /**
     * @return string
     */
    public function getAnalyzer(): ?string;

    /**
     * @return Mapable[]
     */
    public function getProperties(): ?array;

    /**
     * @return array
     */
    public function toArray():array;
}
