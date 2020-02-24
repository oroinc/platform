<?php

namespace Oro\Bundle\SecurityBundle\Acl\Extension;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Exception\InvalidAclMaskException;
use Oro\Bundle\SecurityBundle\Acl\Permission\MaskBuilder;

/**
 * The base class for ACL extensions that check permissions based on access levels
 * and have only one mask builder for all supported permissions.
 */
abstract class AbstractSimpleAccessLevelAclExtension extends AbstractAccessLevelAclExtension
{
    /** @var string[] */
    protected $permissions;

    /** @var MaskBuilder */
    protected $maskBuilder;

    /** @var array [mask => access level, ...] */
    private $accessLevelForMask = [];

    /** @var array [mask => group mask, ...] */
    private $permissionGroupMasks = [];

    /**
     * {@inheritdoc}
     */
    public function getAccessLevel($mask, $permission = null, $object = null)
    {
        if (0 === $mask) {
            return AccessLevel::NONE_LEVEL;
        }

        if (null !== $permission) {
            $mask &= $this->getMaskForGroup($permission);
        }

        return $this->getAccessLevelForMask($mask);
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
                if (0 !== ($mask & $this->getMaskForGroup($permission))) {
                    $result[] = $permission;
                }
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissionGroupMask($mask)
    {
        if (\array_key_exists($mask, $this->permissionGroupMasks)) {
            return $this->permissionGroupMasks[$mask];
        }

        $result = null;
        $permissions = $this->getPermissions($mask, true);
        foreach ($permissions as $permission) {
            if ($this->maskBuilder->hasMaskForGroup($permission)) {
                $result = $this->maskBuilder->getMaskForGroup($permission);
                break;
            }
        }
        $this->permissionGroupMasks[$mask] = $result;

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function adaptRootMask($rootMask, $object)
    {
        $permissions = $this->getPermissions($rootMask, true);
        if (!empty($permissions)) {
            $metadata = $this->getMetadata($object);
            foreach ($permissions as $permission) {
                $permissionMask = $this->getMaskForGroup($permission);
                $mask = $rootMask & $permissionMask;
                $accessLevel = $this->getAccessLevel($mask);
                if (!$metadata->hasOwner()) {
                    if ($accessLevel < AccessLevel::SYSTEM_LEVEL) {
                        $rootMask &= ~$this->removeServiceBits($mask);
                        $rootMask |= $this->getMaskForPermission($permission . '_SYSTEM');
                    }
                } elseif ($metadata->isOrganizationOwned()) {
                    if ($accessLevel < AccessLevel::GLOBAL_LEVEL) {
                        $rootMask &= ~$this->removeServiceBits($mask);
                        $rootMask |= $this->getMaskForPermission($permission . '_GLOBAL');
                    }
                } elseif ($metadata->isBusinessUnitOwned()) {
                    if ($accessLevel < AccessLevel::LOCAL_LEVEL) {
                        $rootMask &= ~$this->removeServiceBits($mask);
                        $rootMask |= $this->getMaskForPermission($permission . '_LOCAL');
                    }
                }
            }
        }

        return $rootMask;
    }

    /**
     * {@inheritdoc}
     */
    public function validateMask($mask, $object, $permission = null)
    {
        if (0 === $mask) {
            return;
        }

        $validMasks = $this->getValidMasks($object);
        if (($mask | $validMasks) === $validMasks) {
            $permissions = null === $permission
                ? $this->getPermissions($mask, true)
                : [$permission];
            foreach ($permissions as $p) {
                $this->validateMaskAccessLevel($p, $mask, $object);
            }

            return;
        }

        throw $this->createInvalidAclMaskException($mask, $object);
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
     * Gets permission value for the given group
     *
     * @param string $name
     *
     * @return int
     */
    protected function getMaskForGroup($name)
    {
        return $this->maskBuilder->getMaskForGroup($name);
    }

    /**
     * Gets permission value by its name
     *
     * @param string $name
     *
     * @return int
     */
    protected function getMaskForPermission($name)
    {
        return $this->maskBuilder->getMaskForPermission($name);
    }

    /**
     * Gets all valid bitmasks for the given object
     *
     * @param mixed $object
     *
     * @return int
     */
    protected function getValidMasks($object)
    {
        $metadata = $this->getMetadata($object);
        if (!$metadata->hasOwner()) {
            return $this->getMaskForGroup('SYSTEM');
        }

        if ($metadata->isOrganizationOwned()) {
            return
                $this->getMaskForGroup('SYSTEM')
                | $this->getMaskForGroup('GLOBAL');
        }
        if ($metadata->isBusinessUnitOwned()) {
            return
                $this->getMaskForGroup('SYSTEM')
                | $this->getMaskForGroup('GLOBAL')
                | $this->getMaskForGroup('DEEP')
                | $this->getMaskForGroup('LOCAL');
        }
        if ($metadata->isUserOwned()) {
            return
                $this->getMaskForGroup('SYSTEM')
                | $this->getMaskForGroup('GLOBAL')
                | $this->getMaskForGroup('DEEP')
                | $this->getMaskForGroup('LOCAL')
                | $this->getMaskForGroup('BASIC');
        }

        return $this->getMaskForGroup('NONE');
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
        if (0 !== ($mask & $this->getMaskForGroup($permission))) {
            $maskAccessLevels = [];
            foreach ($this->getAccessLevelNames($object, $permission) as $accessLevel) {
                if ($accessLevel === AccessLevel::NONE_LEVEL_NAME) {
                    continue;
                }
                if (0 !== ($mask & $this->getMaskForPermission($permission . '_' . $accessLevel))) {
                    $maskAccessLevels[] = $accessLevel;
                }
            }
            if (count($maskAccessLevels) > 1) {
                throw $this->createInvalidAccessLevelAclMaskException($mask, $object, $permission, $maskAccessLevels);
            }
        }
    }

    /**
     * @param int $mask
     *
     * @return int
     */
    private function getAccessLevelForMask($mask)
    {
        if (isset($this->accessLevelForMask[$mask])) {
            return $this->accessLevelForMask[$mask];
        }

        $result = AccessLevel::NONE_LEVEL;
        if (0 !== $mask) {
            foreach (self::ACCESS_LEVELS as $accessLevelName => $accessLevel) {
                if (0 !== ($mask & $this->getMaskForGroup($accessLevelName))) {
                    $result = $accessLevel;
                    break;
                }
            }
        }
        $this->accessLevelForMask[$mask] = $result;

        return $result;
    }
}
