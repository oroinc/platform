<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage;

/**
 * This interface can be implemented by describers that needs {@see ParameterStorage}.
 */
interface ParameterStorageAwareInterface
{
    public function setParameterStorage(?ParameterStorage $parameterStorage): void;
}
