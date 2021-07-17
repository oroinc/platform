<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment;

interface SkippedEntityProviderInterface
{
    public function isSkippedEntity(string $entityClass, string $action): bool;
}
