<?php

namespace Oro\Bundle\SecurityBundle\Acl\Extension;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Exception\InvalidAclMaskException;

abstract class AbstractSimpleAccessLevelAclExtension extends AbstractAccessLevelAclExtension
{
    /** @var string[] */
    protected $permissions;

    /**
     * {@inheritdoc}
     */
    public function getAccessLevelNames($object, $permissionName = null)
    {
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
    public function getAccessLevel($mask, $permission = null, $object = null)
    {
        if (0 === $mask) {
            return AccessLevel::NONE_LEVEL;
        }

        if ($permission !== null) {
            $mask &= $this->getMaskBuilderConst('GROUP_' . $permission);
        }

        $result = AccessLevel::NONE_LEVEL;
        foreach (AccessLevel::$allAccessLevelNames as $accessLevel) {
            if (0 !== ($mask & $this->getMaskBuilderConst('GROUP_' . $accessLevel))) {
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
                if (0 !== ($mask & $this->getMaskBuilderConst('GROUP_' . $permission))) {
                    $result[] = $permission;
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
     * Gets the constant value defined in the mask builder
     *
     * @param string $constName
     *
     * @return int
     */
    abstract protected function getMaskBuilderConst($constName);

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
            return $this->getMaskBuilderConst('GROUP_SYSTEM');
        }

        if ($metadata->isGlobalLevelOwned()) {
            return
                $this->getMaskBuilderConst('GROUP_SYSTEM')
                | $this->getMaskBuilderConst('GROUP_GLOBAL');
        } elseif ($metadata->isLocalLevelOwned()) {
            return
                $this->getMaskBuilderConst('GROUP_SYSTEM')
                | $this->getMaskBuilderConst('GROUP_GLOBAL')
                | $this->getMaskBuilderConst('GROUP_DEEP')
                | $this->getMaskBuilderConst('GROUP_LOCAL');
        } elseif ($metadata->isBasicLevelOwned()) {
            return
                $this->getMaskBuilderConst('GROUP_SYSTEM')
                | $this->getMaskBuilderConst('GROUP_GLOBAL')
                | $this->getMaskBuilderConst('GROUP_DEEP')
                | $this->getMaskBuilderConst('GROUP_LOCAL')
                | $this->getMaskBuilderConst('GROUP_BASIC');
        }

        return $this->getMaskBuilderConst('GROUP_NONE');
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
        if (0 !== ($mask & $this->getMaskBuilderConst('GROUP_' . $permission))) {
            $maskAccessLevels = [];
            foreach ($this->getAccessLevelNames($object, $permission) as $accessLevel) {
                if ($accessLevel === AccessLevel::NONE_LEVEL_NAME) {
                    continue;
                }
                if (0 !== ($mask & $this->getMaskBuilderConst('MASK_' . $permission . '_' . $accessLevel))) {
                    $maskAccessLevels[] = $accessLevel;
                }
            }
            if (count($maskAccessLevels) > 1) {
                throw $this->createInvalidAccessLevelAclMaskException($mask, $object, $permission, $maskAccessLevels);
            }
        }
    }
}
