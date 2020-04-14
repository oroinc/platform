<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi\Collection;

use Doctrine\Common\Collections\Collection;

/**
 * @group regression
 * @dbIsolationPerTest
 */
class TargetManyToManyExtraLazyWithoutOrphanRemovalTest extends AbstractTargetManyToManyCollectionTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            '@OroApiBundle/Tests/Functional/DataFixtures/collection/tmtm_extra_lazy_without_orphan_removal.yml'
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
        return 'manyToManyExtraLazyWithoutOrphanRemovalParents';
    }

    /**
     * {@inheritDoc}
     */
    protected function getItems($entity): Collection
    {
        return $entity->getManyToManyExtraLazyWithoutOrphanRemovalParents();
    }

    public function testTryToUpdateWithRemoveItemFromCollectionAndHasValidationErrors()
    {
        $this->markTestSkipped('Remove this method in BAP-19905');
    }

    public function testTryToUpdateWithRemoveAllItemsFromCollectionAndHasValidationErrors()
    {
        $this->markTestSkipped('Remove this method in BAP-19905');
    }
}
