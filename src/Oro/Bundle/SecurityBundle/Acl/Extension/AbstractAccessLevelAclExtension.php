<?php

namespace Oro\Bundle\SecurityBundle\Acl\Extension;

use Oro\Bundle\EntityBundle\Exception\InvalidEntityException;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Domain\DomainObjectReference;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdAccessor;
use Oro\Bundle\SecurityBundle\Acl\Exception\InvalidAclMaskException;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\SecurityBundle\Owner\EntityOwnerAccessor;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;
use Symfony\Component\Security\Acl\Util\ClassUtils;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * The base class for ACL extensions that check permissions based on access levels.
 */
abstract class AbstractAccessLevelAclExtension extends AbstractAclExtension
{
    /** All access levels from the most to the least permissive level */
    const ACCESS_LEVELS = [
        'SYSTEM' => AccessLevel::SYSTEM_LEVEL,
        'GLOBAL' => AccessLevel::GLOBAL_LEVEL,
        'DEEP'   => AccessLevel::DEEP_LEVEL,
        'LOCAL'  => AccessLevel::LOCAL_LEVEL,
        'BASIC'  => AccessLevel::BASIC_LEVEL
    ];

    /** @var ObjectIdAccessor */
    protected $objectIdAccessor;

    /** @var OwnershipMetadataProviderInterface */
    protected $metadataProvider;

    /** @var EntityOwnerAccessor */
    protected $entityOwnerAccessor;

    /** @var AccessLevelOwnershipDecisionMakerInterface */
    protected $decisionMaker;

    /**
     * @param ObjectIdAccessor                           $objectIdAccessor
     * @param OwnershipMetadataProviderInterface         $metadataProvider
     * @param EntityOwnerAccessor                        $entityOwnerAccessor
     * @param AccessLevelOwnershipDecisionMakerInterface $decisionMaker
     */
    public function __construct(
        ObjectIdAccessor $objectIdAccessor,
        OwnershipMetadataProviderInterface $metadataProvider,
        EntityOwnerAccessor $entityOwnerAccessor,
        AccessLevelOwnershipDecisionMakerInterface $decisionMaker
    ) {
        $this->objectIdAccessor = $objectIdAccessor;
        $this->metadataProvider = $metadataProvider;
        $this->entityOwnerAccessor = $entityOwnerAccessor;
        $this->decisionMaker = $decisionMaker;
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessLevelNames($object, $permissionName = null)
    {
        return $this->getMetadata($object)->getAccessLevelNames();
    }

    /**
     * @param int            $triggeredMask
     * @param mixed          $object
     * @param TokenInterface $securityToken
     *
     * @return bool
     */
    protected function isAccessGranted($triggeredMask, $object, TokenInterface $securityToken)
    {
        $organization = null;
        $accessLevel = $this->getAccessLevel($triggeredMask);
        if ($securityToken instanceof OrganizationContextTokenInterface) {
            if ($this->isAccessDeniedByOrganizationContext($object, $securityToken, $accessLevel)) {
                return false;
            }
            $organization = $securityToken->getOrganizationContext();
        }

        return $this->isAccessGrantedByAccessLevel(
            $accessLevel,
            $object,
            $securityToken->getUser(),
            $organization
        );
    }

    /**
     * @param int    $accessLevel
     * @param mixed  $object
     * @param object $user
     * @param object $organization
     *
     * @return bool
     */
    protected function isAccessGrantedByAccessLevel($accessLevel, $object, $user, $organization)
    {
        if (AccessLevel::SYSTEM_LEVEL === $accessLevel) {
            return true;
        }

        $metadata = $this->getMetadata($object);
        if (!$metadata->hasOwner()) {
            return true;
        }

        $result = false;
        if (AccessLevel::BASIC_LEVEL === $accessLevel) {
            $result = $this->decisionMaker->isAssociatedWithUser(
                $user,
                $object,
                $organization
            );
        } else {
            if ($metadata->isUserOwned()) {
                $result = $this->decisionMaker->isAssociatedWithUser(
                    $user,
                    $object,
                    $organization
                );
            }
            if (!$result) {
                if (AccessLevel::LOCAL_LEVEL === $accessLevel) {
                    $result = $this->decisionMaker->isAssociatedWithBusinessUnit(
                        $user,
                        $object,
                        false,
                        $organization
                    );
                } elseif (AccessLevel::DEEP_LEVEL === $accessLevel) {
                    $result = $this->decisionMaker->isAssociatedWithBusinessUnit(
                        $user,
                        $object,
                        true,
                        $organization
                    );
                } elseif (AccessLevel::GLOBAL_LEVEL === $accessLevel) {
                    $result = $this->decisionMaker->isAssociatedWithOrganization(
                        $user,
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
            is_object($object)
            && !$object instanceof ObjectIdentity;
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
     * @param mixed $object
     *
     * @return string|null
     */
    protected function getObjectClassName($object)
    {
        if ($object instanceof ObjectIdentityInterface) {
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
     * Gets id for the given domain object
     *
     * @param  object $domainObject
     *
     * @return int|string
     * @throws InvalidDomainObjectException
     */
    protected function getObjectId($domainObject)
    {
        return $domainObject instanceof DomainObjectReference
            ? $domainObject->getIdentifier()
            : $this->objectIdAccessor->getId($domainObject);
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
     * @param string                            $accessLevel
     *
     * @return bool
     */
    protected function isAccessDeniedByOrganizationContext(
        $object,
        OrganizationContextTokenInterface $securityToken,
        $accessLevel
    ) {
        $objectOrganizationId = $this->getOrganizationId($object);

        // check entity organization with current organization
        return
            null !== $objectOrganizationId
            && $objectOrganizationId !== $securityToken->getOrganizationContext()->getId();
    }

    /**
     * @param object $object
     *
     * @return int|null
     */
    protected function getOrganizationId($object)
    {
        if ($object instanceof DomainObjectReference) {
            return $object->getOrganizationId();
        }

        $organization = null;
        try {
            $organization = $this->entityOwnerAccessor->getOrganization($object);
        } catch (InvalidEntityException $e) {
            // in case if entity has no organization field (none ownership type)
        }

        return null !== $organization
            ? $organization->getId()
            : null;
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
