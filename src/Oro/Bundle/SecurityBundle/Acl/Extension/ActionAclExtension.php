<?php

namespace Oro\Bundle\SecurityBundle\Acl\Extension;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
use Oro\Bundle\SecurityBundle\Annotation\Acl as AclAnnotation;
use Oro\Bundle\SecurityBundle\Metadata\ActionMetadataProvider;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

class ActionAclExtension extends AbstractAclExtension
{
    const NAME = 'action';

    const PERMISSION_EXECUTE = 'EXECUTE';

    /** @var ActionMetadataProvider */
    protected $actionMetadataProvider;

    /**
     * @param ActionMetadataProvider $actionMetadataProvider
     */
    public function __construct(ActionMetadataProvider $actionMetadataProvider)
    {
        $this->actionMetadataProvider = $actionMetadataProvider;

        $this->map = [
            self::PERMISSION_EXECUTE => [ActionMaskBuilder::MASK_EXECUTE]
        ];
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
        if ($type === ObjectIdentityFactory::ROOT_IDENTITY_TYPE) {
            return $id === $this->getExtensionKey();
        }

        return
            $id === $this->getExtensionKey()
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
    public function getPermissions($mask = null, $setOnly = false, $byCurrentGroup = false)
    {
        if ($mask === null || !$setOnly || $mask !== 0) {
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
        return $mask === 0
            ? AccessLevel::NONE_LEVEL
            : AccessLevel::SYSTEM_LEVEL;
    }

    /**
     * {@inheritdoc}
     */
    public function validateMask($mask, $object, $permission = null)
    {
        if ($mask === 0) {
            return;
        }
        if ($mask === ActionMaskBuilder::MASK_EXECUTE) {
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
        if (is_string($val)) {
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
        return new ActionMaskBuilder();
    }

    /**
     * {@inheritdoc}
     */
    public function getAllMaskBuilders()
    {
        return [new ActionMaskBuilder()];
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
