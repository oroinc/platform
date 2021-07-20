<?php

namespace Oro\Bundle\OrganizationBundle\Validator\Constraints;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager;
use Oro\Bundle\SecurityBundle\Acl\Group\AclGroupProviderInterface;
use Oro\Bundle\SecurityBundle\Acl\Voter\AclVoterInterface;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProviderInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Validates that the current logged in user is granted to change the owner for an entity.
 */
class OwnerValidator extends AbstractOwnerValidator
{
    /** @var BusinessUnitManager */
    private $businessUnitManager;

    public function __construct(
        ManagerRegistry $doctrine,
        OwnershipMetadataProviderInterface $ownershipMetadataProvider,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenAccessorInterface $tokenAccessor,
        OwnerTreeProviderInterface $ownerTreeProvider,
        AclVoterInterface $aclVoter,
        AclGroupProviderInterface $aclGroupProvider,
        BusinessUnitManager $businessUnitManager
    ) {
        parent::__construct(
            $doctrine,
            $ownershipMetadataProvider,
            $authorizationChecker,
            $tokenAccessor,
            $ownerTreeProvider,
            $aclVoter,
            $aclGroupProvider
        );
        $this->businessUnitManager = $businessUnitManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function isValidExistingOwner(OwnershipMetadataInterface $ownershipMetadata, $owner, $accessLevel)
    {
        $currentUser = $this->tokenAccessor->getUser();
        if (null === $currentUser) {
            return true;
        }

        if ($ownershipMetadata->isUserOwned()) {
            return $this->businessUnitManager->canUserBeSetAsOwner(
                $currentUser,
                $owner,
                $accessLevel,
                $this->ownerTreeProvider,
                $this->getOrganization()
            );
        }
        if ($ownershipMetadata->isBusinessUnitOwned()) {
            return $this->businessUnitManager->canBusinessUnitBeSetAsOwner(
                $currentUser,
                $owner,
                $accessLevel,
                $this->ownerTreeProvider,
                $this->getOrganization()
            );
        }
        if ($ownershipMetadata->isOrganizationOwned()) {
            return in_array(
                $owner->getId(),
                $this->ownerTreeProvider->getTree()->getUserOrganizationIds($this->tokenAccessor->getUserId()),
                true
            );
        }

        return true;
    }
}
