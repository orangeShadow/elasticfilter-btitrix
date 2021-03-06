<?php
declare(strict_types=1);

namespace OrangeShadow\ElasticSearch\Exceptions;

use Throwable;

abstract class AbstractElasticSearchException extends \Exception
{
    /**
     * AbstractElasticSearchException constructor.
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
