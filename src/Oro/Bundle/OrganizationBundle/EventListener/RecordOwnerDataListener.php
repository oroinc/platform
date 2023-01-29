<?php

namespace Oro\Bundle\OrganizationBundle\EventListener;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\OrganizationBundle\Ownership\EntityOwnershipAssociationsSetter;

/**
 * Sets owner and organization to new entity if this data was not set yet.
 */
class RecordOwnerDataListener
{
    private EntityOwnershipAssociationsSetter $entityOwnershipAssociationsSetter;

    public function __construct(EntityOwnershipAssociationsSetter $entityOwnershipAssociationsSetter)
    {
        $this->entityOwnershipAssociationsSetter = $entityOwnershipAssociationsSetter;
    }

    public function prePersist(LifecycleEventArgs $args): void
    {
        $this->entityOwnershipAssociationsSetter->setOwnershipAssociations($args->getEntity());
    }
}
