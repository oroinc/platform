<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment;

class NullSkippedEntityProvider implements SkippedEntityProviderInterface
{
    #[\Override]
    public function isSkippedEntity(string $entityClass, string $action): bool
    {
        return false;
    }
}
