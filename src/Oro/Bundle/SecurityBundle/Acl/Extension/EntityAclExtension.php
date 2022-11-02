<?php

namespace Oro\Bundle\SecurityBundle\Acl\Extension;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdAccessor;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
use Oro\Bundle\SecurityBundle\Acl\Exception\InvalidAclMaskException;
use Oro\Bundle\SecurityBundle\Acl\Group\AclGroupProviderInterface;
use Oro\Bundle\SecurityBundle\Acl\Permission\PermissionManager;
use Oro\Bundle\SecurityBundle\Annotation\Acl as AclAnnotation;
use Oro\Bundle\SecurityBundle\Entity\Permission;
use Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadataProvider;
use Oro\Bundle\SecurityBundle\Owner\EntityOwnerAccessor;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Util\ClassUtils;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * The ACL extension that works with Doctrine entities.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class EntityAclExtension extends AbstractAccessLevelAclExtension
{
    public const NAME = 'entity';

    /** @var EntityClassResolver */
    protected $entityClassResolver;

    /** @var EntitySecurityMetadataProvider */
    protected $entityMetadataProvider;

    /** @var PermissionManager */
    protected $permissionManager;

    /** @var AclGroupProviderInterface */
    protected $groupProvider;

    /** @var FieldAclExtension */
    protected $fieldAclExtension;

    /** @var int[] [permission => the identity of a permission mask builder, ...] */
    private $permissionsToIdentity;

    /** @var array [the identity of a permission mask builder => [permission, ...], ...] */
    private $identityToPermissions;

    /** @var EntityMaskBuilder[] [the identity of a permission mask builder => EntityMaskBuilder, ...] */
    private $builders = [];

    /** @var array [mask => access level, ...] */
    private $accessLevelForMask = [];

    /** @var array [group => permissions, ...] */
    private $permissionsForGroup = [];

    /** @var array [mask => group mask, ...] */
    private $permissionGroupMasks = [];

    public function __construct(
        ObjectIdAccessor $objectIdAccessor,
        EntityClassResolver $entityClassResolver,
        EntitySecurityMetadataProvider $entityMetadataProvider,
        OwnershipMetadataProviderInterface $metadataProvider,
        EntityOwnerAccessor $entityOwnerAccessor,
        AccessLevelOwnershipDecisionMakerInterface $decisionMaker,
        PermissionManager $permissionManager,
        AclGroupProviderInterface $groupProvider,
        FieldAclExtension $fieldAclExtension
    ) {
        parent::__construct($objectIdAccessor, $metadataProvider, $entityOwnerAccessor, $decisionMaker);
        $this->entityClassResolver = $entityClassResolver;
        $this->entityMetadataProvider = $entityMetadataProvider;
        $this->permissionManager = $permissionManager;
        $this->groupProvider = $groupProvider;
        $this->fieldAclExtension = $fieldAclExtension;
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
    public function getFieldExtension()
    {
        return $this->fieldAclExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($type, $id)
    {
        if (ObjectIdentityFactory::ROOT_IDENTITY_TYPE === $type) {
            return $this->getExtensionKey() === $id;
        }

        $type = ClassUtils::getRealClass(
            ObjectIdentityHelper::removeGroupName(ObjectIdentityHelper::removeFieldName($type))
        );
        if ($this->getExtensionKey() === $id) {
            $type = $this->entityClassResolver->getEntityClass($type);
        }

        return $this->entityMetadataProvider->isProtectedEntity($type);
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
     * {@inheritdoc}
     */
    public function getPermissionGroupMask($mask)
    {
        if (\array_key_exists($mask, $this->permissionGroupMasks)) {
            return $this->permissionGroupMasks[$mask];
        }

        $result = null;
        $permissions = $this->getPermissions($mask, true);
        foreach ($permissions as $permission) {
            $maskBuilder = $this->getEntityMaskBuilder($this->getIdentityForPermission($permission));
            if ($maskBuilder->hasMaskForGroup($permission)) {
                $result = $maskBuilder->getMaskForGroup($permission);
                break;
            }
        }
        $this->permissionGroupMasks[$mask] = $result;

        return $result;
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
        if (\is_string($val)) {
            return $this->fromDescriptor($val);
        }
        if ($val instanceof AclAnnotation) {
            $class = $this->entityClassResolver->getEntityClass($val->getClass());
            $group = $val->getGroup();

            return new ObjectIdentity($val->getType(), ObjectIdentityHelper::buildType($class, $group));
        }

        return $this->fromDomainObject($val);
    }

    /**
     * {@inheritdoc}
     */
    public function getMaskBuilder($permission)
    {
        if (!$permission) {
            $permission = 'VIEW';
        }

        return clone $this->getEntityMaskBuilder($this->getIdentityForPermission($permission));
    }

    /**
     * {@inheritdoc}
     */
    public function getAllMaskBuilders()
    {
        $result = [];
        $map = $this->getIdentityToPermissionsMap();
        foreach ($map as $identity => $permissions) {
            $result[] = clone $this->getEntityMaskBuilder($identity);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getMaskPattern($mask)
    {
        return EntityMaskBuilder::getPatternFor($mask);
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
            $maskBuilder = $this->getEntityMaskBuilder($identity);
            foreach ($permissions as $permission) {
                $mask = $rootMask & $maskBuilder->getMaskForGroup($permission);
                if (!$metadata->hasOwner()) {
                    if (\in_array($permission, $this->getOwnershipPermissions(), true)) {
                        $rootMask &= ~$this->removeServiceBits($mask);
                    } elseif ($this->getAccessLevel($mask) < AccessLevel::SYSTEM_LEVEL) {
                        $rootMask &= ~$this->removeServiceBits($mask);
                        $rootMask |= $maskBuilder->getMaskForPermission($permission . '_SYSTEM');
                    }
                } elseif ($metadata->isOrganizationOwned()) {
                    if ($this->getAccessLevel($mask) < AccessLevel::GLOBAL_LEVEL) {
                        $rootMask &= ~$this->removeServiceBits($mask);
                        $rootMask |= $maskBuilder->getMaskForPermission($permission . '_GLOBAL');
                    }
                } elseif ($metadata->isBusinessUnitOwned()) {
                    if ($this->getAccessLevel($mask) < AccessLevel::LOCAL_LEVEL) {
                        $rootMask &= ~$this->removeServiceBits($mask);
                        $rootMask |= $maskBuilder->getMaskForPermission($permission . '_LOCAL');
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
        if (null !== $permission) {
            $mask &= $this->getEntityMaskBuilder($identity)->getMaskForGroup($permission);
        }

        $mask = $this->removeServiceBits($mask);

        $result = $this->getAccessLevelForMask($mask, $identity);

        if (null !== $object) {
            $result = $this->metadataProvider->getMaxAccessLevel($result, $this->getObjectClassName($object));
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissions($mask = null, $setOnly = false, $byCurrentGroup = false)
    {
        if (null === $mask) {
            if ($byCurrentGroup) {
                return $this->getPermissionsForGroup($this->groupProvider->getGroup());
            }

            return array_keys($this->getPermissionsToIdentityMap());
        }
        if (!$setOnly) {
            $result = $this->getPermissionsForIdentity($this->getServiceBits($mask));
        } else {
            $result = [];
            if (0 !== $this->removeServiceBits($mask)) {
                $identity = $this->getServiceBits($mask);
                $maskBuilder = $this->getEntityMaskBuilder($identity);
                $mask = $this->removeServiceBits($mask);
                $permissions = $this->getPermissionsForIdentity($identity);
                foreach ($permissions as $permission) {
                    if (0 !== ($mask & $maskBuilder->getMaskForGroup($permission))) {
                        $result[] = $permission;
                    }
                }
            }
        }

        if ($byCurrentGroup && !empty($result)) {
            $result = array_intersect(
                $result,
                array_keys($this->permissionManager->getPermissionsMap($this->groupProvider->getGroup()))
            );
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllowedPermissions(ObjectIdentity $oid, $fieldName = null, $aclGroup = null)
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
                $result = array_diff($result, $this->getOwnershipPermissions());
            }
        }

        $allowed = $this->getPermissionsForType($oid->getType(), $aclGroup);

        return array_values(array_intersect($result, $allowed));
    }

    /**
     * That method returns the collection of permissions that used only if the level of ownership less than System
     *
     * @return array
     */
    protected function getOwnershipPermissions()
    {
        return ['ASSIGN'];
    }

    /**
     * @param string $type
     * @param string|null $aclGroup
     * @return array
     */
    protected function getPermissionsForType($type, $aclGroup = null)
    {
        $group = $aclGroup ?: $this->groupProvider->getGroup();

        if (ObjectIdentityFactory::ROOT_IDENTITY_TYPE === $type) {
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
     * {@inheritdoc}
     */
    public function decideIsGranting($triggeredMask, $object, TokenInterface $securityToken)
    {
        if (!$this->isSupportedObject($object) || null === $this->getObjectId($object)) {
            return true;
        }

        return $this->isAccessGranted($triggeredMask, $object, $securityToken);
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
            return new ObjectIdentity($id, ObjectIdentityHelper::buildType($type, $group));
        }

        throw new \InvalidArgumentException(
            sprintf('Unsupported object identity descriptor: %s.', $descriptor)
        );
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
        $maskBuilder = $this->getEntityMaskBuilder($identity);
        if (0 !== ($mask & $maskBuilder->getMaskForGroup($permission))) {
            $maskAccessLevels = [];
            $clearedMask = $this->removeServiceBits($mask);

            foreach (AccessLevel::$allAccessLevelNames as $accessLevel) {
                $levelMask = $this->removeServiceBits(
                    $maskBuilder->getMaskForPermission($permission . '_' . $accessLevel)
                );

                if (0 !== ($clearedMask & $levelMask)) {
                    $maskAccessLevels[] = $accessLevel;
                }
            }
            if (count($maskAccessLevels) > 1) {
                throw $this->createInvalidAccessLevelAclMaskException($mask, $object, $permission, $maskAccessLevels);
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
            return $this->getValidMasksForRoot($identity);
        }

        $metadata = $this->getMetadata($object);
        if (!$metadata->hasOwner()) {
            $maskBuilder = $this->getMaskBuilder($permission);
            $maskBuilder->add($maskBuilder->getMaskForGroup('SYSTEM'));
            foreach ($this->getOwnershipPermissions() as $ownershipPermission) {
                $maskName = $ownershipPermission . '_SYSTEM';
                if ($maskBuilder->hasMaskForPermission($maskName)) {
                    $maskBuilder->remove($maskName);
                }
            }

            return $maskBuilder->get();
        }

        if ($metadata->isOrganizationOwned()) {
            $maskBuilder = $this->getEntityMaskBuilder($identity);

            return
                $maskBuilder->getMaskForGroup('SYSTEM')
                | $maskBuilder->getMaskForGroup('GLOBAL');
        }
        if ($metadata->isBusinessUnitOwned()) {
            $maskBuilder = $this->getEntityMaskBuilder($identity);

            return
                $maskBuilder->getMaskForGroup('SYSTEM')
                | $maskBuilder->getMaskForGroup('GLOBAL')
                | $maskBuilder->getMaskForGroup('DEEP')
                | $maskBuilder->getMaskForGroup('LOCAL');
        }
        if ($metadata->isUserOwned()) {
            $maskBuilder = $this->getEntityMaskBuilder($identity);

            return
                $maskBuilder->getMaskForGroup('SYSTEM')
                | $maskBuilder->getMaskForGroup('GLOBAL')
                | $maskBuilder->getMaskForGroup('DEEP')
                | $maskBuilder->getMaskForGroup('LOCAL')
                | $maskBuilder->getMaskForGroup('BASIC');
        }

        return $this->getIdentityForPermission($permission);
    }

    /**
     * Makes sure that $this->permissionsToIdentity and $this->identityToPermissions are initialized
     */
    protected function ensurePermissionsInitialized()
    {
        if (null !== $this->permissionsToIdentity) {
            return;
        }

        $levelsCount = count(AccessLevel::$allAccessLevelNames);
        $map = range(0, EntityMaskBuilder::MAX_PERMISSIONS_IN_MASK - 1);
        array_unshift($map, array_pop($map));
        $allPermissions = $this->permissionManager->getPermissionsMap();
        foreach ($allPermissions as $permission => $pk) {
            $identity = (int) (($pk - 1) / EntityMaskBuilder::MAX_PERMISSIONS_IN_MASK);
            $identity <<= $levelsCount * EntityMaskBuilder::MAX_PERMISSIONS_IN_MASK;
            $number = $map[$pk % EntityMaskBuilder::MAX_PERMISSIONS_IN_MASK];

            $this->permissionsToIdentity[$permission] = $identity;
            $this->identityToPermissions[$identity][$number] = $permission;
        }
    }

    protected function buildPermissionsMap()
    {
        if (null !== $this->map) {
            return;
        }

        $this->map = [];
        $permissions = array_keys($this->getPermissionsToIdentityMap());
        foreach ($permissions as $permission) {
            $masks = [];

            $maskBuilder = $this->getEntityMaskBuilder($this->getIdentityForPermission($permission));
            foreach (AccessLevel::$allAccessLevelNames as $accessLevel) {
                $masks[] = $maskBuilder->getMaskForPermission($permission . '_' . $accessLevel);
            }

            $this->map[$permission] = $masks;
        }
    }

    /**
     * @return int[]
     */
    protected function getPermissionsToIdentityMap()
    {
        $this->ensurePermissionsInitialized();

        return $this->permissionsToIdentity;
    }

    /**
     * @param string $group
     *
     * @return int[]
     */
    protected function getPermissionsToIdentityMapForGroup($group)
    {
        $this->ensurePermissionsInitialized();

        return array_intersect_key(
            $this->permissionsToIdentity,
            $this->permissionManager->getPermissionsMap($group)
        );
    }

    /**
     * @param string $permission
     *
     * @return int
     */
    protected function getIdentityForPermission($permission)
    {
        $identities = $this->getPermissionsToIdentityMap();

        return $identities[$permission];
    }

    /**
     * @return array [identity => [permission, ...], ...]
     */
    protected function getIdentityToPermissionsMap()
    {
        $this->ensurePermissionsInitialized();

        return $this->identityToPermissions;
    }

    /**
     * @param int $identity
     *
     * @return string[]
     */
    protected function getPermissionsForIdentity($identity)
    {
        $this->ensurePermissionsInitialized();

        return $this->identityToPermissions[$identity];
    }

    /**
     * @param string $aclGroup
     *
     * @return string[]
     */
    private function getPermissionsForGroup($aclGroup)
    {
        if (isset($this->permissionsForGroup[$aclGroup])) {
            return $this->permissionsForGroup[$aclGroup];
        }

        $result = array_keys($this->getPermissionsToIdentityMapForGroup($aclGroup));
        $this->permissionsForGroup[$aclGroup] = $result;

        return $result;
    }

    /**
     * @param int $identity
     *
     * @return EntityMaskBuilder
     */
    protected function getEntityMaskBuilder($identity)
    {
        if (isset($this->builders[$identity])) {
            return $this->builders[$identity];
        }

        $maskBuilder = new EntityMaskBuilder($identity, $this->getPermissionsForIdentity($identity));
        $this->builders[$identity] = $maskBuilder;

        return $maskBuilder;
    }

    /**
     * @param int $mask
     * @param int $identity
     *
     * @return int
     */
    private function getAccessLevelForMask($mask, $identity)
    {
        if (isset($this->accessLevelForMask[$mask])) {
            return $this->accessLevelForMask[$mask];
        }

        $result = AccessLevel::NONE_LEVEL;
        if (0 !== $mask) {
            $maskBuilder = $this->getEntityMaskBuilder($identity);
            foreach (self::ACCESS_LEVELS as $accessLevelName => $accessLevel) {
                if (0 !== ($mask & $maskBuilder->getMaskForGroup($accessLevelName))) {
                    $result = $accessLevel;
                    break;
                }
            }
        }
        $this->accessLevelForMask[$mask] = $result;

        return $result;
    }

    private function getValidMasksForRoot(int $identity): int
    {
        $maskBuilder = $this->getEntityMaskBuilder($identity);
        $maxAccessLevel = $this->metadataProvider->getMaxAccessLevel(
            AccessLevel::SYSTEM_LEVEL,
            ObjectIdentityFactory::ROOT_IDENTITY_TYPE
        );

        return $maxAccessLevel === AccessLevel::SYSTEM_LEVEL
            ? $maskBuilder->getMaskForGroup('SYSTEM')
                | $maskBuilder->getMaskForGroup('GLOBAL')
                | $maskBuilder->getMaskForGroup('DEEP')
                | $maskBuilder->getMaskForGroup('LOCAL')
                | $maskBuilder->getMaskForGroup('BASIC')
            : $maskBuilder->getMaskForGroup('GLOBAL')
                | $maskBuilder->getMaskForGroup('DEEP')
                | $maskBuilder->getMaskForGroup('LOCAL')
                | $maskBuilder->getMaskForGroup('BASIC');
    }
}
