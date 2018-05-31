<?php

namespace Oro\Bundle\OrganizationBundle\Validator\Constraints;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Acl\Domain\OneShotIsGrantedObserver;
use Oro\Bundle\SecurityBundle\Acl\Voter\AclVoter;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that the current logged in user is granted to change the owner for an entity.
 */
class OwnerValidator extends ConstraintValidator
{
    /** @var ManagerRegistry */
    private $doctrine;

    /** @var OwnershipMetadataProviderInterface */
    private $ownershipMetadataProvider;

    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /** @var TokenAccessorInterface */
    private $tokenAccessor;

    /** @var OwnerTreeProvider */
    private $ownerTreeProvider;

    /** @var AclVoter */
    private $aclVoter;

    /** @var BusinessUnitManager */
    private $businessUnitManager;

    /**
     * @param ManagerRegistry                    $doctrine
     * @param OwnershipMetadataProviderInterface $ownershipMetadataProvider
     * @param AuthorizationCheckerInterface      $authorizationChecker
     * @param TokenAccessorInterface             $tokenAccessor
     * @param OwnerTreeProvider                  $ownerTreeProvider
     * @param AclVoter                           $aclVoter
     * @param BusinessUnitManager                $businessUnitManager
     */
    public function __construct(
        ManagerRegistry $doctrine,
        OwnershipMetadataProviderInterface $ownershipMetadataProvider,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenAccessorInterface $tokenAccessor,
        OwnerTreeProvider $ownerTreeProvider,
        AclVoter $aclVoter,
        BusinessUnitManager $businessUnitManager
    ) {
        $this->doctrine = $doctrine;
        $this->ownershipMetadataProvider = $ownershipMetadataProvider;
        $this->businessUnitManager = $businessUnitManager;
        $this->aclVoter = $aclVoter;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenAccessor = $tokenAccessor;
        $this->ownerTreeProvider = $ownerTreeProvider;
    }


    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof Owner) {
            throw new UnexpectedTypeException($constraint, Owner::class);
        }

        if (null === $value) {
            return;
        }

        $entityClass = ClassUtils::getClass($value);
        $em = $this->doctrine->getManagerForClass($entityClass);
        if (!$em instanceof EntityManagerInterface) {
            // the validation is required only for ORM entities
            return;
        }

        $ownershipMetadata = $this->ownershipMetadataProvider->getMetadata($entityClass);
        if (null === $ownershipMetadata || !$ownershipMetadata->hasOwner()) {
            // the validation is required only for ACL protected entities
            return;
        }

        if (!$this->validateOwner($ownershipMetadata, $em, $entityClass, $value)) {
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
     * @param OwnershipMetadataInterface $ownershipMetadata
     * @param EntityManagerInterface     $em
     * @param string                     $entityClass
     * @param object                     $entity
     *
     * @return bool
     */
    protected function validateOwner(
        OwnershipMetadataInterface $ownershipMetadata,
        EntityManagerInterface $em,
        $entityClass,
        $entity
    ) {
        $entityMetadata = $em->getClassMetadata($entityClass);
        $ownerFieldName = $ownershipMetadata->getOwnerFieldName();
        $owner = $entityMetadata->getFieldValue($entity, $ownerFieldName);
        if (null === $owner || !$this->isEntityOwnerChanged($em, $entity, $ownerFieldName, $owner)) {
            // skip validation for entities that do not assigned to any owner
            // or the assigned owner was not changed
            return true;
        }

        $accessLevel = $this->getGrantedAccessLevel($entityMetadata, $entityClass, $entity);
        if (null === $accessLevel) {
            // the access to change the entity is denied for the current logged in user
            return false;
        }

        return $this->isValidOwner($ownershipMetadata, $entityMetadata, $entity, $owner, $accessLevel);
    }

    /**
     * @param OwnershipMetadataInterface $ownershipMetadata
     * @param ClassMetadata              $entityMetadata
     * @param object                     $entity
     * @param object                     $owner
     * @param int                        $accessLevel
     *
     * @return bool
     */
    protected function isValidOwner(
        OwnershipMetadataInterface $ownershipMetadata,
        ClassMetadata $entityMetadata,
        $entity,
        $owner,
        $accessLevel
    ) {
        if (null === $owner->getId()) {
            return $this->isValidNewOwner($ownershipMetadata, $entityMetadata, $entity, $owner);
        }

        return $this->isValidExistingOwner($ownershipMetadata, $owner, $accessLevel);
    }

    /**
     * @return Organization
     */
    protected function getOrganization()
    {
        return $this->tokenAccessor->getOrganization();
    }

    /**
     * @param OwnershipMetadataInterface $ownershipMetadata
     * @param object                     $owner
     * @param integer                    $accessLevel
     *
     * @return bool
     */
    private function isValidExistingOwner(OwnershipMetadataInterface $ownershipMetadata, $owner, $accessLevel)
    {
        if ($ownershipMetadata->isUserOwned()) {
            return $this->businessUnitManager->canUserBeSetAsOwner(
                $this->tokenAccessor->getUser(),
                $owner,
                $accessLevel,
                $this->ownerTreeProvider,
                $this->getOrganization()
            );
        }
        if ($ownershipMetadata->isBusinessUnitOwned()) {
            return $this->businessUnitManager->canBusinessUnitBeSetAsOwner(
                $this->tokenAccessor->getUser(),
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

    /**
     * @param OwnershipMetadataInterface $ownershipMetadata
     * @param ClassMetadata              $entityMetadata
     * @param object                     $entity
     * @param object                     $owner
     *
     * @return bool
     */
    private function isValidNewOwner(
        OwnershipMetadataInterface $ownershipMetadata,
        ClassMetadata $entityMetadata,
        $entity,
        $owner
    ) {
        if ($ownershipMetadata->isOrganizationOwned()) {
            return true;
        }

        $organization = $entityMetadata->getFieldValue(
            $entity,
            $ownershipMetadata->getOrganizationFieldName()
        );

        return $this->getOwnerOrganization($owner) === $organization;
    }

    /**
     * @param ClassMetadata $entityMetadata
     * @param string        $entityClass
     * @param object        $entity
     *
     * @return int|null
     */
    private function getGrantedAccessLevel(ClassMetadata $entityMetadata, $entityClass, $entity)
    {
        $isExistinEntity = count($entityMetadata->getIdentifierValues($entity)) !== 0;
        if ($isExistinEntity) {
            $permission = 'ASSIGN';
            $object = $entity;
        } else {
            $permission = 'CREATE';
            $object = 'entity:' . $entityClass;
        }

        $observer = new OneShotIsGrantedObserver();
        $this->aclVoter->addOneShotIsGrantedObserver($observer);
        if ($this->authorizationChecker->isGranted($permission, $object)) {
            return $observer->getAccessLevel();
        }

        return null;
    }

    /**
     * @param User|BusinessUnit $owner
     *
     * @return Organization|null
     */
    private function getOwnerOrganization($owner)
    {
        return $owner->getOrganization();
    }

    /**
     * @param EntityManagerInterface $em
     * @param object                 $entity
     * @param string                 $ownerFieldName
     * @param object                 $owner
     *
     * @return bool
     */
    private function isEntityOwnerChanged(EntityManagerInterface $em, $entity, $ownerFieldName, $owner)
    {
        $originalEntityData = $em->getUnitOfWork()->getOriginalEntityData($entity);

        return
            !isset($originalEntityData[$ownerFieldName])
            || $originalEntityData[$ownerFieldName] !== $owner;
    }
}
