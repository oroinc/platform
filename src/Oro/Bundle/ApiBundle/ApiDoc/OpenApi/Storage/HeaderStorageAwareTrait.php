<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage;

/**
 * This trait can be used by classes that implement {@see HeaderStorageAwareInterface}.
 */
trait HeaderStorageAwareTrait
{
    private ?HeaderStorage $headerStorage = null;

    /**
     * {@inheritDoc}
     */
    public function setHeaderStorage(?HeaderStorage $headerStorage): void
    {
        $this->headerStorage = $headerStorage;
    }
}
