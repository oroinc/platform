<?php

namespace Oro\Bundle\OrganizationBundle\Handler;

use Oro\Bundle\EntityBundle\Handler\AbstractEntityDeleteHandlerExtension;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Repository\BusinessUnitRepository;
use Oro\Bundle\OrganizationBundle\Ownership\OwnerDeletionManager;

/**
 * The delete handler extension for BusinessUnit entity.
 */
class BusinessUnitDeleteHandlerExtension extends AbstractEntityDeleteHandlerExtension
{
    /** @var OwnerDeletionManager */
    private $ownerDeletionManager;

    public function __construct(OwnerDeletionManager $ownerDeletionManager)
    {
        $this->ownerDeletionManager = $ownerDeletionManager;
    }

    /**
     * {@inheritdoc}
     */
    public function assertDeleteGranted($entity): void
    {
        /** @var BusinessUnit $entity */

        if ($this->ownerDeletionManager->hasAssignments($entity)) {
            throw $this->createAccessDeniedException('has associations to other entities');
        }

        /** @var BusinessUnitRepository $repo */
        $repo = $this->getEntityRepository(BusinessUnit::class);
        if ($repo->getBusinessUnitsCount() <= 1) {
            throw $this->createAccessDeniedException('the last business unit');
        }
    }
}
