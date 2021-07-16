<?php

namespace Oro\Bundle\OrganizationBundle\Handler;

use Oro\Bundle\EntityBundle\Handler\AbstractEntityDeleteHandlerExtension;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Ownership\OwnerDeletionManager;

/**
 * The delete handler extension for Organization entity.
 */
class OrganizationDeleteHandlerExtension extends AbstractEntityDeleteHandlerExtension
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
        /** @var Organization $entity */

        if ($this->ownerDeletionManager->hasAssignments($entity)) {
            throw $this->createAccessDeniedException('has associations to other entities');
        }
    }
}
