<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiCollection;

use Doctrine\Common\Collections\Collection;

/**
 * @group regression
 * @dbIsolationPerTest
 */
class ManyToManyExtraLazyWithoutOrphanRemovalTest extends AbstractManyToManyCollectionTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            '@OroApiBundle/Tests/Functional/DataFixtures/collection/mtm_extra_lazy_without_orphan_removal.yml'
        ]);
    }

    /**
     * {@inheritDoc}
     */
    protected function isOrphanRemoval(): bool
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    protected function getAssociationName(): string
    {
        return 'manyToManyExtraLazyWithoutOrphanRemovalItems';
    }

    /**
     * {@inheritDoc}
     */
    protected function getItems($entity): Collection
    {
        return $entity->getManyToManyExtraLazyWithoutOrphanRemovalItems();
    }
}
