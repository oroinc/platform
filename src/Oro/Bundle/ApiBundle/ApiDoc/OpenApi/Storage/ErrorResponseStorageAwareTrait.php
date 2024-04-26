<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage;

/**
 * This trait can be used by classes that implement {@see ErrorResponseStorageAwareInterface}.
 */
trait ErrorResponseStorageAwareTrait
{
    private ?ErrorResponseStorage $errorResponseStorage = null;

    /**
     * {@inheritDoc}
     */
    public function setErrorResponseStorage(?ErrorResponseStorage $errorResponseStorage): void
    {
        $this->errorResponseStorage = $errorResponseStorage;
    }
}
