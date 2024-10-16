<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiCollection;

use Doctrine\Common\Collections\Collection;

/**
 * @group regression
 * @dbIsolationPerTest
 */
class TargetManyToManyLazyWithoutOrphanRemovalTest extends AbstractTargetManyToManyCollectionTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            '@OroApiBundle/Tests/Functional/DataFixtures/collection/tmtm_lazy_without_orphan_removal.yml'
        ]);
    }

    #[\Override]
    protected function isOrphanRemoval(): bool
    {
        return false;
    }

    #[\Override]
    protected function getAssociationName(): string
    {
        return 'manyToManyLazyWithoutOrphanRemovalParents';
    }

    #[\Override]
    protected function getItems($entity): Collection
    {
        return $entity->getManyToManyLazyWithoutOrphanRemovalParents();
    }
}
