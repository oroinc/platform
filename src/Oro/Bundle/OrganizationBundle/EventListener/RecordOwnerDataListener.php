<?php

namespace Oro\Bundle\OrganizationBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\OrganizationBundle\Ownership\EntityOwnershipAssociationsSetter;

/**
 * Listener that sets owner and organization to new entity if this data was not set.
 */
class RecordOwnerDataListener
{
    /** @var EntityOwnershipAssociationsSetter */
    private $entityOwnershipAssociationsSetter;

    public function __construct(EntityOwnershipAssociationsSetter $entityOwnershipAssociationsSetter)
    {
        $this->entityOwnershipAssociationsSetter = $entityOwnershipAssociationsSetter;
    }

    /**
     * Handle prePersist.
     *
     * @throws \LogicException when getOwner method isn't implemented for entity with ownership type
     */
    public function prePersist(LifecycleEventArgs $args): void
    {
        $this->entityOwnershipAssociationsSetter->setOwnershipAssociations($args->getEntity());
    }
}
