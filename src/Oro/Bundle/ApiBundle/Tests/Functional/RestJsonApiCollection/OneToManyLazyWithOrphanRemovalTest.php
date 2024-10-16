<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiCollection;

use Doctrine\Common\Collections\Collection;

/**
 * @group regression
 * @dbIsolationPerTest
 */
class OneToManyLazyWithOrphanRemovalTest extends AbstractOneToManyCollectionTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            '@OroApiBundle/Tests/Functional/DataFixtures/collection/otm_lazy_with_orphan_removal.yml'
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
        return 'lazyWithOrphanRemovalItems';
    }

    #[\Override]
    protected function getItems($entity): Collection
    {
        return $entity->getLazyWithOrphanRemovalItems();
    }
}
