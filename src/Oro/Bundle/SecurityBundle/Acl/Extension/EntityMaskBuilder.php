<?php

namespace Oro\Bundle\SecurityBundle\Acl\Extension;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Permission\MaskBuilder;

/**
 * The permission mask builder for 'Entity' ACL extension.
 */
final class EntityMaskBuilder extends MaskBuilder
{
    /**
     * Determines how many permissions stored in one mask.
     * For service mask is used 32 bit integer value. We use five bits for each permissions in mask to store
     * all access levels (basic, local, deep, global, system). 25 bits used for permissions data. The remaining 6
     * bits will be used as service bits (as we know, 31 bits we can use for data and 1 bit is used for sign) and we
     * have 320 ((2^6)*5) unique permissions.
     * Current constant value was selected based on optimal numbers of unique permissions and better performance.
     */
    public const MAX_PERMISSIONS_IN_MASK = 5;

    public const SERVICE_BITS        = -33554432; // 0xFE000000
    public const REMOVE_SERVICE_BITS = 33554431;  // 0x01FFFFFF

    /** @var int */
    private $identity;

    /**
     * @param int   $identity
     * @param array $permissions [permission key => permission, ...]
     */
    public function __construct($identity, array $permissions)
    {
        if (!\is_int($identity)) {
            throw new \InvalidArgumentException(sprintf('Identity should be integer, %s given.', $identity));
        }

        if ($identity < 0) {
            throw new \InvalidArgumentException(sprintf('Identity should be greater than zero, %d given.', $identity));
        }

        $permissionsCount = count($permissions);
        if ($permissionsCount < 1 || $permissionsCount > self::MAX_PERMISSIONS_IN_MASK) {
            throw new \InvalidArgumentException(
                sprintf('Permissions count should be from 1 to %d.', self::MAX_PERMISSIONS_IN_MASK)
            );
        }

        $this->identity = $identity;
        parent::__construct();

        $this->preparePermissions($permissions);
        $this->prepareLevels();
    }

    /**
     * @param array $permissions [permission key => permission, ...]
     */
    private function preparePermissions(array $permissions)
    {
        $identity = $this->getIdentity();
        $levelsCount = count(AccessLevel::$allAccessLevelNames);
        foreach ($permissions as $permissionKey => $permission) {
            $permissionSum = 0;
            foreach (AccessLevel::$allAccessLevelNames as $accessLevelKey => $accessLevel) {
                $permissionName = $permission . '_' . $accessLevel;
                $mask = 1 << ($accessLevelKey + ($permissionKey * $levelsCount));
                $permissionSum += $mask;
                $mask += $identity;
                $this->map->permission[$permissionName] = $mask;
                $this->map->all['MASK_' . $permissionName] = $mask;
            }
            $mask = $permissionSum + $identity;
            $this->map->group[$permission] = $mask;
            $this->map->all['GROUP_' . $permission] = $mask;
        }
    }

    private function prepareLevels()
    {
        $levelSums = [];
        foreach (AccessLevel::$allAccessLevelNames as $accessLevel) {
            $levelSums[$accessLevel] = 0;
        }
        $levelsCount = count(AccessLevel::$allAccessLevelNames);
        for ($i = 0; $i < self::MAX_PERMISSIONS_IN_MASK; $i++) {
            foreach (AccessLevel::$allAccessLevelNames as $accessLevelKey => $accessLevel) {
                $levelSums[$accessLevel] += (1 << ($accessLevelKey + ($i * $levelsCount)));
            }
        }

        $identity = $this->getIdentity();
        $this->map->group['NONE'] = $identity;
        $this->map->all['GROUP_NONE'] = $identity;
        $allMask = $identity;
        foreach ($levelSums as $level => $sum) {
            $mask = $sum + $identity;
            $this->map->group[$level] = $mask;
            $this->map->all['GROUP_' . $level] = $mask;
            $allMask += $sum;
        }
        $this->map->group['ALL'] = $allMask;
        $this->map->all['GROUP_ALL'] = $allMask;
    }

    /**
     * @return int
     */
    public function getIdentity()
    {
        return $this->identity;
    }

    /**
     * {@inheritdoc}
     */
    public function reset()
    {
        $this->mask = $this->identity;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function add($mask)
    {
        $this->mask |= $this->parseMask($mask) & self::REMOVE_SERVICE_BITS;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($mask)
    {
        $this->mask = ($this->mask & ~$this->parseMask($mask)) | $this->getIdentity();

        return $this;
    }
}
