<?php

namespace Oro\Bundle\SecurityBundle\Acl\Extension;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdAccessor;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
use Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadataProvider;
use Oro\Bundle\SecurityBundle\Owner\EntityOwnerAccessor;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Util\ClassUtils;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class FieldAclExtension extends AbstractSimpleAccessLevelAclExtension
{
    const PERMISSION_VIEW   = 'VIEW';
    const PERMISSION_CREATE = 'CREATE';
    const PERMISSION_EDIT   = 'EDIT';

    /** @var ConfigManager */
    protected $configManager;

    /** @var EntitySecurityMetadataProvider */
    protected $entityMetadataProvider;

    /** @var array */
    protected $supportedTypes = [];

    /**
     * @param ObjectIdAccessor                           $objectIdAccessor
     * @param OwnershipMetadataProviderInterface         $metadataProvider
     * @param AccessLevelOwnershipDecisionMakerInterface $decisionMaker
     * @param EntityOwnerAccessor                        $entityOwnerAccessor
     * @param ConfigManager                              $configManager
     * @param EntitySecurityMetadataProvider             $entityMetadataProvider
     */
    public function __construct(
        ObjectIdAccessor $objectIdAccessor,
        OwnershipMetadataProviderInterface $metadataProvider,
        AccessLevelOwnershipDecisionMakerInterface $decisionMaker,
        EntityOwnerAccessor $entityOwnerAccessor,
        ConfigManager $configManager,
        EntitySecurityMetadataProvider $entityMetadataProvider
    ) {
        parent::__construct($objectIdAccessor, $metadataProvider, $entityOwnerAccessor, $decisionMaker);
        $this->configManager = $configManager;
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
    public function getExtensionKey()
    {
        return EntityAclExtension::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($type, $id)
    {
        if (array_key_exists($type, $this->supportedTypes)) {
            return $this->supportedTypes[$type];
        }

        if ($type === ObjectIdentityFactory::ROOT_IDENTITY_TYPE) {
            $result = true;
        } else {
            $result = false;
            $entityClass = ClassUtils::getRealClass(
                ObjectIdentityHelper::removeGroupName(ObjectIdentityHelper::removeFieldName($type))
            );
            if ($this->configManager->hasConfig($entityClass)) {
                $securityConfig = $this->configManager->getEntityConfig('security', $entityClass);
                $result =
                    $securityConfig->get('field_acl_supported')
                    && $securityConfig->get('field_acl_enabled');
            }
        }

        $this->supportedTypes[$type] = $result;

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getClasses()
    {
        throw new \LogicException('Field ACL Extension does not support "getClasses" method.');
    }

    /**
     * {@inheritdoc}
     */
    public function getObjectIdentity($val)
    {
        throw new \LogicException('Field ACL Extension does not support "getObjectIdentity" method.');
    }

    /**
     * {@inheritdoc}
     */
    public function getAllowedPermissions(ObjectIdentity $oid, $fieldName = null, $aclGroup = null)
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
    public function decideIsGranting($triggeredMask, $object, TokenInterface $securityToken)
    {
        if (!$this->isSupportedObject($object)) {
            return true;
        }

        return $this->isAccessGranted($triggeredMask, $object, $securityToken);
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
     * {@inheritdoc}
     */
    protected function getMaskBuilderConst($constName)
    {
        return FieldMaskBuilder::getConst($constName);
    }

    /**
     * {@inheritdoc}
     */
    protected function parseDescriptor($descriptor, &$type, &$id, &$group)
    {
        $descriptor = ObjectIdentityHelper::removeFieldName($descriptor);

        return parent::parseDescriptor($descriptor, $type, $id, $group);
    }
}
