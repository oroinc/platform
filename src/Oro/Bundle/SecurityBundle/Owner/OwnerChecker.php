<?php

namespace Oro\Bundle\SecurityBundle\Owner;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Acl\Domain\OneShotIsGrantedObserver;
use Oro\Bundle\SecurityBundle\Acl\Voter\AclVoter;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * This checker helps to check if user able to set the owner to the entity.
 */
class OwnerChecker
{
    /** @var OwnershipMetadataProviderInterface */
    protected $ownershipMetadataProvider;

    /** @var EntityOwnerAccessor */
    protected $ownerAccessor;

    /** @var BusinessUnitManager */
    protected $businessUnitManager;

    /** @var AclVoter */
    protected $aclVoter;

    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var OwnerTreeProvider */
    protected $treeProvider;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper                     $doctrineHelper
     * @param BusinessUnitManager                $businessUnitManager
     * @param OwnershipMetadataProviderInterface $ownershipMetadataProvider
     * @param EntityOwnerAccessor                $ownerAccessor
     * @param AuthorizationCheckerInterface      $authorizationChecker
     * @param TokenAccessorInterface             $tokenAccessor
     * @param OwnerTreeProvider                  $treeProvider
     * @param AclVoter                           $aclVoter
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        BusinessUnitManager $businessUnitManager,
        OwnershipMetadataProviderInterface $ownershipMetadataProvider,
        EntityOwnerAccessor $ownerAccessor,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenAccessorInterface $tokenAccessor,
        OwnerTreeProvider $treeProvider,
        AclVoter $aclVoter
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->ownershipMetadataProvider = $ownershipMetadataProvider;
        $this->ownerAccessor = $ownerAccessor;
        $this->businessUnitManager = $businessUnitManager;
        $this->aclVoter = $aclVoter;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenAccessor = $tokenAccessor;
        $this->treeProvider = $treeProvider;
    }

    /**
     * Checks if owner can be set for entity
     *
     * @param object $entity
     *
     * @return bool
     */
    public function isOwnerCanBeSet($entity)
    {
        if (!$this->tokenAccessor->hasUser()) {
            return true;
        }

        $entityClass = ClassUtils::getClass($entity);
        if (!$this->doctrineHelper->isManageableEntity($entity)) {
            return true;
        }

        $ownershipMetadata = $this->ownershipMetadataProvider->getMetadata($entityClass);
        if (!$ownershipMetadata || !$ownershipMetadata->hasOwner()) {
            return true;
        }

        $owner = $this->ownerAccessor->getOwner($entity);
        if (!$owner) {
            return true;
        }

        if (!$this->doctrineHelper->isNewEntity($entity)) {
            $accessLevel = $this->getAccessLevel('ASSIGN', $entity);
        } else {
            $accessLevel = $this->getAccessLevel('CREATE', 'entity:' . $entityClass);
        }

        $isOwnerValid = true;
        if ($accessLevel === null) {
            $isOwnerValid = false;
        } elseif (null !== $owner->getId()) {
            $isOwnerValid = $this->isValidOwner($ownershipMetadata, $owner, $accessLevel, $entity);
        } elseif ($this->ownerAccessor->getOrganization($owner) !== $this->ownerAccessor->getOrganization($entity)) {
            $isOwnerValid = false;
        }

        return $isOwnerValid;
    }

    /**
     * Returns true if given owner can be used
     *
     * @param OwnershipMetadataInterface $metadata
     * @param object                     $owner
     * @param integer                    $accessLevel
     * @param object                    $entity
     *
     * @return bool
     */
    protected function isValidOwner(OwnershipMetadataInterface $metadata, $owner, $accessLevel, $entity)
    {
        if ($metadata->isUserOwned()) {
            return $this->businessUnitManager->canUserBeSetAsOwner(
                $this->tokenAccessor->getUser(),
                $owner,
                $accessLevel,
                $this->treeProvider,
                $this->getOrganization($entity)
            );
        }

        if ($metadata->isBusinessUnitOwned()) {
            return $this->businessUnitManager->canBusinessUnitBeSetAsOwner(
                $this->tokenAccessor->getUser(),
                $owner,
                $accessLevel,
                $this->treeProvider,
                $this->getOrganization($entity)
            );
        }

        if ($metadata->isOrganizationOwned()) {
            return in_array(
                $owner->getId(),
                $this->treeProvider->getTree()->getUserOrganizationIds($this->tokenAccessor->getUserId()),
                true
            );
        }

        return true;
    }

    /**
     * @param $permission
     * @param $object
     *
     * @return int|null
     */
    protected function getAccessLevel($permission, $object)
    {
        $observer = new OneShotIsGrantedObserver();
        $this->aclVoter->addOneShotIsGrantedObserver($observer);
        if ($this->authorizationChecker->isGranted($permission, $object)) {
            return $observer->getAccessLevel();
        }

        return null;
    }


    /**
     * Returns current Organization
     *
     * @param object $object the test object
     *
     * @return Organization
     */
    protected function getOrganization($object)
    {
        return $this->tokenAccessor->getOrganization();
    }
}
