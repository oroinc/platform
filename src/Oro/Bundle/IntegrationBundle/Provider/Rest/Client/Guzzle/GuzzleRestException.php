<?php

namespace Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Guzzle;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException as BaseException;

class GuzzleRestException extends BaseException
{
    /**
     * @param \Exception $exception
     * @return GuzzleRestException
     */
    public static function createFromException(\Exception $exception)
    {
        return new static($exception->getMessage(), $exception->getCode(), $exception);
    }
}
