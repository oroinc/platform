<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage;

/**
 * This trait can be used by classes that implement {@see ParameterStorageAwareInterface}.
 */
trait ParameterStorageAwareTrait
{
    private ?ParameterStorage $parameterStorage = null;

    /**
     * {@inheritDoc}
     */
    public function setParameterStorage(?ParameterStorage $parameterStorage): void
    {
        $this->parameterStorage = $parameterStorage;
    }
}
