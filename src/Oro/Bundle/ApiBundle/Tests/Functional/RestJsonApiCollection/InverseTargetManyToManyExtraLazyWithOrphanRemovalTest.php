<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiCollection;

use Doctrine\Common\Collections\Collection;

/**
 * @group regression
 * @dbIsolationPerTest
 */
class InverseTargetManyToManyExtraLazyWithOrphanRemovalTest extends AbstractTargetManyToManyCollectionTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            '@OroApiBundle/Tests/Functional/DataFixtures/collection/timtm_extra_lazy_with_orphan_removal.yml'
        ]);
    }

    #[\Override]
    protected function isOrphanRemoval(): bool
    {
        return true;
    }

    #[\Override]
    protected function getAssociationName(): string
    {
        return 'inverseManyToManyExtraLazyWithOrphanRemovalParents';
    }

    #[\Override]
    protected function getItems($entity): Collection
    {
        return $entity->getInverseManyToManyExtraLazyWithOrphanRemovalParents();
    }
}
