<?php

namespace Oro\Bundle\SecurityBundle\Acl\Extension;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Util\ClassUtils;

use Oro\Bundle\EntityBundle\Exception\InvalidEntityException;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdAccessor;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
use Oro\Bundle\SecurityBundle\Acl\Exception\InvalidAclMaskException;
use Oro\Bundle\SecurityBundle\Acl\Group\AclGroupProviderInterface;
use Oro\Bundle\SecurityBundle\Acl\Permission\PermissionManager;
use Oro\Bundle\SecurityBundle\Annotation\Acl as AclAnnotation;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\SecurityBundle\Entity\Permission;
use Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadataProvider;
use Oro\Bundle\SecurityBundle\Owner\EntityOwnerAccessor;
use Oro\Bundle\SecurityBundle\Owner\Metadata\MetadataProviderInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class EntityAclExtension extends AbstractAclExtension
{
    const NAME = 'entity';

    /** @var ObjectIdAccessor */
    protected $objectIdAccessor;

    /** @var EntityClassResolver */
    protected $entityClassResolver;

    /** @var MetadataProviderInterface */
    protected $metadataProvider;

    /** @var EntitySecurityMetadataProvider */
    protected $entityMetadataProvider;

    /** @var AccessLevelOwnershipDecisionMakerInterface */
    protected $decisionMaker;

    /** @var EntityOwnerAccessor */
    protected $entityOwnerAccessor;

    /** @var PermissionManager */
    protected $permissionManager;

    /** @var AclGroupProviderInterface */
    protected $groupProvider;

    /**
     * key = Permission
     * value = The identity of a permission mask builder
     *
     * @var int[]
     */
    protected $permissionToMaskBuilderIdentity;

    /** @var array */
    protected $maskBuilderIdentityToPermissions;

    /**
     * @param ObjectIdAccessor $objectIdAccessor
     * @param EntityClassResolver $entityClassResolver
     * @param EntitySecurityMetadataProvider $entityMetadataProvider
     * @param MetadataProviderInterface $metadataProvider
     * @param AccessLevelOwnershipDecisionMakerInterface $decisionMaker
     * @param PermissionManager $permissionManager
     * @param AclGroupProviderInterface $groupProvider
     */
    public function __construct(
        ObjectIdAccessor $objectIdAccessor,
        EntityClassResolver $entityClassResolver,
        EntitySecurityMetadataProvider $entityMetadataProvider,
        MetadataProviderInterface $metadataProvider,
        AccessLevelOwnershipDecisionMakerInterface $decisionMaker,
        PermissionManager $permissionManager,
        AclGroupProviderInterface $groupProvider
    ) {
        $this->objectIdAccessor       = $objectIdAccessor;
        $this->entityClassResolver    = $entityClassResolver;
        $this->entityMetadataProvider = $entityMetadataProvider;
        $this->metadataProvider       = $metadataProvider;
        $this->decisionMaker          = $decisionMaker;
        $this->permissionManager      = $permissionManager;
        $this->groupProvider          = $groupProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getMasks($permission)
    {
        $this->buildPermissionsMap();

        return parent::getMasks($permission);
    }

    /**
     * {@inheritdoc}
     */
    public function hasMasks($permission)
    {
        $this->buildPermissionsMap();

        return parent::hasMasks($permission);
    }

    /**
     * @param EntityOwnerAccessor $entityOwnerAccessor
     */
    public function setEntityOwnerAccessor(EntityOwnerAccessor $entityOwnerAccessor)
    {
        $this->entityOwnerAccessor = $entityOwnerAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessLevelNames($object, $permissionName = null)
    {
        if ($this->getObjectClassName($object) === ObjectIdentityFactory::ROOT_IDENTITY_TYPE) {
            /**
             * In community version root entity should not have GLOBAL(Organization) access level
             */
            return AccessLevel::getAccessLevelNames(
                AccessLevel::BASIC_LEVEL,
                AccessLevel::SYSTEM_LEVEL,
                [AccessLevel::GLOBAL_LEVEL]
            );
        } else {
            return $this->getMetadata($object)->getAccessLevelNames();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supports($type, $id)
    {
        if ($type === ObjectIdentityFactory::ROOT_IDENTITY_TYPE && $id === $this->getExtensionKey()) {
            return true;
        }

        $delim = strpos($type, '@');
        if ($delim !== false) {
            $type = ltrim(substr($type, $delim + 1), ' ');
        }

        if ($id === $this->getExtensionKey()) {
            $type = $this->entityClassResolver->getEntityClass(ClassUtils::getRealClass($type));
        } else {
            $type = ClassUtils::getRealClass($type);
        }

        return $this->entityClassResolver->isEntity($type);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensionKey()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function validateMask($mask, $object, $permission = null)
    {
        if (0 === $this->removeServiceBits($mask)) {
            // zero mask
            return;
        }

        $permissions = $permission === null
            ? $this->getPermissions($mask, true)
            : [$permission];

        foreach ($permissions as $permission) {
            $validMasks = $this->getValidMasks($permission, $object);
            if (($mask | $validMasks) === $validMasks) {
                $identity = $this->getIdentityForPermission($permission);
                foreach ($this->getPermissionsToIdentityMap() as $p => $i) {
                    if ($identity === $i) {
                        $this->validateMaskAccessLevel($p, $mask, $object);
                    }
                }

                return;
            }
        }

        throw $this->createInvalidAclMaskException($mask, $object);
    }

    /**
     * {@inheritdoc}
     */
    public function getObjectIdentity($val)
    {
        if (is_string($val)) {
            return $this->fromDescriptor($val);
        } elseif ($val instanceof AclAnnotation) {
            $class = $this->entityClassResolver->getEntityClass($val->getClass());
            $group = $val->getGroup();

            return new ObjectIdentity($val->getType(), !empty($group) ? $group . '@' . $class : $class);
        }

        return $this->fromDomainObject($val);
    }

    /**
     * {@inheritdoc}
     */
    public function getMaskBuilder($permission)
    {
        if (empty($permission)) {
            $permission = 'VIEW';
        }

        $identity = $this->getIdentityForPermission($permission);

        return new EntityMaskBuilder($identity, $this->getPermissionsForIdentity($identity));
    }

    /**
     * {@inheritdoc}
     */
    public function getAllMaskBuilders()
    {
        $result = [];
        foreach ($this->getPermissionsForIdentity() as $identity => $permissions) {
            $result[] = new EntityMaskBuilder($identity, $permissions);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getMaskPattern($mask)
    {
        $identity    = $this->getServiceBits($mask);
        $maskBuilder = new EntityMaskBuilder($identity, $this->getPermissionsForIdentity($identity));

        return $maskBuilder->getPatternFor($mask);
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function adaptRootMask($rootMask, $object)
    {
        $permissions = $this->getPermissions($rootMask, true);
        if (!empty($permissions)) {
            $metadata = $this->getMetadata($object);
            $identity = $this->getServiceBits($rootMask);
            foreach ($permissions as $permission) {
                $permissionMask = $this->getMaskBuilderConst($identity, 'GROUP_' . $permission);
                $mask           = $rootMask & $permissionMask;
                $accessLevel    = $this->getAccessLevel($mask);
                if (!$metadata->hasOwner()) {
                    if ($identity === $this->getIdentityForPermission('ASSIGN')
                        && ($permission === 'ASSIGN' || $permission === 'SHARE')
                    ) {
                        $rootMask &= ~$this->removeServiceBits($mask);
                    } elseif ($accessLevel < AccessLevel::SYSTEM_LEVEL) {
                        $rootMask &= ~$this->removeServiceBits($mask);
                        $rootMask |= $this->getMaskBuilderConst($identity, 'MASK_' . $permission . '_SYSTEM');
                    }
                } elseif ($metadata->isGlobalLevelOwned()) {
                    if ($accessLevel < AccessLevel::GLOBAL_LEVEL) {
                        $rootMask &= ~$this->removeServiceBits($mask);
                        $rootMask |= $this->getMaskBuilderConst($identity, 'MASK_' . $permission . '_GLOBAL');
                    }
                } elseif ($metadata->isLocalLevelOwned()) {
                    if ($accessLevel < AccessLevel::LOCAL_LEVEL) {
                        $rootMask &= ~$this->removeServiceBits($mask);
                        $rootMask |= $this->getMaskBuilderConst($identity, 'MASK_' . $permission . '_LOCAL');
                    }
                }
            }
        }

        return $rootMask;
    }

    /**
     * {@inheritdoc}
     */
    public function getServiceBits($mask)
    {
        return $mask & EntityMaskBuilder::SERVICE_BITS;
    }

    /**
     * {@inheritdoc}
     */
    public function removeServiceBits($mask)
    {
        return $mask & EntityMaskBuilder::REMOVE_SERVICE_BITS;
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessLevel($mask, $permission = null, $object = null)
    {
        if (0 === $this->removeServiceBits($mask)) {
            return AccessLevel::NONE_LEVEL;
        }

        $identity = $this->getServiceBits($mask);
        if ($permission !== null) {
            $permissionMask = $this->getMaskBuilderConst($identity, 'GROUP_' . $permission);
            $mask           = $mask & $permissionMask;
        }

        $mask = $this->removeServiceBits($mask);

        $result = AccessLevel::NONE_LEVEL;
        foreach (AccessLevel::$allAccessLevelNames as $accessLevel) {
            if (0 !== ($mask & $this->getMaskBuilderConst($identity, 'GROUP_' . $accessLevel))) {
                $result = AccessLevel::getConst($accessLevel . '_LEVEL');
            }
        }

        return $this->metadataProvider->getMaxAccessLevel($result, $this->getObjectClassName($object));
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissions($mask = null, $setOnly = false, $byCurrentGroup = false)
    {
        $map = $this->getPermissionsToIdentityMap($byCurrentGroup);

        if ($mask === null) {
            return array_keys($map);
        }

        $result = [];
        if (!$setOnly) {
            $identity = $this->getServiceBits($mask);
            foreach ($map as $permission => $id) {
                if ($id === $identity) {
                    $result[] = $permission;
                }
            }
        } elseif (0 !== $this->removeServiceBits($mask)) {
            $identity = $this->getServiceBits($mask);
            $mask = $this->removeServiceBits($mask);
            foreach ($map as $permission => $id) {
                if ($id === $identity) {
                    if (0 !== ($mask & $this->getMaskBuilderConst($identity, 'GROUP_' . $permission))) {
                        $result[] = $permission;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllowedPermissions(ObjectIdentity $oid, $fieldName = null)
    {
        if ($oid->getType() === ObjectIdentityFactory::ROOT_IDENTITY_TYPE) {
            $result = array_keys($this->getPermissionsToIdentityMap());
        } else {
            $config = $this->entityMetadataProvider->getMetadata($oid->getType());
            $result = $config->getPermissions();
            if (empty($result)) {
                $result = array_keys($this->getPermissionsToIdentityMap());
            }

            $metadata = $this->getMetadata($oid);
            if (!$metadata->hasOwner()) {
                $result = array_diff($result, ['ASSIGN', 'SHARE']);
            }
        }

        $allowed = $this->getPermissionsForType($oid->getType());

        return array_values(array_intersect($result, $allowed));
    }

    /**
     * @param string $type
     * @return array
     */
    protected function getPermissionsForType($type)
    {
        $group = $this->groupProvider->getGroup();

        if ($type === ObjectIdentityFactory::ROOT_IDENTITY_TYPE) {
            $permissions = $this->permissionManager->getPermissionsForGroup($group);
        } else {
            $permissions = $this->permissionManager->getPermissionsForEntity($type, $group);
        }

        return array_map(
            function (Permission $permission) {
                return $permission->getName();
            },
            $permissions
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getClasses()
    {
        return $this->entityMetadataProvider->getEntities();
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * {@inheritdoc}
     */
    public function decideIsGranting($triggeredMask, $object, TokenInterface $securityToken)
    {
        // check whether we check permissions for a domain object
        if ($object === null || !is_object($object) || $object instanceof ObjectIdentityInterface) {
            return true;
        }

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
     * @param int   $accessLevel Current object access level
     * @param mixed $object      Object for test
     *
     * @return int
     *
     * @deprecated since 1.8, use MetadataProviderInterface::getMaxAccessLevel instead
     */
    protected function fixMaxAccessLevel($accessLevel, $object)
    {
        return $this->metadataProvider->getMaxAccessLevel($accessLevel, $this->getObjectClassName($object));
    }

    /**
     * Constructs an ObjectIdentity for the given domain object
     *
     * @param string $descriptor
     *
     * @return ObjectIdentity
     * @throws \InvalidArgumentException
     */
    protected function fromDescriptor($descriptor)
    {
        $type = $id = $group = null;
        $this->parseDescriptor($descriptor, $type, $id, $group);

        $type = $this->entityClassResolver->getEntityClass(ClassUtils::getRealClass($type));

        if ($id === $this->getExtensionKey()) {
            return new ObjectIdentity($id, !empty($group) ? $group . '@' . $type : $type);
        }

        throw new \InvalidArgumentException(
            sprintf('Unsupported object identity descriptor: %s.', $descriptor)
        );
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
     * Checks that the given mask represents only one access level
     *
     * @param string $permission
     * @param int    $mask
     * @param mixed  $object
     *
     * @throws InvalidAclMaskException
     */
    protected function validateMaskAccessLevel($permission, $mask, $object)
    {
        $identity = $this->getIdentityForPermission($permission);
        if (0 !== ($mask & $this->getMaskBuilderConst($identity, 'GROUP_' . $permission))) {
            $maskAccessLevels = [];
            $clearedMask = $this->removeServiceBits($mask);

            foreach (AccessLevel::$allAccessLevelNames as $accessLevel) {
                $levelMask = $this->removeServiceBits(
                    $this->getMaskBuilderConst($identity, sprintf('MASK_%s_%s', $permission, $accessLevel))
                );

                if (0 !== ($clearedMask & $levelMask)) {
                    $maskAccessLevels[] = $accessLevel;
                }
            }
            if (count($maskAccessLevels) > 1) {
                $msg = sprintf(
                    'The %s mask must be in one access level only, but it is in %s access levels.',
                    $permission,
                    implode(', ', $maskAccessLevels)
                );
                throw $this->createInvalidAclMaskException($mask, $object, $msg);
            }
        }
    }

    /**
     * Gets all valid bitmasks for the given object
     *
     * @param string $permission
     * @param mixed  $object
     *
     * @return int
     */
    protected function getValidMasks($permission, $object)
    {
        $identity = $this->getIdentityForPermission($permission);

        if ($object instanceof ObjectIdentity && $object->getType() === ObjectIdentityFactory::ROOT_IDENTITY_TYPE) {
            return
                $this->getMaskBuilderConst($identity, 'GROUP_SYSTEM')
                | $this->getMaskBuilderConst($identity, 'GROUP_GLOBAL')
                | $this->getMaskBuilderConst($identity, 'GROUP_DEEP')
                | $this->getMaskBuilderConst($identity, 'GROUP_LOCAL')
                | $this->getMaskBuilderConst($identity, 'GROUP_BASIC');
        }

        $metadata = $this->getMetadata($object);
        if (!$metadata->hasOwner()) {
            $maskBuilder = $this->getMaskBuilder($permission);
            $maskBuilder->reset()->add($maskBuilder->getMask('GROUP_SYSTEM'));

            if ($maskBuilder->hasMask('MASK_ASSIGN_SYSTEM')) {
                $maskBuilder->remove('ASSIGN_SYSTEM');
            }

            if ($maskBuilder->hasMask('MASK_SHARE_SYSTEM')) {
                $maskBuilder->remove('SHARE_SYSTEM');
            }

            return $maskBuilder->get();
        }

        if ($metadata->isGlobalLevelOwned()) {
            return
                $this->getMaskBuilderConst($identity, 'GROUP_SYSTEM')
                | $this->getMaskBuilderConst($identity, 'GROUP_GLOBAL');
        } elseif ($metadata->isLocalLevelOwned()) {
            return
                $this->getMaskBuilderConst($identity, 'GROUP_SYSTEM')
                | $this->getMaskBuilderConst($identity, 'GROUP_GLOBAL')
                | $this->getMaskBuilderConst($identity, 'GROUP_DEEP')
                | $this->getMaskBuilderConst($identity, 'GROUP_LOCAL');
        } elseif ($metadata->isBasicLevelOwned()) {
            return
                $this->getMaskBuilderConst($identity, 'GROUP_SYSTEM')
                | $this->getMaskBuilderConst($identity, 'GROUP_GLOBAL')
                | $this->getMaskBuilderConst($identity, 'GROUP_DEEP')
                | $this->getMaskBuilderConst($identity, 'GROUP_LOCAL')
                | $this->getMaskBuilderConst($identity, 'GROUP_BASIC');
        }

        return $this->getIdentityForPermission($permission);
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
     * Gets class name for given object
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
     * Gets the constant value defined in the given permission mask builder
     *
     * @param int    $maskBuilderIdentity The permission mask builder identity
     * @param string $constName
     *
     * @return int
     */
    protected function getMaskBuilderConst($maskBuilderIdentity, $constName)
    {
        $maskBuilder = new EntityMaskBuilder(
            $maskBuilderIdentity,
            $this->getPermissionsForIdentity($maskBuilderIdentity)
        );

        return $maskBuilder->getMask($constName);
    }

    /**
     * Check organization. If user try to access entity what was created in organization this user do not have access -
     *  deny access. We should check organization for all the entities what have ownership
     *  (USER, BUSINESS_UNIT, ORGANIZATION ownership types)
     *
     * @param mixed $object
     * @param OrganizationContextTokenInterface $securityToken
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

    protected function loadPermissions()
    {
        if (null !== $this->permissionToMaskBuilderIdentity && null !== $this->maskBuilderIdentityToPermissions) {
            return;
        }

        $allPermissions = $this->permissionManager->getPermissionsMap();
        $permissionChunks = array_chunk(array_keys($allPermissions), EntityMaskBuilder::MAX_PERMISSIONS_IN_MASK);

        foreach ($permissionChunks as $permissions) {
            foreach ($permissions as $permission) {
                $pk = $allPermissions[$permission];

                $identity = $this->getIdentityForPrimaryKey($pk);
                $number = $this->getPermissionNumber($pk);

                $this->permissionToMaskBuilderIdentity[$permission] = $identity;
                $this->maskBuilderIdentityToPermissions[$identity][$number] = $permission;
            }
        }
    }

    protected function buildPermissionsMap()
    {
        if ($this->map !== null) {
            return;
        }

        $this->map = [];

        $permissions = array_keys($this->getPermissionsToIdentityMap());
        foreach ($permissions as $permission) {
            $maskBuilder = $this->getMaskBuilder($permission);
            $masks = [];

            foreach (AccessLevel::$allAccessLevelNames as $accessLevel) {
                $masks[] = $maskBuilder->getMask(sprintf('MASK_%s_%s', $permission, $accessLevel));
            }

            $this->map[$permission] = $masks;
        }
    }

    /**
     * @param bool $byCurrentGroup
     * @return array|int[]
     */
    protected function getPermissionsToIdentityMap($byCurrentGroup = false)
    {
        $this->loadPermissions();
        $map = $this->permissionToMaskBuilderIdentity;

        if ($byCurrentGroup) {
            $permissions = $this->permissionManager->getPermissionsMap($this->groupProvider->getGroup());

            $map = array_intersect_key($map, $permissions);
        }

        return $map;
    }

    /**
     * @param int $pk
     * @return int
     */
    protected function getIdentityForPrimaryKey($pk)
    {
        $identity = (int) (($pk - 1) / EntityMaskBuilder::MAX_PERMISSIONS_IN_MASK);

        return $identity << (count(AccessLevel::$allAccessLevelNames) * EntityMaskBuilder::MAX_PERMISSIONS_IN_MASK);
    }

    /**
     * @param string $permission
     * @return int
     */
    protected function getIdentityForPermission($permission)
    {
        $identities = $this->getPermissionsToIdentityMap();

        return $identities[$permission];
    }

    /**
     * @param int $pk
     * @return int
     */
    protected function getPermissionNumber($pk)
    {
        $map = range(0, EntityMaskBuilder::MAX_PERMISSIONS_IN_MASK - 1);
        array_unshift($map, array_pop($map));

        return $map[$pk % EntityMaskBuilder::MAX_PERMISSIONS_IN_MASK];
    }

    /**
     * @param int|null $identity
     * @return array
     */
    protected function getPermissionsForIdentity($identity = null)
    {
        $this->loadPermissions();

        return $identity === null
            ? $this->maskBuilderIdentityToPermissions
            : $this->maskBuilderIdentityToPermissions[$identity];
    }
}
