<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiCollection;

use Doctrine\Common\Collections\Collection;

/**
 * @group regression
 * @dbIsolationPerTest
 */
class OneToManyWithoutOrphanRemovalTest extends AbstractOneToManyCollectionTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            '@OroApiBundle/Tests/Functional/DataFixtures/collection/otm_without_orphan_removal.yml'
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
        return 'withoutOrphanRemovalItems';
    }

    #[\Override]
    protected function getItems($entity): Collection
    {
        return $entity->getWithoutOrphanRemovalItems();
    }
}
