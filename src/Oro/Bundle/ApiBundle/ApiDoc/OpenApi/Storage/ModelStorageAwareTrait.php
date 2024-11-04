<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage;

/**
 * This trait can be used by classes that implement {@see ModelStorageAwareInterface}.
 */
trait ModelStorageAwareTrait
{
    private ?ModelStorage $modelStorage = null;

    public function setModelStorage(?ModelStorage $modelStorage): void
    {
        $this->modelStorage = $modelStorage;
    }
}
