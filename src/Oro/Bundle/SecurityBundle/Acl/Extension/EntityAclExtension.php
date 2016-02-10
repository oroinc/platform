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
use Oro\Bundle\SecurityBundle\Acl\Permission\PermissionManager;
use Oro\Bundle\SecurityBundle\Annotation\Acl as AclAnnotation;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
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

    /**
     * key = Permission
     * value = The identity of a permission mask builder
     *
     * @var int[]
     */
    protected $permissionToMaskBuilderIdentity = [];

    /** @var array */
    protected $maskBuilderIdentityToPermissions = [];

    /**
     * @param ObjectIdAccessor $objectIdAccessor
     * @param EntityClassResolver $entityClassResolver
     * @param EntitySecurityMetadataProvider $entityMetadataProvider
     * @param MetadataProviderInterface $metadataProvider
     * @param AccessLevelOwnershipDecisionMakerInterface $decisionMaker
     * @param PermissionManager $permissionManager
     */
    public function __construct(
        ObjectIdAccessor $objectIdAccessor,
        EntityClassResolver $entityClassResolver,
        EntitySecurityMetadataProvider $entityMetadataProvider,
        MetadataProviderInterface $metadataProvider,
        AccessLevelOwnershipDecisionMakerInterface $decisionMaker,
        PermissionManager $permissionManager
    ) {
        $this->objectIdAccessor       = $objectIdAccessor;
        $this->entityClassResolver    = $entityClassResolver;
        $this->entityMetadataProvider = $entityMetadataProvider;
        $this->metadataProvider       = $metadataProvider;
        $this->decisionMaker          = $decisionMaker;
        $this->permissionManager      = $permissionManager;
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
     * @param int $pk
     * @return int
     */
    protected function getIdentityForPrimaryKey($pk)
    {
        $identity = (int) (($pk - 1) / EntityMaskBuilder::MAX_PERMISSIONS_IN_MASK);

        return $identity << (count(AccessLevel::$allAccessLevelNames) * EntityMaskBuilder::MAX_PERMISSIONS_IN_MASK);
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
    public function getAccessLevelNames($object)
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

        $this->loadPermissions();

        $permissions = $permission === null
            ? $this->getPermissions($mask, true)
            : array($permission);

        foreach ($permissions as $permission) {
            $validMasks = $this->getValidMasks($permission, $object);
            if (($mask | $validMasks) === $validMasks) {
                $identity = $this->permissionToMaskBuilderIdentity[$permission];
                foreach ($this->permissionToMaskBuilderIdentity as $p => $i) {
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

        $this->loadPermissions();

        $identity = $this->permissionToMaskBuilderIdentity[$permission];

        return new EntityMaskBuilder($identity, $this->maskBuilderIdentityToPermissions[$identity]);
    }

    /**
     * {@inheritdoc}
     */
    public function getAllMaskBuilders()
    {
        $this->loadPermissions();

        $result = [];
        foreach ($this->maskBuilderIdentityToPermissions as $identity => $permissions) {
            $result[] = new EntityMaskBuilder($identity, $permissions);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getMaskPattern($mask)
    {
        $this->loadPermissions();

        $identity    = $this->getServiceBits($mask);
        $maskBuilder = new EntityMaskBuilder($identity, $this->maskBuilderIdentityToPermissions[$identity]);

        return $maskBuilder->getPatternFor($mask);
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function adaptRootMask($rootMask, $object)
    {
        $this->loadPermissions();

        $permissions = $this->getPermissions($rootMask, true);
        if (!empty($permissions)) {
            $metadata = $this->getMetadata($object);
            $identity = $this->getServiceBits($rootMask);
            foreach ($permissions as $permission) {
                $permissionMask = $this->getMaskBuilderConst($identity, 'GROUP_' . $permission);
                $mask           = $rootMask & $permissionMask;
                $accessLevel    = $this->getAccessLevel($mask);
                if (!$metadata->hasOwner()) {
                    if ($identity === $this->permissionToMaskBuilderIdentity['ASSIGN']
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
            $mask           = $this->removeServiceBits($mask & $permissionMask);
        }

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
    public function getPermissions($mask = null, $setOnly = false)
    {
        $this->loadPermissions();

        if ($mask === null) {
            return array_keys($this->permissionToMaskBuilderIdentity);
        }

        $result = array();
        if (!$setOnly) {
            $identity = $this->getServiceBits($mask);
            foreach ($this->permissionToMaskBuilderIdentity as $permission => $id) {
                if ($id === $identity) {
                    $result[] = $permission;
                }
            }
        } elseif (0 !== $this->removeServiceBits($mask)) {
            $identity = $this->getServiceBits($mask);
            $mask = $this->removeServiceBits($mask);
            foreach ($this->permissionToMaskBuilderIdentity as $permission => $id) {
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
    public function getAllowedPermissions(ObjectIdentity $oid)
    {
        $this->loadPermissions();

        if ($oid->getType() === ObjectIdentityFactory::ROOT_IDENTITY_TYPE) {
            $result = array_keys($this->permissionToMaskBuilderIdentity);
        } else {
            $config = $this->entityMetadataProvider->getMetadata($oid->getType());
            $result = $config->getPermissions();
            if (empty($result)) {
                $result = array_keys($this->map);
            }

            $metadata = $this->getMetadata($oid);
            if (!$metadata->hasOwner()) {
                foreach ($result as $key => $value) {
                    if (in_array($value, array('ASSIGN', 'SHARE'))) {
                        unset($result[$key]);
                    }
                }
            }
        }

        return $result;
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
        $this->loadPermissions();

        $identity = $this->permissionToMaskBuilderIdentity[$permission];
        if (0 !== ($mask & $this->getMaskBuilderConst($identity, 'GROUP_' . $permission))) {
            $maskAccessLevels = array();
            foreach (AccessLevel::$allAccessLevelNames as $accessLevel) {
                $_mask = $this->removeServiceBits($mask);
                $levelMask = $this->removeServiceBits(
                    $this->getMaskBuilderConst($identity, 'MASK_' . $permission . '_' . $accessLevel)
                );

                if (0 !== ($_mask & $levelMask)) {
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
        $this->loadPermissions();

        $identity = $this->permissionToMaskBuilderIdentity[$permission];

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
            if ($identity === $this->permissionToMaskBuilderIdentity['CREATE']) {
                return $this->getMaskBuilderConst($identity, 'GROUP_SYSTEM');
            } elseif ($identity === $this->permissionToMaskBuilderIdentity['ASSIGN']) {
                return $this->getMaskBuilderConst($identity, 'MASK_DELETE_SYSTEM');
            }

            return $identity;
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

        return $this->permissionToMaskBuilderIdentity[$permission];
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
        $this->loadPermissions();

        $maskBuilder = new EntityMaskBuilder(
            $maskBuilderIdentity,
            $this->maskBuilderIdentityToPermissions[$maskBuilderIdentity]
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
        $map = [2, 0, 1];

        $allPermissions = $this->permissionManager->getPermissionsMap(false);
        $permissionChunks = array_chunk(array_keys($allPermissions), EntityMaskBuilder::MAX_PERMISSIONS_IN_MASK);

        foreach ($permissionChunks as $permissions) {
            foreach ($permissions as $permission) {
                $pk = $allPermissions[$permission];

                $identity = $this->getIdentityForPrimaryKey($pk);
                $number = $map[$pk % EntityMaskBuilder::MAX_PERMISSIONS_IN_MASK];

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

        $this->loadPermissions();
        $this->map = [];

        $permissions = array_keys($this->permissionToMaskBuilderIdentity);
        foreach ($permissions as $permission) {
            $maskBuilder = $this->getMaskBuilder($permission);
            $masks = [];

            foreach (AccessLevel::$allAccessLevelNames as $accessLevel) {
                $masks[] = $maskBuilder->getMask('MASK_' . $permission . '_' . $accessLevel);
            }

            $this->map[$permission] = $masks;
        }
    }
}
