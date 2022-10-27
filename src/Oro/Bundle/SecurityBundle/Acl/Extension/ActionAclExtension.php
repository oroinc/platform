<?php

namespace Oro\Bundle\SecurityBundle\Acl\Extension;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
use Oro\Bundle\SecurityBundle\Acl\Permission\MaskBuilder;
use Oro\Bundle\SecurityBundle\Annotation\Acl as AclAnnotation;
use Oro\Bundle\SecurityBundle\Metadata\ActionSecurityMetadataProvider;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

/**
 * The ACL extension that works with actions (another name is capabilities).
 */
class ActionAclExtension extends AbstractAclExtension
{
    public const NAME = 'action';

    private const PERMISSION_EXECUTE = 'EXECUTE';

    /** @var ActionSecurityMetadataProvider */
    protected $actionMetadataProvider;

    /** @var MaskBuilder */
    protected $maskBuilder;

    public function __construct(ActionSecurityMetadataProvider $actionMetadataProvider)
    {
        $this->actionMetadataProvider = $actionMetadataProvider;

        $this->map = [
            self::PERMISSION_EXECUTE => [ActionMaskBuilder::MASK_EXECUTE]
        ];

        $this->maskBuilder = new ActionMaskBuilder();
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
    public function supports($type, $id)
    {
        if (ObjectIdentityFactory::ROOT_IDENTITY_TYPE === $type) {
            return $this->getExtensionKey() === $id;
        }

        return
            $this->getExtensionKey() === $id
            && $this->actionMetadataProvider->isKnownAction(ObjectIdentityHelper::removeGroupName($type));
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultPermission()
    {
        return self::PERMISSION_EXECUTE;
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissionGroupMask($mask)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissions($mask = null, $setOnly = false, $byCurrentGroup = false)
    {
        if (null === $mask || !$setOnly || 0 !== $mask) {
            return [self::PERMISSION_EXECUTE];
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAllowedPermissions(ObjectIdentity $oid, $fieldName = null, $aclGroup = null)
    {
        return [self::PERMISSION_EXECUTE];
    }

    /**
     * {@inheritdoc}
     */
    public function getClasses()
    {
        return $this->actionMetadataProvider->getActions();
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessLevelNames($object, $permissionName = null)
    {
        return [
            AccessLevel::NONE_LEVEL   => AccessLevel::NONE_LEVEL_NAME,
            AccessLevel::SYSTEM_LEVEL => AccessLevel::getAccessLevelName(AccessLevel::SYSTEM_LEVEL)
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessLevel($mask, $permission = null, $object = null)
    {
        return 0 === $mask
            ? AccessLevel::NONE_LEVEL
            : AccessLevel::SYSTEM_LEVEL;
    }

    /**
     * {@inheritdoc}
     */
    public function validateMask($mask, $object, $permission = null)
    {
        if (0 === $mask) {
            return;
        }
        if (ActionMaskBuilder::MASK_EXECUTE === $mask) {
            return;
        }

        throw $this->createInvalidAclMaskException($mask, $object);
    }

    /**
     * {@inheritdoc}
     */
    public function getObjectIdentity($val)
    {
        $type = $id = $group = null;
        if (\is_string($val)) {
            $this->parseDescriptor($val, $type, $id, $group);
        } elseif ($val instanceof AclAnnotation) {
            $type = $val->getId();
            $id = $val->getType();
            $group = $val->getGroup();
        }

        return new ObjectIdentity($id, ObjectIdentityHelper::buildType($type, $group));
    }

    /**
     * {@inheritdoc}
     */
    public function getMaskBuilder($permission)
    {
        return clone $this->maskBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllMaskBuilders()
    {
        return [clone $this->maskBuilder];
    }

    /**
     * {@inheritdoc}
     */
    public function getMaskPattern($mask)
    {
        return ActionMaskBuilder::getPatternFor($mask);
    }

    /**
     * {@inheritdoc}
     */
    public function getServiceBits($mask)
    {
        return $mask & ActionMaskBuilder::SERVICE_BITS;
    }

    /**
     * {@inheritdoc}
     */
    public function removeServiceBits($mask)
    {
        return $mask & ActionMaskBuilder::REMOVE_SERVICE_BITS;
    }
}
