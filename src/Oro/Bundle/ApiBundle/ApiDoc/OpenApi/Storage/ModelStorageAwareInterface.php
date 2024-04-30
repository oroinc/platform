<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage;

/**
 * This interface can be implemented by describers that needs {@see ModelStorage}.
 */
interface ModelStorageAwareInterface
{
    public function setModelStorage(?ModelStorage $modelStorage): void;
}
