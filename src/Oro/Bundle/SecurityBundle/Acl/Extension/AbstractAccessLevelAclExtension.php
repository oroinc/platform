<?php

namespace Oro\Bundle\SecurityBundle\Acl\Extension;

use Oro\Bundle\EntityBundle\Exception\InvalidEntityException;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Domain\DomainObjectReference;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdAccessor;
use Oro\Bundle\SecurityBundle\Acl\Exception\InvalidAclMaskException;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface;
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
    protected const ACCESS_LEVELS = [
        'SYSTEM' => AccessLevel::SYSTEM_LEVEL,
        'GLOBAL' => AccessLevel::GLOBAL_LEVEL,
        'DEEP'   => AccessLevel::DEEP_LEVEL,
        'LOCAL'  => AccessLevel::LOCAL_LEVEL,
        'BASIC'  => AccessLevel::BASIC_LEVEL
    ];

    protected ObjectIdAccessor $objectIdAccessor;
    protected OwnershipMetadataProviderInterface $metadataProvider;
    protected EntityOwnerAccessor $entityOwnerAccessor;
    protected AccessLevelOwnershipDecisionMakerInterface $decisionMaker;

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
     * {@inheritDoc}
     */
    public function getAccessLevelNames($object, $permissionName = null)
    {
        return $this->getMetadata($object)->getAccessLevelNames();
    }

    protected function isAccessGranted(int $triggeredMask, mixed $object, TokenInterface $securityToken): bool
    {
        $organization = null;
        $accessLevel = $this->getAccessLevel($triggeredMask);
        if ($securityToken instanceof OrganizationAwareTokenInterface) {
            if ($this->isAccessDeniedByOrganizationContext(
                $object,
                $securityToken,
                $accessLevel,
                $this->getPermissions($triggeredMask, true)
            )) {
                return false;
            }
            $organization = $securityToken->getOrganization();
        }

        return $this->isAccessGrantedByAccessLevel(
            $accessLevel,
            $object,
            $securityToken->getUser(),
            $organization
        );
    }

    protected function isAccessGrantedByAccessLevel(
        int $accessLevel,
        mixed $object,
        object|string|null $user,
        ?object $organization
    ): bool {
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
     * Checks whether an object is supported for check permissions.
     */
    protected function isSupportedObject(mixed $object): bool
    {
        return
            \is_object($object)
            && !$object instanceof ObjectIdentity;
    }

    /**
     * Gets metadata for the given object.
     */
    protected function getMetadata(mixed $object): OwnershipMetadataInterface
    {
        return $this->metadataProvider->getMetadata($this->getObjectClassName($object));
    }

    /**
     * Gets class name for the given object.
     */
    protected function getObjectClassName($object)
    {
        if ($object instanceof ObjectIdentityInterface) {
            $className = $object->getType();
        } elseif (\is_string($object)) {
            $className = $id = $group = null;
            $this->parseDescriptor($object, $className, $id, $group);
        } else {
            $className = ClassUtils::getRealClass($object);
        }

        return $className;
    }

    /**
     * Gets id for the given domain object.
     *
     * @throws InvalidDomainObjectException
     */
    protected function getObjectId(object $domainObject): int|string|null
    {
        return $domainObject instanceof DomainObjectReference
            ? $domainObject->getIdentifier()
            : $this->objectIdAccessor->getId($domainObject);
    }

    /**
     * Constructs an ObjectIdentity for the given domain object.
     *
     * @throws InvalidDomainObjectException
     */
    protected function fromDomainObject(object $domainObject): ObjectIdentity
    {
        if (!\is_object($domainObject)) {
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
     *  (USER, BUSINESS_UNIT, ORGANIZATION ownership types).
     */
    protected function isAccessDeniedByOrganizationContext(
        object $object,
        OrganizationAwareTokenInterface $securityToken,
        int $accessLevel,
        array $permissions
    ): bool {
        $objectOrganizationId = $this->getOrganizationId($object);

        // check entity organization with current organization
        return
            null !== $objectOrganizationId
            && $objectOrganizationId !== $securityToken->getOrganization()->getId();
    }

    protected function getOrganizationId(object $object): int|string|null
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

        return $organization?->getId();
    }

    /**
     * Creates an instance of InvalidAclMaskException indicates that a bitmask
     * has invalid access level related permission.
     */
    protected function createInvalidAccessLevelAclMaskException(
        int $mask,
        mixed $object,
        string $permission,
        array $maskAccessLevels
    ): InvalidAclMaskException {
        return $this->createInvalidAclMaskException(
            $mask,
            $object,
            sprintf(
                'The %s permission must be in one access level only, but it is in %s access levels.',
                $permission,
                implode(', ', $maskAccessLevels)
            )
        );
    }
}
