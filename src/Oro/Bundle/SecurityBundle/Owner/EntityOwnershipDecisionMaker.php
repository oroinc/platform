<?php

namespace Oro\Bundle\SecurityBundle\Owner;

use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdAccessor;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Makes ownership decisions for entities based on the current user.
 *
 * This decision maker determines whether the current user owns an entity by checking
 * if the user is the owner of the entity. It supports the standard user-based ownership
 * model and is used to enforce ownership-based access control in the system.
 */
class EntityOwnershipDecisionMaker extends AbstractEntityOwnershipDecisionMaker
{
    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    public function __construct(
        OwnerTreeProviderInterface $treeProvider,
        ObjectIdAccessor $objectIdAccessor,
        EntityOwnerAccessor $entityOwnerAccessor,
        OwnershipMetadataProviderInterface $ownershipMetadataProvider,
        TokenAccessorInterface $tokenAccessor
    ) {
        parent::__construct($treeProvider, $objectIdAccessor, $entityOwnerAccessor, $ownershipMetadataProvider);
        $this->tokenAccessor = $tokenAccessor;
    }

    #[\Override]
    public function supports()
    {
        return $this->tokenAccessor->getUser() instanceof User;
    }
}
