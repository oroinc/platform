<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiCollection;

use Doctrine\Common\Collections\Collection;

/**
 * @group regression
 * @dbIsolationPerTest
 */
class InverseManyToManyExtraLazyWithOrphanRemovalTest extends AbstractManyToManyCollectionTestCase
{
    protected function setUp(): void
    {
        self::markTestSkipped('Must be fixed in BAP-21553');

        parent::setUp();
        $this->loadFixtures([
            '@OroApiBundle/Tests/Functional/DataFixtures/collection/imtm_extra_lazy_with_orphan_removal.yml'
        ]);
    }

    /**
     * {@inheritDoc}
     */
    protected function isOrphanRemoval(): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    protected function getAssociationName(): string
    {
        return 'inverseManyToManyExtraLazyWithOrphanRemovalItems';
    }

    /**
     * {@inheritDoc}
     */
    protected function getItems($entity): Collection
    {
        return $entity->getInverseManyToManyExtraLazyWithOrphanRemovalItems();
    }
}
