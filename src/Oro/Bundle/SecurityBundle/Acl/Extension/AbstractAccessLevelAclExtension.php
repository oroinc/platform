<?php

namespace Oro\Bundle\SecurityBundle\Acl\Extension;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;
use Symfony\Component\Security\Acl\Util\ClassUtils;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\EntityBundle\Exception\InvalidEntityException;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdAccessor;
use Oro\Bundle\SecurityBundle\Acl\Exception\InvalidAclMaskException;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\MetadataProviderInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataInterface;
use Oro\Bundle\SecurityBundle\Owner\EntityOwnerAccessor;

abstract class AbstractAccessLevelAclExtension extends AbstractAclExtension
{
    /** @var ObjectIdAccessor */
    protected $objectIdAccessor;

    /** @var MetadataProviderInterface */
    protected $metadataProvider;

    /** @var EntityOwnerAccessor */
    protected $entityOwnerAccessor;

    /** @var AccessLevelOwnershipDecisionMakerInterface */
    protected $decisionMaker;

    /**
     * @param ObjectIdAccessor                           $objectIdAccessor
     * @param MetadataProviderInterface                  $metadataProvider
     * @param EntityOwnerAccessor                        $entityOwnerAccessor
     * @param AccessLevelOwnershipDecisionMakerInterface $decisionMaker
     */
    public function __construct(
        ObjectIdAccessor $objectIdAccessor,
        MetadataProviderInterface $metadataProvider,
        EntityOwnerAccessor $entityOwnerAccessor,
        AccessLevelOwnershipDecisionMakerInterface $decisionMaker
    ) {
        $this->objectIdAccessor = $objectIdAccessor;
        $this->metadataProvider = $metadataProvider;
        $this->entityOwnerAccessor = $entityOwnerAccessor;
        $this->decisionMaker = $decisionMaker;
    }

    /**
     * @param int            $triggeredMask
     * @param mixed          $object
     * @param TokenInterface $securityToken
     *
     * @return bool
     */
    public function isAccessGranted($triggeredMask, $object, TokenInterface $securityToken)
    {
        $organization = null;
        if ($securityToken instanceof OrganizationContextTokenInterface) {
            if ($this->isAccessDeniedByOrganizationContext($object, $securityToken)) {
                return false;
            }
            $organization = $securityToken->getOrganizationContext();
        }

        $accessLevel = $this->getAccessLevel($triggeredMask);
        if (AccessLevel::SYSTEM_LEVEL === $accessLevel) {
            return true;
        }

        $metadata = $this->getMetadata($object);
        if (!$metadata->hasOwner()) {
            return true;
        }

        $result = false;
        if (AccessLevel::BASIC_LEVEL === $accessLevel) {
            $result = $this->decisionMaker->isAssociatedWithBasicLevelEntity(
                $securityToken->getUser(),
                $object,
                $organization
            );
        } else {
            if ($metadata->isBasicLevelOwned()) {
                $result = $this->decisionMaker->isAssociatedWithBasicLevelEntity(
                    $securityToken->getUser(),
                    $object,
                    $organization
                );
            }
            if (!$result) {
                if (AccessLevel::LOCAL_LEVEL === $accessLevel) {
                    $result = $this->decisionMaker->isAssociatedWithLocalLevelEntity(
                        $securityToken->getUser(),
                        $object,
                        false,
                        $organization
                    );
                } elseif (AccessLevel::DEEP_LEVEL === $accessLevel) {
                    $result = $this->decisionMaker->isAssociatedWithLocalLevelEntity(
                        $securityToken->getUser(),
                        $object,
                        true,
                        $organization
                    );
                } elseif (AccessLevel::GLOBAL_LEVEL === $accessLevel) {
                    $result = $this->decisionMaker->isAssociatedWithGlobalLevelEntity(
                        $securityToken->getUser(),
                        $object,
                        $organization
                    );
                }
            }
        }

        return $result;
    }

    /**
     * Checks whether an object is supported for check permissions
     *
     * @param mixed $object
     *
     * @return bool
     */
    protected function isSupportedObject($object)
    {
        return
            null !== $object
            && is_object($object)
            && !$object instanceof ObjectIdentityInterface;
    }

    /**
     * Gets metadata for the given object
     *
     * @param mixed $object
     *
     * @return OwnershipMetadataInterface
     */
    protected function getMetadata($object)
    {
        return $this->metadataProvider->getMetadata($this->getObjectClassName($object));
    }

    /**
     * Gets class name for the given object
     *
     * @param $object
     *
     * @return string
     */
    protected function getObjectClassName($object)
    {
        if ($object instanceof ObjectIdentity) {
            $className = $object->getType();
        } elseif (is_string($object)) {
            $className = $id = $group = null;
            $this->parseDescriptor($object, $className, $id, $group);
        } else {
            $className = ClassUtils::getRealClass($object);
        }

        return $className;
    }

    /**
     * Constructs an ObjectIdentity for the given domain object
     *
     * @param object $domainObject
     *
     * @return ObjectIdentity
     * @throws InvalidDomainObjectException
     */
    protected function fromDomainObject($domainObject)
    {
        if (!is_object($domainObject)) {
            throw new InvalidDomainObjectException('$domainObject must be an object.');
        }

        try {
            return new ObjectIdentity(
                $this->objectIdAccessor->getId($domainObject),
                ClassUtils::getRealClass($domainObject)
            );
        } catch (\InvalidArgumentException $invalid) {
            throw new InvalidDomainObjectException($invalid->getMessage(), 0, $invalid);
        }
    }

    /**
     * Checks whether user does not have access to an entity from another organization.
     * We should check organization for all the entities what have ownership
     *  (USER, BUSINESS_UNIT, ORGANIZATION ownership types)
     *
     * @param object                            $object
     * @param OrganizationContextTokenInterface $securityToken
     *
     * @return bool
     */
    protected function isAccessDeniedByOrganizationContext($object, OrganizationContextTokenInterface $securityToken)
    {
        try {
            // try to get entity organization value
            $objectOrganization = $this->entityOwnerAccessor->getOrganization($object);

            // check entity organization with current organization
            if ($objectOrganization
                && $objectOrganization->getId() !== $securityToken->getOrganizationContext()->getId()
            ) {
                return true;
            }
        } catch (InvalidEntityException $e) {
            // in case if entity has no organization field (none ownership type)
        }

        return false;
    }

    /**
     * Creates an instance of InvalidAclMaskException indicates that a bitmask
     * has invalid access level related permission
     *
     * @param int      $mask
     * @param mixed    $object
     * @param string   $permission
     * @param string[] $maskAccessLevels
     *
     * @return InvalidAclMaskException
     */
    protected function createInvalidAccessLevelAclMaskException($mask, $object, $permission, array $maskAccessLevels)
    {
        $msg = sprintf(
            'The %s permission must be in one access level only, but it is in %s access levels.',
            $permission,
            implode(', ', $maskAccessLevels)
        );

        return $this->createInvalidAclMaskException($mask, $object, $msg);
    }
}
