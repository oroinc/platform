<?php

namespace Oro\Bundle\SecurityBundle\Acl\Permission;

use Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionSelector;
use Symfony\Component\Security\Acl\Permission\PermissionMapInterface;

/**
 * This is permission map complements the masks which have been defined
 * on in all implementations of the mask builder and registered using ACL extension functionality.
 */
class PermissionMap implements PermissionMapInterface
{
    /** @var AclExtensionSelector */
    private $extensionSelector;

    /** @var array */
    private $permissions = [];

    /**
     * Constructor
     *
     * @param AclExtensionSelector $extensionSelector
     */
    public function __construct(AclExtensionSelector $extensionSelector)
    {
        $this->extensionSelector = $extensionSelector;
    }

    /**
     * {@inheritDoc}
     */
    public function getMasks($permission, $object)
    {
        return $this->extensionSelector
            ->select($object)
            ->getMasks($permission);
    }

    /**
     * {@inheritDoc}
     */
    public function contains($permission)
    {
        if (isset($this->permissions[$permission])) {
            return $this->permissions[$permission];
        }

        $supported = false;
        $extensions = $this->extensionSelector->all();
        foreach ($extensions as $extension) {
            $permissionToCheck = $permission;
            if (!$permissionToCheck) {
                $permissionToCheck = $extension->getDefaultPermission();
            }
            if ($extension->hasMasks($permissionToCheck)) {
                $supported = true;
                break;
            }
            $fieldExtension = $extension->getFieldExtension();
            if (null !== $fieldExtension && $fieldExtension->hasMasks($permissionToCheck)) {
                $supported = true;
                break;
            }
        }
        $this->permissions[$permission] = $supported;

        return $supported;
    }
}
