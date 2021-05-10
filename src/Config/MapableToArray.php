<?php
declare(strict_types=1);


namespace OrangeShadow\ElasticSearch\Config;


trait MapableToArray
{
    public function toArray(): array
    {
        $result = [
            'type' => $this->getType(),
        ];

        if ($this->isEnabled() === false) {
            $result['enabled'] = false;
        }

        if (!is_null($this->getNormalizer())) {
            $result['normalizer'] = $this->getNormalizer();
        }

        if (!is_null($this->getAnalyzer())) {
            $result['analyzer'] = $this->getAnalyzer();
        }


        if (!empty($this->getProperties())) {
            $props = [];
            /**
             * @var  Mapable $item
             */
            foreach ($this->getProperties() as $item) {
                $props[ $item->getName() ] = $item->toArray();
            }

            $result['properties'] = $props;
        }

        return $result;
    }
}
