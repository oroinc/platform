<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage;

/**
 * This interface can be implemented by describers that needs {@see HeaderStorage}.
 */
interface HeaderStorageAwareInterface
{
    public function setHeaderStorage(?HeaderStorage $headerStorage): void;
}
