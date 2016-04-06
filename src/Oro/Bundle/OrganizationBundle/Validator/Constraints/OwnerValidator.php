<?php

namespace Oro\Bundle\OrganizationBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Acl\Domain\OneShotIsGrantedObserver;
use Oro\Bundle\SecurityBundle\Acl\Voter\AclVoter;
use Oro\Bundle\SecurityBundle\Owner\EntityOwnerAccessor;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class OwnerValidator extends ConstraintValidator
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var OwnershipMetadataProvider */
    protected $ownershipMetadataProvider;

    /** @var EntityOwnerAccessor */
    protected $entityOwnerAccessor;

    /** @var BusinessUnitManager */
    protected $businessUnitManager;

    /** @var AclVoter */
    protected $aclVoter;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var OwnerTreeProvider */
    protected $treeProvider;

    protected $object;

    /**
     * @param DoctrineHelper            $doctrineHelper
     * @param OwnershipMetadataProvider $ownershipMetadataProvider
     * @param EntityOwnerAccessor       $entityOwnerAccessor
     * @param BusinessUnitManager       $businessUnitManager
     * @param AclVoter                  $aclVoter
     * @param SecurityFacade            $securityFacade
     * @param OwnerTreeProvider         $treeProvider
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        OwnershipMetadataProvider $ownershipMetadataProvider,
        EntityOwnerAccessor $entityOwnerAccessor,
        BusinessUnitManager $businessUnitManager,
        AclVoter $aclVoter,
        SecurityFacade $securityFacade,
        OwnerTreeProvider $treeProvider
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->ownershipMetadataProvider = $ownershipMetadataProvider;
        $this->entityOwnerAccessor = $entityOwnerAccessor;
        $this->businessUnitManager = $businessUnitManager;
        $this->aclVoter = $aclVoter;
        $this->securityFacade = $securityFacade;
        $this->treeProvider = $treeProvider;
    }


    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        $this->object = $value;

        $entityClass = $this->doctrineHelper->getEntityClass($value);
        if (!$this->doctrineHelper->isManageableEntity($entityClass)) {
            return;
        }

        $metadata = $this->getMetadata($entityClass);
        if (!$metadata) {
            return;
        }

        $owner = $this->entityOwnerAccessor->getOwner($value);
        if (!$owner) {
            return;
        }

        $entityId = $this->doctrineHelper->getSingleEntityIdentifier($value);
        if ($entityId) {
            $accessLevel = $this->getAccessLevel('ASSIGN', $value);
        } else {
            $accessLevel = $this->getAccessLevel('CREATE', 'entity:' . $entityClass);
        }

        if (!$this->isOwnerCorrect($metadata, $owner, $accessLevel)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('%value%', $owner->getId())
                ->setParameter('%owner%', $metadata->getOwnerFieldName())
                ->addViolation();
        }
    }

    /**
     * Returns true if given owner can be used
     *
     * @param OwnershipMetadataInterface $metadata
     * @param object                     $owner
     * @param integer                    $accessLevel
     *
     * @return bool
     */
    protected function isOwnerCorrect(OwnershipMetadataInterface $metadata, $owner, $accessLevel)
    {
        if ($accessLevel === null) {
            return false;
        }

        if ($metadata->isBasicLevelOwned()) {
            return $this->businessUnitManager->canUserBeSetAsOwner(
                $this->securityFacade->getLoggedUser(),
                $owner,
                $accessLevel,
                $this->treeProvider,
                $this->getOrganization()
            );
        } elseif ($metadata->isLocalLevelOwned()) {
            return $this->businessUnitManager->canBusinessUnitBeSetAsOwner(
                $this->securityFacade->getLoggedUser(),
                $owner,
                $accessLevel,
                $this->treeProvider,
                $this->getOrganization()
            );
        } elseif ($metadata->isGlobalLevelOwned()) {
            return in_array(
                $owner->getId(),
                $this->treeProvider->getTree()->getUserOrganizationIds($this->securityFacade->getLoggedUserId()),
                true
            );
        }

        return true;
    }

    /**
     * Get metadata for entity class
     *
     * @param string $entityClass
     *
     * @return bool|OwnershipMetadataInterface
     */
    protected function getMetadata($entityClass)
    {
        $metadata = $this->ownershipMetadataProvider->getMetadata($entityClass);

        return $metadata->hasOwner()
            ? $metadata
            : false;
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
        if ($this->securityFacade->isGranted($permission, $object)) {
            return $observer->getAccessLevel();
        }

        return null;
    }

    /**
     * Returns current Organization
     *
     * @return Organization
     */
    protected function getOrganization()
    {
        return $this->securityFacade->getOrganization();
    }
}
