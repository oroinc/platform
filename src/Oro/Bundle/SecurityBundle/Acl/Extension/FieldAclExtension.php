<?php

namespace Oro\Bundle\SecurityBundle\Acl\Extension;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Util\ClassUtils;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityBundle\Exception\InvalidEntityException;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Domain\EntityObjectReference;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdAccessor;
use Oro\Bundle\SecurityBundle\Acl\Exception\InvalidAclMaskException;
use Oro\Bundle\SecurityBundle\Annotation\Acl as AclAnnotation;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadataProvider;
use Oro\Bundle\SecurityBundle\Owner\EntityOwnerAccessor;
use Oro\Bundle\SecurityBundle\Owner\Metadata\MetadataProviderInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class FieldAclExtension extends AbstractAccessLevelAclExtension
{
    const NAME = 'field';

    const PERMISSION_VIEW   = 'VIEW';
    const PERMISSION_CREATE = 'CREATE';
    const PERMISSION_EDIT   = 'EDIT';

    /** @var EntityClassResolver */
    protected $entityClassResolver;

    /** @var ConfigProvider */
    protected $securityConfigProvider;

    /** @var EntitySecurityMetadataProvider */
    protected $entityMetadataProvider;

    /** @var string[] */
    protected $permissions = [];

    /**
     * @param ObjectIdAccessor                           $objectIdAccessor
     * @param EntityClassResolver                        $entityClassResolver
     * @param MetadataProviderInterface                  $metadataProvider
     * @param AccessLevelOwnershipDecisionMakerInterface $decisionMaker
     * @param EntityOwnerAccessor                        $entityOwnerAccessor
     * @param ConfigProvider                             $configProvider
     * @param EntitySecurityMetadataProvider             $entityMetadataProvider
     */
    public function __construct(
        ObjectIdAccessor $objectIdAccessor,
        EntityClassResolver $entityClassResolver,
        MetadataProviderInterface $metadataProvider,
        AccessLevelOwnershipDecisionMakerInterface $decisionMaker,
        EntityOwnerAccessor $entityOwnerAccessor,
        ConfigProvider $configProvider,
        EntitySecurityMetadataProvider $entityMetadataProvider
    ) {
        parent::__construct($objectIdAccessor, $metadataProvider, $entityOwnerAccessor, $decisionMaker);
        $this->entityClassResolver = $entityClassResolver;
        $this->securityConfigProvider = $configProvider;
        $this->entityMetadataProvider = $entityMetadataProvider;

        $this->permissions = [
            self::PERMISSION_VIEW,
            self::PERMISSION_CREATE,
            self::PERMISSION_EDIT,
        ];

        $this->map = [
            self::PERMISSION_VIEW   => [
                FieldMaskBuilder::MASK_VIEW_BASIC,
                FieldMaskBuilder::MASK_VIEW_LOCAL,
                FieldMaskBuilder::MASK_VIEW_DEEP,
                FieldMaskBuilder::MASK_VIEW_GLOBAL,
                FieldMaskBuilder::MASK_VIEW_SYSTEM,
            ],
            self::PERMISSION_CREATE => [
                FieldMaskBuilder::MASK_CREATE_SYSTEM,
            ],
            self::PERMISSION_EDIT   => [
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
        throw new \LogicException('Field ACL Extension does not support "supports" method');
    }

    /**
     * {@inheritdoc}
     */
    public function getAllowedPermissions(ObjectIdentity $oid, $fieldName = null)
    {
        $fields = $this->entityMetadataProvider->getMetadata($oid->getType())->getFields();
        $result = $fields[$fieldName]->getPermissions();
        if (empty($result)) {
            $result = $this->permissions;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getClasses()
    {
        throw new \LogicException('Field ACL Extension does not support "getClasses" method');
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessLevelNames($object, $permissionName = null)
    {
        if (self::PERMISSION_CREATE === $permissionName) {
            // only system and none access levels are applicable to CREATE permission
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
        if (0 === $mask) {
            return AccessLevel::NONE_LEVEL;
        }

        if ($permission !== null) {
            $mask &= FieldMaskBuilder::getConst('GROUP_' . $permission);
        }

        $result = AccessLevel::NONE_LEVEL;
        foreach (AccessLevel::$allAccessLevelNames as $accessLevel) {
            if (0 !== ($mask & FieldMaskBuilder::getConst('GROUP_' . $accessLevel))) {
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
            return $this->permissions;
        }

        $result = [];
        if (!$setOnly) {
            $result = $this->permissions;
        } elseif (0 !== $mask) {
            foreach ($this->permissions as $permission) {
                if (0 !== ($mask & FieldMaskBuilder::getConst('GROUP_' . $permission))) {
                    $result[] = $permission;
                }
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function decideIsGranting($triggeredMask, $object, TokenInterface $securityToken)
    {
        if (!$this->isSupportedObject($object)) {
            return true;
        }

        if (!$this->isFieldLevelAclEnabled($object)) {
            return true;
        }

        return $this->isAccessGranted($triggeredMask, $object, $securityToken);
    }

    /**
     * {@inheritdoc}
     */
    public function validateMask($mask, $object, $permission = null)
    {
        if (0 === $mask) {
            return;
        }

        $permissions = $permission === null
            ? $this->getPermissions($mask, true)
            : [$permission];

        foreach ($permissions as $permission) {
            $validMasks = $this->getValidMasks($permission, $object);
            if (($mask | $validMasks) === $validMasks) {
                foreach ($this->permissions as $p) {
                    $this->validateMaskAccessLevel($p, $mask, $object);
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
     * Gets all valid bitmasks for the given object
     *
     * @param string $permission
     * @param mixed  $object
     *
     * @return int
     */
    protected function getValidMasks($permission, $object)
    {
        $metadata = $this->getMetadata($object);
        if (!$metadata->hasOwner()) {
            return FieldMaskBuilder::GROUP_SYSTEM;
        }

        if ($metadata->isGlobalLevelOwned()) {
            return
                FieldMaskBuilder::GROUP_SYSTEM
                | FieldMaskBuilder::GROUP_GLOBAL;
        } elseif ($metadata->isLocalLevelOwned()) {
            return
                FieldMaskBuilder::GROUP_SYSTEM
                | FieldMaskBuilder::GROUP_GLOBAL
                | FieldMaskBuilder::GROUP_DEEP
                | FieldMaskBuilder::GROUP_LOCAL;
        } elseif ($metadata->isBasicLevelOwned()) {
            return
                FieldMaskBuilder::GROUP_SYSTEM
                | FieldMaskBuilder::GROUP_GLOBAL
                | FieldMaskBuilder::GROUP_DEEP
                | FieldMaskBuilder::GROUP_LOCAL
                | FieldMaskBuilder::GROUP_BASIC;
        }

        return FieldMaskBuilder::GROUP_NONE;
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

        $type = $this->entityClassResolver->getEntityClass(ClassUtils::getRealClass($this->getObjectClassName($type)));

        if ($id === $this->getExtensionKey()) {
            return new ObjectIdentity($id, $type);
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
        if (0 !== ($mask & FieldMaskBuilder::getConst('GROUP_' . $permission))) {
            $maskAccessLevels = [];
            foreach ($this->getAccessLevelNames($object, $permission) as $accessLevel) {
                if ($accessLevel === AccessLevel::NONE_LEVEL_NAME) {
                    continue;
                }
                if (0 !== ($mask & FieldMaskBuilder::getConst('MASK_' . $permission . '_' . $accessLevel))) {
                    $maskAccessLevels[] = $accessLevel;
                }
            }
            if (count($maskAccessLevels) > 1) {
                throw $this->createInvalidAccessLevelAclMaskException($mask, $object, $permission, $maskAccessLevels);
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
            if (ObjectIdentityHelper::isFieldEncodedKey($object)) {
                $object = ObjectIdentityHelper::decodeEntityFieldInfo($object)[0];
            }
            $this->parseDescriptor($object, $className, $id, $group);
        } elseif ($object instanceof EntityObjectReference) {
            $className = $object->getType();
        } else {
            $className = ClassUtils::getRealClass($object);
        }

        return $className;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    protected function isSupportedObject($object)
    {
        return
            parent::isSupportedObject($object)
            && !$object instanceof EntityObjectReference;
    }

    /**
     * @param object $object
     *
     * @return bool
     */
    protected function isFieldLevelAclEnabled($object)
    {
        $securityConfig = $this->securityConfigProvider->getConfig($this->getObjectClassName($object));

        return
            $securityConfig->get('field_acl_supported')
            && $securityConfig->get('field_acl_enabled');
    }
}
