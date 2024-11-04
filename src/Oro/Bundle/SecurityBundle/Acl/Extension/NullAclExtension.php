<?php

namespace Oro\Bundle\SecurityBundle\Acl\Extension;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Exception\InvalidAclMaskException;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * This class implements "Null object" design pattern for AclExtensionInterface
 */
final class NullAclExtension implements AclExtensionInterface
{
    #[\Override]
    public function supports($type, $id)
    {
        throw new \LogicException('Not supported by NullAclExtension.');
    }

    #[\Override]
    public function getExtensionKey()
    {
        throw new \LogicException('Not supported by NullAclExtension.');
    }

    #[\Override]
    public function validateMask($mask, $object, $permission = null)
    {
        throw new InvalidAclMaskException('Not supported by NullAclExtension.');
    }

    #[\Override]
    public function getObjectIdentity($val)
    {
        throw new InvalidDomainObjectException('Not supported by NullAclExtension.');
    }

    #[\Override]
    public function getMaskBuilder($permission)
    {
        throw new \LogicException('Not supported by NullAclExtension.');
    }

    #[\Override]
    public function getAllMaskBuilders()
    {
        throw new \LogicException('Not supported by NullAclExtension.');
    }

    #[\Override]
    public function getMaskPattern($mask)
    {
        return 'NullAclExtension: ' . $mask;
    }

    #[\Override]
    public function getMasks($permission)
    {
        return null;
    }

    #[\Override]
    public function hasMasks($permission)
    {
        return false;
    }

    #[\Override]
    public function adaptRootMask($rootMask, $object)
    {
        return $rootMask;
    }

    #[\Override]
    public function getServiceBits($mask)
    {
        return 0;
    }

    #[\Override]
    public function removeServiceBits($mask)
    {
        return $mask;
    }

    #[\Override]
    public function getAccessLevel($mask, $permission = null, $object = null)
    {
        return AccessLevel::UNKNOWN;
    }

    #[\Override]
    public function getPermissions($mask = null, $setOnly = false, $byCurrentGroup = false)
    {
        return [];
    }

    #[\Override]
    public function getAllowedPermissions(ObjectIdentity $oid, $fieldName = null, $aclGroup = null)
    {
        return [];
    }

    #[\Override]
    public function getDefaultPermission()
    {
        return '';
    }

    #[\Override]
    public function getPermissionGroupMask($mask)
    {
        return null;
    }

    #[\Override]
    public function getClasses()
    {
        return [];
    }

    #[\Override]
    public function decideIsGranting($triggeredMask, $object, TokenInterface $securityToken)
    {
        return true;
    }

    #[\Override]
    public function getAccessLevelNames($object, $permissionName = null)
    {
        return [];
    }

    #[\Override]
    public function getFieldExtension()
    {
        return null;
    }
}
