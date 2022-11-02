<?php

namespace Oro\Bundle\UserBundle\Handler;

use Oro\Bundle\EntityBundle\Handler\AbstractEntityDeleteHandlerExtension;
use Oro\Bundle\OrganizationBundle\Ownership\OwnerDeletionManager;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * The delete handler extension for User entity.
 */
class UserDeleteHandlerExtension extends AbstractEntityDeleteHandlerExtension
{
    /** @var TokenAccessorInterface */
    private $tokenAccessor;

    /** @var OwnerDeletionManager */
    private $ownerDeletionManager;

    public function __construct(
        TokenAccessorInterface $tokenAccessor,
        OwnerDeletionManager $ownerDeletionManager
    ) {
        $this->tokenAccessor = $tokenAccessor;
        $this->ownerDeletionManager = $ownerDeletionManager;
    }

    /**
     * {@inheritdoc}
     */
    public function assertDeleteGranted($entity): void
    {
        /** @var User $entity */

        $loggedUser = $this->tokenAccessor->getUser();
        if ($loggedUser instanceof User && $loggedUser->getId() === $entity->getId()) {
            throw $this->createAccessDeniedException('self delete');
        }

        if ($this->ownerDeletionManager->hasAssignments($entity)) {
            throw $this->createAccessDeniedException('has associations to other entities');
        }
    }
}
