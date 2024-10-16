<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiCollection;

use Doctrine\Common\Collections\Collection;

/**
 * @group regression
 * @dbIsolationPerTest
 */
class OneToManyLazyWithoutOrphanRemovalTest extends AbstractOneToManyCollectionTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            '@OroApiBundle/Tests/Functional/DataFixtures/collection/otm_lazy_without_orphan_removal.yml'
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
        return 'lazyWithoutOrphanRemovalItems';
    }

    #[\Override]
    protected function getItems($entity): Collection
    {
        return $entity->getLazyWithoutOrphanRemovalItems();
    }
}
