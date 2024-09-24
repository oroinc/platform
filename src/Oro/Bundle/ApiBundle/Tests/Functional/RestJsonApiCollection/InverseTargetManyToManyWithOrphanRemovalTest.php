<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiCollection;

use Doctrine\Common\Collections\Collection;

/**
 * @group regression
 * @dbIsolationPerTest
 */
class InverseTargetManyToManyWithOrphanRemovalTest extends AbstractTargetManyToManyCollectionTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            '@OroApiBundle/Tests/Functional/DataFixtures/collection/timtm_with_orphan_removal.yml'
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
        return 'inverseManyToManyWithOrphanRemovalParents';
    }

    #[\Override]
    protected function getItems($entity): Collection
    {
        return $entity->getInverseManyToManyWithOrphanRemovalParents();
    }
}
