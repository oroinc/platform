<?php

namespace Oro\Bundle\OrganizationBundle\Validator\Constraints;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

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

    /** @var ManagerRegistry */
    protected $registry;

    /** @var object */
    protected $object;

    /**
     * @param ManagerRegistry           $registry
     * @param BusinessUnitManager       $businessUnitManager
     * @param OwnershipMetadataProvider $ownershipMetadataProvider
     * @param EntityOwnerAccessor       $entityOwnerAccessor
     * @param SecurityFacade            $securityFacade
     * @param OwnerTreeProvider         $treeProvider
     * @param AclVoter                  $aclVoter
     */
    public function __construct(
        ManagerRegistry $registry,
        BusinessUnitManager $businessUnitManager,
        OwnershipMetadataProvider $ownershipMetadataProvider,
        EntityOwnerAccessor $entityOwnerAccessor,
        SecurityFacade $securityFacade,
        OwnerTreeProvider $treeProvider,
        AclVoter $aclVoter
    ) {
        $this->registry = $registry;
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

        $entityClass = ClassUtils::getClass($value);
        $manager = $this->registry->getManagerForClass($entityClass);
        if (!$manager) {
            return;
        }

        $ownershipMetadata = $this->ownershipMetadataProvider->getMetadata($entityClass);
        if (!$ownershipMetadata || !$ownershipMetadata->hasOwner()) {
            return;
        }

        $owner = $this->entityOwnerAccessor->getOwner($value);
        if (!$owner) {
            return;
        }

        $idValues = $manager->getClassMetadata($entityClass)->getIdentifierValues($value);
        if (count($idValues) !== 0) {
            $accessLevel = $this->getAccessLevel('ASSIGN', $value);
        } else {
            $accessLevel = $this->getAccessLevel('CREATE', 'entity:' . $entityClass);
        }

        if ($accessLevel === null) {
            $isOwnerValid = false;
        } else {
            $isOwnerValid = $this->isValidOwner($ownershipMetadata, $owner, $accessLevel);
        }

        if (!$isOwnerValid) {
            $ownerFieldName = $ownershipMetadata->getOwnerFieldName();
            /** @var ExecutionContextInterface $context */
            $context = $this->context;
            $context->buildViolation($constraint->message)
                ->atPath($ownerFieldName)
                ->setParameter('{{ owner }}', $ownerFieldName)
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
    protected function isValidOwner(OwnershipMetadataInterface $metadata, $owner, $accessLevel)
    {
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
