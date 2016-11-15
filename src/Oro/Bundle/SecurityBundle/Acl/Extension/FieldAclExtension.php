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
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadataProvider;
use Oro\Bundle\SecurityBundle\Owner\EntityOwnerAccessor;
use Oro\Bundle\SecurityBundle\Owner\Metadata\MetadataProviderInterface;

class FieldAclExtension extends AbstractSimpleAccessLevelAclExtension
{
    const PERMISSION_VIEW   = 'VIEW';
    const PERMISSION_CREATE = 'CREATE';
    const PERMISSION_EDIT   = 'EDIT';

    /** @var EntityClassResolver */
    protected $entityClassResolver;

    /** @var ConfigProvider */
    protected $securityConfigProvider;

    /** @var EntitySecurityMetadataProvider */
    protected $entityMetadataProvider;

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

        return parent::getAccessLevelNames($object, $permissionName);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensionKey()
    {
        throw new \LogicException('Field ACL Extension does not support "getExtensionKey" method.');
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
    public function getObjectIdentity($val)
    {
        throw new \LogicException('Field ACL Extension does not support "getObjectIdentity" method');
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
    protected function getMaskBuilderConst($constName)
    {
        return FieldMaskBuilder::getConst($constName);
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
