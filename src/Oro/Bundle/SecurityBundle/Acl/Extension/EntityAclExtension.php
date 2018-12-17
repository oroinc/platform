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
 * The ACL extensions that check permissions for ORM entities.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class EntityAclExtension extends AbstractAccessLevelAclExtension
{
    const NAME = 'entity';

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

    /**
     * @param ObjectIdAccessor                           $objectIdAccessor
     * @param EntityClassResolver                        $entityClassResolver
     * @param EntitySecurityMetadataProvider             $entityMetadataProvider
     * @param OwnershipMetadataProviderInterface         $metadataProvider
     * @param EntityOwnerAccessor                        $entityOwnerAccessor
     * @param AccessLevelOwnershipDecisionMakerInterface $decisionMaker
     * @param PermissionManager                          $permissionManager
     * @param AclGroupProviderInterface                  $groupProvider
     * @param FieldAclExtension                          $fieldAclExtension
     */
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
        if ($type === ObjectIdentityFactory::ROOT_IDENTITY_TYPE) {
            return $id === $this->getExtensionKey();
        }

        $type = ClassUtils::getRealClass(
            ObjectIdentityHelper::removeGroupName(ObjectIdentityHelper::removeFieldName($type))
        );
        if ($id === $this->getExtensionKey()) {
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

            return new ObjectIdentity($val->getType(), ObjectIdentityHelper::buildType($class, $group));
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
                $mask = $rootMask & $maskBuilder->getMask('GROUP_' . $permission);
                if (!$metadata->hasOwner()) {
                    if (in_array($permission, $this->getOwnershipPermissions(), true)) {
                        $rootMask &= ~$this->removeServiceBits($mask);
                    } elseif ($this->getAccessLevel($mask) < AccessLevel::SYSTEM_LEVEL) {
                        $rootMask &= ~$this->removeServiceBits($mask);
                        $rootMask |= $maskBuilder->getMask('MASK_' . $permission . '_SYSTEM');
                    }
                } elseif ($metadata->isOrganizationOwned()) {
                    if ($this->getAccessLevel($mask) < AccessLevel::GLOBAL_LEVEL) {
                        $rootMask &= ~$this->removeServiceBits($mask);
                        $rootMask |= $maskBuilder->getMask('MASK_' . $permission . '_GLOBAL');
                    }
                } elseif ($metadata->isBusinessUnitOwned()) {
                    if ($this->getAccessLevel($mask) < AccessLevel::LOCAL_LEVEL) {
                        $rootMask &= ~$this->removeServiceBits($mask);
                        $rootMask |= $maskBuilder->getMask('MASK_' . $permission . '_LOCAL');
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
            $mask &= $this->getEntityMaskBuilder($identity)->getMask('GROUP_' . $permission);
        }

        $mask = $this->removeServiceBits($mask);

        $result = AccessLevel::NONE_LEVEL;
        if (0 !== $mask) {
            $maskBuilder = $this->getEntityMaskBuilder($identity);
            foreach (self::ACCESS_LEVELS as $accessLevelName => $accessLevel) {
                if (0 !== ($mask & $maskBuilder->getMask('GROUP_' . $accessLevelName))) {
                    $result = $accessLevel;
                    break;
                }
            }
        }

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
        if ($mask === null) {
            return array_keys($this->getPermissionsToIdentityMap($byCurrentGroup));
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
                    if (0 !== ($mask & $maskBuilder->getMask('GROUP_' . $permission))) {
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
        if (0 !== ($mask & $maskBuilder->getMask('GROUP_' . $permission))) {
            $maskAccessLevels = [];
            $clearedMask = $this->removeServiceBits($mask);

            foreach (AccessLevel::$allAccessLevelNames as $accessLevel) {
                $levelMask = $this->removeServiceBits(
                    $maskBuilder->getMask('MASK_' . $permission . '_' . $accessLevel)
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
            $maskBuilder = $this->getEntityMaskBuilder($identity);

            return
                $maskBuilder->getMask('GROUP_SYSTEM')
                | $maskBuilder->getMask('GROUP_GLOBAL')
                | $maskBuilder->getMask('GROUP_DEEP')
                | $maskBuilder->getMask('GROUP_LOCAL')
                | $maskBuilder->getMask('GROUP_BASIC');
        }

        $metadata = $this->getMetadata($object);
        if (!$metadata->hasOwner()) {
            $maskBuilder = $this->getMaskBuilder($permission);
            $maskBuilder->reset()->add($maskBuilder->getMask('GROUP_SYSTEM'));

            foreach ($this->getOwnershipPermissions() as $ownershipPermission) {
                $maskName = 'MASK_' . $ownershipPermission . '_SYSTEM';

                if ($maskBuilder->hasMask($maskName)) {
                    $maskBuilder->remove($ownershipPermission . '_SYSTEM');
                }
            }

            return $maskBuilder->get();
        }

        if ($metadata->isOrganizationOwned()) {
            $maskBuilder = $this->getEntityMaskBuilder($identity);

            return
                $maskBuilder->getMask('GROUP_SYSTEM')
                | $maskBuilder->getMask('GROUP_GLOBAL');
        } elseif ($metadata->isBusinessUnitOwned()) {
            $maskBuilder = $this->getEntityMaskBuilder($identity);

            return
                $maskBuilder->getMask('GROUP_SYSTEM')
                | $maskBuilder->getMask('GROUP_GLOBAL')
                | $maskBuilder->getMask('GROUP_DEEP')
                | $maskBuilder->getMask('GROUP_LOCAL');
        } elseif ($metadata->isUserOwned()) {
            $maskBuilder = $this->getEntityMaskBuilder($identity);

            return
                $maskBuilder->getMask('GROUP_SYSTEM')
                | $maskBuilder->getMask('GROUP_GLOBAL')
                | $maskBuilder->getMask('GROUP_DEEP')
                | $maskBuilder->getMask('GROUP_LOCAL')
                | $maskBuilder->getMask('GROUP_BASIC');
        }

        return $this->getIdentityForPermission($permission);
    }

    /**
     * Gets the constant value defined in the given permission mask builder
     *
     * @param int    $identity  The permission mask builder identity
     * @param string $constName The name of a constant
     *
     * @return int
     */
    protected function getMaskBuilderConst($identity, $constName)
    {
        return $this->getEntityMaskBuilder($identity)->getMask($constName);
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
        if ($this->map !== null) {
            return;
        }

        $this->map = [];

        $permissions = array_keys($this->getPermissionsToIdentityMap());
        foreach ($permissions as $permission) {
            $masks = [];

            $maskBuilder = $this->getEntityMaskBuilder($this->getIdentityForPermission($permission));
            foreach (AccessLevel::$allAccessLevelNames as $accessLevel) {
                $masks[] = $maskBuilder->getMask('MASK_' . $permission . '_' . $accessLevel);
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
        $this->ensurePermissionsInitialized();

        $map = $this->permissionsToIdentity;
        if ($byCurrentGroup) {
            $permissions = $this->permissionManager->getPermissionsMap($this->groupProvider->getGroup());

            $map = array_intersect_key($map, $permissions);
        }

        return $map;
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
     * @return array [identity => [permission, ...], ...]
     */
    protected function getIdentityToPermissionsMap()
    {
        $this->ensurePermissionsInitialized();

        return $this->identityToPermissions;
    }

    /**
     * @param int $identity
     * @return string[]
     */
    protected function getPermissionsForIdentity($identity)
    {
        $this->ensurePermissionsInitialized();

        return $this->identityToPermissions[$identity];
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
}
