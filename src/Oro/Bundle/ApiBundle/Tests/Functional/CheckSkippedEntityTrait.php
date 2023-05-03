<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\SkippedEntityProviderInterface;

trait CheckSkippedEntityTrait
{
    private function isSkippedEntity(string $entityClass, string $action): bool
    {
        /** @var SkippedEntityProviderInterface $provider */
        $provider = self::getContainer()->get('oro_api.tests.skipped_entity_provider');

        return $provider->isSkippedEntity($entityClass, $action);
    }
}
