<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiCollection;

use Doctrine\Common\Collections\Collection;

/**
 * @group regression
 * @dbIsolationPerTest
 */
class TargetManyToManyExtraLazyWithoutOrphanRemovalTest extends AbstractTargetManyToManyCollectionTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            '@OroApiBundle/Tests/Functional/DataFixtures/collection/tmtm_extra_lazy_without_orphan_removal.yml'
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
        return 'manyToManyExtraLazyWithoutOrphanRemovalParents';
    }

    #[\Override]
    protected function getItems($entity): Collection
    {
        return $entity->getManyToManyExtraLazyWithoutOrphanRemovalParents();
    }
}
