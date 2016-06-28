<?php

namespace Oro\Bundle\SecurityBundle\Acl\Extension;

use Symfony\Component\Security\Core\Util\ClassUtils;
use Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityBundle\Exception\InvalidEntityException;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Domain\EntityObjectReference;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdAccessor;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
use Oro\Bundle\SecurityBundle\Acl\Exception\InvalidAclMaskException;
use Oro\Bundle\SecurityBundle\Annotation\Acl as AclAnnotation;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadataProvider;
use Oro\Bundle\SecurityBundle\Owner\EntityOwnerAccessor;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\MetadataProviderInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class FieldAclExtension extends AbstractAclExtension
{
    const NAME = 'field';

    /** @var EntitySecurityMetadataProvider */
    protected $entityMetadataProvider;

    /** @var EntityClassResolver */
    protected $entityClassResolver;

    /** @var MetadataProviderInterface */
    protected $metadataProvider;

    /** @var AccessLevelOwnershipDecisionMakerInterface */
    protected $decisionMaker;

    /** @var ObjectIdAccessor */
    protected $objectIdAccessor;

    /** @var array */
    protected $metadataCache = [];

    /** @var EntityOwnerAccessor */
    protected $entityOwnerAccessor;

    /** @var ConfigProvider */
    protected $securityConfigProvider;

    /**
     * key = Permission
     * value = The identity of a permission mask builder
     *
     * @var int[]
     */
    protected $permissionToMaskBuilderIdentity = [];

    /**
     * key = The identity of a permission mask builder
     * value = The full class name of a permission mask builder
     *
     * @var string[]
     */
    protected $maskBuilderClassNames = [];

    /** @var array */
    protected $maskBuilderIdentityToPermissions;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        ObjectIdAccessor $objectIdAccessor,
        EntityClassResolver $entityClassResolver,
        EntitySecurityMetadataProvider $entityMetadataProvider,
        MetadataProviderInterface $metadataProvider,
        AccessLevelOwnershipDecisionMakerInterface $decisionMaker,
        EntityOwnerAccessor $entityOwnerAccessor,
        ConfigProvider $configProvider
    ) {
        $this->entityClassResolver = $entityClassResolver;
        $this->entityMetadataProvider = $entityMetadataProvider;
        $this->metadataProvider = $metadataProvider;
        $this->entityOwnerAccessor = $entityOwnerAccessor;
        $this->decisionMaker = $decisionMaker;
        $this->objectIdAccessor = $objectIdAccessor;
        $this->securityConfigProvider = $configProvider;

        $this->permissionToMaskBuilderIdentity = [
            'VIEW'   => FieldMaskBuilder::IDENTITY,
            'CREATE' => FieldMaskBuilder::IDENTITY,
            'EDIT'   => FieldMaskBuilder::IDENTITY,
        ];

        $this->maskBuilderIdentityToPermissions = [
            array_keys($this->permissionToMaskBuilderIdentity)
        ];

        $this->map = [
            'VIEW'   => [
                FieldMaskBuilder::MASK_VIEW_BASIC,
                FieldMaskBuilder::MASK_VIEW_LOCAL,
                FieldMaskBuilder::MASK_VIEW_DEEP,
                FieldMaskBuilder::MASK_VIEW_GLOBAL,
                FieldMaskBuilder::MASK_VIEW_SYSTEM,
            ],
            'CREATE' => [
                FieldMaskBuilder::MASK_CREATE_SYSTEM,
            ],
            'EDIT'   => [
                FieldMaskBuilder::MASK_EDIT_BASIC,
                FieldMaskBuilder::MASK_EDIT_LOCAL,
                FieldMaskBuilder::MASK_EDIT_DEEP,
                FieldMaskBuilder::MASK_EDIT_GLOBAL,
                FieldMaskBuilder::MASK_EDIT_SYSTEM,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function supports($type, $id)
    {
        if ($type === ObjectIdentityFactory::ROOT_IDENTITY_TYPE
            || $type === 'Oro\Bundle\SecurityBundle\Acl\Domain\EntityObjectReference'
        ) {
            return $id === $this->getExtensionKey();
        }

        if ($id === $this->getExtensionKey()) {
            $type = $this->entityClassResolver->getEntityClass(ClassUtils::getRealClass($type));
        } else {
            $type = ClassUtils::getRealClass($type);
        }

        if (!$this->entityClassResolver->isEntity($type)) {
            return false;
        }

        // either id starts with 'field' (e.g. field+fieldName)
        // or id is null (checking for new entity)

        return (0 === strpos($id, self::NAME) || null === $id);
    }

    /**
     * {@inheritdoc}
     */
    public function getAllowedPermissions(ObjectIdentity $oid, $fieldName = null)
    {
        return array_keys($this->getPermissionsToIdentityMap());
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
    public function getAccessLevelNames($object, $permissionName = null)
    {
        if ('CREATE' === $permissionName) {
            // only system and none access levels are applicable to Create permission
            return AccessLevel::getAccessLevelNames(AccessLevel::SYSTEM_LEVEL);
        }

        $metadata = $this->getMetadata($object);
        if (!$metadata->hasOwner()) {
            return AccessLevel::getAccessLevelNames(AccessLevel::SYSTEM_LEVEL);
        }

        if ($metadata->isBasicLevelOwned()) {
            $minLevel = AccessLevel::BASIC_LEVEL;
        } elseif ($metadata->isLocalLevelOwned()) {
            $minLevel = AccessLevel::LOCAL_LEVEL;
        } else {
            $minLevel = AccessLevel::GLOBAL_LEVEL;
        }

        return AccessLevel::getAccessLevelNames($minLevel);
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
    public function getMaskPattern($mask)
    {
        return FieldMaskBuilder::getPatternFor($mask);
    }

    /**
     * {@inheritdoc}
     */
    public function getMaskBuilder($permission)
    {
        return new FieldMaskBuilder();
    }

    /**
     * {@inheritdoc}
     */
    public function getAllMaskBuilders()
    {
        return [new FieldMaskBuilder()];
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
            $mask = $mask & $permissionMask;
        }

        $mask = $this->removeServiceBits($mask);

        $result = AccessLevel::NONE_LEVEL;
        foreach (AccessLevel::$allAccessLevelNames as $accessLevel) {
            if (0 !== ($mask & $this->getMaskBuilderConst($identity, 'GROUP_' . $accessLevel))) {
                $result = AccessLevel::getConst($accessLevel . '_LEVEL');
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissions($mask = null, $setOnly = false, $byCurrentGroup = false)
    {
        if ($mask === null) {
            return array_keys($this->permissionToMaskBuilderIdentity);
        }
        $result = [];
        if (!$setOnly) {
            $identity = $this->getServiceBits($mask);
            foreach ($this->permissionToMaskBuilderIdentity as $permission => $id) {
                if ($id === $identity) {
                    $result[] = $permission;
                }
            }
        } elseif (0 !== $this->removeServiceBits($mask)) {
            $identity = $this->getServiceBits($mask);
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
    public function getServiceBits($mask)
    {
        return $mask & FieldMaskBuilder::SERVICE_BITS;
    }

    /**
     * {@inheritdoc}
     */
    public function removeServiceBits($mask)
    {
        return $mask & FieldMaskBuilder::REMOVE_SERVICE_BITS;
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * {@inheritdoc}
     */
    public function decideIsGranting($triggeredMask, $object, TokenInterface $securityToken)
    {
        // check whether we check permissions for a domain object
        if ($object === null
            || !is_object($object)
            || ($object instanceof ObjectIdentityInterface && !($object instanceof EntityObjectReference))
        ) {
            return true;
        }

        $securityConfig = $this->securityConfigProvider->getConfig($this->getObjectClassName($object));
        // check if FACL is enabled for given object. If FACL not enabled - grant access
        if (!($securityConfig->get('field_acl_supported') && $securityConfig->get('field_acl_enabled'))) {
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
        $user = $securityToken->getUser();
        if (AccessLevel::BASIC_LEVEL === $accessLevel) {
            $result = $this->decisionMaker->isAssociatedWithBasicLevelEntity(
                $user,
                $object,
                $organization
            );
        } else {
            if ($metadata->isBasicLevelOwned()) {
                $result = $this->decisionMaker->isAssociatedWithBasicLevelEntity(
                    $user,
                    $object,
                    $organization
                );
            }
            if (!$result) {
                if (AccessLevel::LOCAL_LEVEL === $accessLevel) {
                    $result = $this->decisionMaker->isAssociatedWithLocalLevelEntity(
                        $user,
                        $object,
                        false,
                        $organization
                    );
                } elseif (AccessLevel::DEEP_LEVEL === $accessLevel) {
                    $result = $this->decisionMaker->isAssociatedWithLocalLevelEntity(
                        $user,
                        $object,
                        true,
                        $organization
                    );
                } elseif (AccessLevel::GLOBAL_LEVEL === $accessLevel) {
                    $result = $this->decisionMaker->isAssociatedWithGlobalLevelEntity(
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
     * Gets all valid bitmasks for the given object
     *
     * @param string $permission
     * @param mixed  $object
     *
     * @return int
     */
    protected function getValidMasks($permission, $object)
    {
        $identity = $this->permissionToMaskBuilderIdentity[$permission];

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
     * {@inheritdoc}
     */
    protected function parseDescriptor($descriptor, &$type, &$id, &$group)
    {
        parent::parseDescriptor($descriptor, $type, $id, $group);

        if (strpos($id, '+')) {
            $id = explode('+', $id)[0];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getObjectIdentity($val)
    {
        if (is_string($val)) {
            $identity = $this->fromDescriptor($val);
        } elseif ($val instanceof AclAnnotation) {
            $class = $this->entityClassResolver->getEntityClass($val->getClass());
            $identity = new ObjectIdentity($val->getType(), $class);
        } else {
            $identity = $this->fromDomainObject($val);
        }

        if (null === $identity->getIdentifier()) {
            $identity = new ObjectIdentity('entity', $identity->getType());
        }

        return $identity;
    }

    /**
     * {@inheritdoc}
     */
    protected function getPermissionsToIdentityMap($byCurrentGroup = false)
    {
        return $this->permissionToMaskBuilderIdentity;
    }

    /**
     * {@inheritdoc}
     */
    protected function getMaskBuilderConst($maskBuilderIdentity, $constName)
    {
        $maskBuilder = new FieldMaskBuilder(
            $maskBuilderIdentity,
            $this->getPermissionsForIdentity($maskBuilderIdentity)
        );

        return $maskBuilder->getMask($constName);
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
            return new ObjectIdentity($id, $type);
        }

        throw new \InvalidArgumentException(
            sprintf('Unsupported object identity descriptor: %s.', $descriptor)
        );
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
        $identity = $this->permissionToMaskBuilderIdentity[$permission];
        if (0 !== ($mask & $this->getMaskBuilderConst($identity, 'GROUP_' . $permission))) {
            $maskAccessLevels = [];
            foreach ($this->getAccessLevelNames($object, $permission) as $accessLevel) {
                if ($accessLevel === AccessLevel::NONE_LEVEL_NAME) {
                    continue;
                }
                if (0 !== ($mask & $this->getMaskBuilderConst($identity, 'MASK_' . $permission . '_' . $accessLevel))) {
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
        } elseif ($object instanceof EntityObjectReference) {
            $className = $object->getType();
        } else {
            $className = ClassUtils::getRealClass($object);
        }

        return $className;
    }

    /**
     * @param int|null $identity
     *
     * @return array
     */
    protected function getPermissionsForIdentity($identity = null)
    {
        return $identity === null
            ? $this->maskBuilderIdentityToPermissions
            : $this->maskBuilderIdentityToPermissions[$identity];
    }

    /**
     * Check organization. If user try to access entity what was created in organization this user do not have access -
     *  deny access. We should check organization for all the entities what have ownership
     *  (USER, BUSINESS_UNIT, ORGANIZATION ownership types)
     *
     * @param mixed                             $object
     * @param OrganizationContextTokenInterface $securityToken
     *
     * @return bool
     */
    protected function isAccessDeniedByOrganizationContext($object, OrganizationContextTokenInterface $securityToken)
    {
        try {
            // try to get entity organization value
            if ($object instanceof EntityObjectReference) {
                $objectOrganization = $object->getOrganizationId();
            } else {
                $objectOrganization = $this->entityOwnerAccessor->getOrganization($object);
            }

            if (is_object($objectOrganization)) {
                $objectOrganization = $objectOrganization->getId();
            }

            // check entity organization with current organization
            if ($objectOrganization && $objectOrganization !== $securityToken->getOrganizationContext()->getId()) {
                return true;
            }
        } catch (InvalidEntityException $e) {
            // in case if entity has no organization field (none ownership type)
        }

        return false;
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
}
