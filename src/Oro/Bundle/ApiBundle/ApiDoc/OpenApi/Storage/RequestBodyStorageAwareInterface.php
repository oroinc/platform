<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage;

/**
 * This interface can be implemented by describers that needs {@see RequestBodyStorage}.
 */
interface RequestBodyStorageAwareInterface
{
    public function setRequestBodyStorage(?RequestBodyStorage $requestBodyStorage): void;
}
