<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage;

/**
 * This interface can be implemented by describers that needs {@see ResponseStorage}.
 */
interface ResponseStorageAwareInterface
{
    public function setResponseStorage(?ResponseStorage $responseStorage): void;
}
