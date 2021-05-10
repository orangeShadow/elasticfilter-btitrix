<?php
declare(strict_types=1);

namespace OrangeShadow\ElasticSearch;

class TypeEnum
{
    const KEYWORD = 'keyword';
    const FLOAT = 'float';
    const INT = 'integer';
    const NESTED = 'nested';
    const BOOL = 'boolean';
    const DATE = 'date';
    const TEXT = 'text';
    const OBJECT = 'object';
}
