<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage;

/**
 * This trait can be used by classes that implement {@see ResponseStorageAwareInterface}.
 */
trait ResponseStorageAwareTrait
{
    private ?ResponseStorage $responseStorage = null;

    /**
     * {@inheritDoc}
     */
    public function setResponseStorage(?ResponseStorage $responseStorage): void
    {
        $this->responseStorage = $responseStorage;
    }
}
