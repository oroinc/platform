<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment;

interface SkippedEntityProviderInterface
{
    /**
     * @param string $entityClass
     * @param string $action
     *
     * @return bool
     */
    public function isSkippedEntity(string $entityClass, string $action): bool;
}
