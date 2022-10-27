<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment;

class NullSkippedEntityProvider implements SkippedEntityProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function isSkippedEntity(string $entityClass, string $action): bool
    {
        return false;
    }
}
