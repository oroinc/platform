<?php

namespace Oro\Bundle\OrganizationBundle\Validator\Constraints;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Acl\Domain\OneShotIsGrantedObserver;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension;
use Oro\Bundle\SecurityBundle\Acl\Extension\ObjectIdentityHelper;
use Oro\Bundle\SecurityBundle\Acl\Group\AclGroupProviderInterface;
use Oro\Bundle\SecurityBundle\Acl\Voter\AclVoter;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProviderInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * The base class for entity owner validators.
 */
abstract class AbstractOwnerValidator extends ConstraintValidator
{
    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var OwnershipMetadataProviderInterface */
    protected $ownershipMetadataProvider;

    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var OwnerTreeProviderInterface */
    protected $ownerTreeProvider;

    /** @var AclVoter */
    protected $aclVoter;

    /** @var AclGroupProviderInterface */
    protected $aclGroupProvider;

    /**
     * @param ManagerRegistry                    $doctrine
     * @param OwnershipMetadataProviderInterface $ownershipMetadataProvider
     * @param AuthorizationCheckerInterface      $authorizationChecker
     * @param TokenAccessorInterface             $tokenAccessor
     * @param OwnerTreeProviderInterface         $ownerTreeProvider
     * @param AclVoter                           $aclVoter
     * @param AclGroupProviderInterface          $aclGroupProvider
     */
    public function __construct(
        ManagerRegistry $doctrine,
        OwnershipMetadataProviderInterface $ownershipMetadataProvider,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenAccessorInterface $tokenAccessor,
        OwnerTreeProviderInterface $ownerTreeProvider,
        AclVoter $aclVoter,
        AclGroupProviderInterface $aclGroupProvider
    ) {
        $this->doctrine = $doctrine;
        $this->ownershipMetadataProvider = $ownershipMetadataProvider;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenAccessor = $tokenAccessor;
        $this->ownerTreeProvider = $ownerTreeProvider;
        $this->aclVoter = $aclVoter;
        $this->aclGroupProvider = $aclGroupProvider;
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
     * @param int                        $accessLevel
     *
     * @return bool
     */
    abstract protected function isValidExistingOwner(
        OwnershipMetadataInterface $ownershipMetadata,
        $owner,
        $accessLevel
    );

    /**
     * @param OwnershipMetadataInterface $ownershipMetadata
     * @param ClassMetadata              $entityMetadata
     * @param object                     $entity
     * @param object                     $owner
     *
     * @return bool
     */
    protected function isValidNewOwner(
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
    protected function getGrantedAccessLevel(ClassMetadata $entityMetadata, $entityClass, $entity)
    {
        $isExistingEntity = count($entityMetadata->getIdentifierValues($entity)) !== 0;
        $permission = $isExistingEntity ? 'ASSIGN' : 'CREATE';

        $group = $this->aclGroupProvider->getGroup();
        $object = ObjectIdentityHelper::encodeIdentityString(
            EntityAclExtension::NAME,
            $group ? ObjectIdentityHelper::buildType($entityClass, $group) : $entityClass
        );

        $observer = new OneShotIsGrantedObserver();
        $this->aclVoter->addOneShotIsGrantedObserver($observer);
        if ($this->authorizationChecker->isGranted($permission, $object)) {
            return $observer->getAccessLevel();
        }

        return null;
    }

    /**
     * @param object $owner
     *
     * @return Organization|null
     */
    protected function getOwnerOrganization($owner)
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
    protected function isEntityOwnerChanged(EntityManagerInterface $em, $entity, $ownerFieldName, $owner)
    {
        $originalEntityData = $em->getUnitOfWork()->getOriginalEntityData($entity);

        return
            !isset($originalEntityData[$ownerFieldName])
            || $originalEntityData[$ownerFieldName] !== $owner;
    }
}
