<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage;

/**
 * This trait can be used by classes that implement {@see RequestBodyStorageAwareInterface}.
 */
trait RequestBodyStorageAwareTrait
{
    private ?RequestBodyStorage $requestBodyStorage = null;

    public function setRequestBodyStorage(?RequestBodyStorage $requestBodyStorage): void
    {
        $this->requestBodyStorage = $requestBodyStorage;
    }
}
