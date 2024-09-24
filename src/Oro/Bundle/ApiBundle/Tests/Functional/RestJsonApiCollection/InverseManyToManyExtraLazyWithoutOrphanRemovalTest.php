<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiCollection;

use Doctrine\Common\Collections\Collection;

/**
 * @group regression
 * @dbIsolationPerTest
 */
class InverseManyToManyExtraLazyWithoutOrphanRemovalTest extends AbstractManyToManyCollectionTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            '@OroApiBundle/Tests/Functional/DataFixtures/collection/imtm_extra_lazy_without_orphan_removal.yml'
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
        return 'inverseManyToManyExtraLazyWithoutOrphanRemovalItems';
    }

    #[\Override]
    protected function getItems($entity): Collection
    {
        return $entity->getInverseManyToManyExtraLazyWithoutOrphanRemovalItems();
    }
}
