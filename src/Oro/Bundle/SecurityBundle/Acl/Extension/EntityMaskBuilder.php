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
     * all access levels (basic, local, deep, global, system). So 15 bits used for permissions data. The remaining 16
     * bits will be used as service bits (as we know, 31 bits we can use for data and 1 bit is used for sign) and we
     * have 65536 (2^16) unique combinations of masks.
     * Current constant value was selected based on optimal numbers of unique masks and better performance.
     */
    const MAX_PERMISSIONS_IN_MASK = 3;

    const SERVICE_BITS            = -32768;
    const REMOVE_SERVICE_BITS     = 32767;

    /** @var int */
    protected $identity;

    /** @var array */
    protected $map;

    /**
     * @param int $identity
     * @param array $permissions
     */
    public function __construct($identity, array $permissions)
    {
        if (!is_int($identity)) {
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
        $this->preparePermissions($permissions);
        $this->prepareLevels();

        parent::__construct();
    }

    /**
     * @param array $permissions
     */
    protected function preparePermissions(array $permissions)
    {
        $identity = $this->getIdentity();
        $levelsCount = count(AccessLevel::$allAccessLevelNames);

        foreach ($permissions as $permissionKey => $permission) {
            $permissionSum = 0;

            foreach (AccessLevel::$allAccessLevelNames as $accessLevelKey => $accessLevel) {
                $name = sprintf('MASK_%s_%s', $permission, $accessLevel);
                $mask = 1 << ($accessLevelKey + ($permissionKey * $levelsCount));

                $permissionSum += $mask;

                $this->map[$name] = $mask + $identity;
            }

            $this->map['GROUP_' . $permission] = $permissionSum + $identity;
        }
    }

    protected function prepareLevels()
    {
        $levelSums = array_map(
            function () {
                return 0;
            },
            array_flip(AccessLevel::$allAccessLevelNames)
        );

        $levelsCount = count(AccessLevel::$allAccessLevelNames);

        for ($i = 0; $i < self::MAX_PERMISSIONS_IN_MASK; $i++) {
            foreach (AccessLevel::$allAccessLevelNames as $accessLevelKey => $accessLevel) {
                $levelSums[$accessLevel] += (1 << ($accessLevelKey + ($i * $levelsCount)));
            }
        }

        $identity = $this->getIdentity();

        $this->map['GROUP_NONE'] = $identity;
        $this->map['GROUP_ALL'] = $identity;

        foreach ($levelSums as $level => $sum) {
            $this->map['GROUP_' . $level] = $sum + $identity;
            $this->map['GROUP_ALL'] += $sum;
        }
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
        $this->mask = $this->getIdentity();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function add($mask)
    {
        if (is_string($mask)) {
            $mask = $this->getMask('MASK_' . strtoupper($mask));
        } elseif (!is_int($mask)) {
            throw new \InvalidArgumentException('$mask must be a string or an integer.');
        }

        $mask &= self::REMOVE_SERVICE_BITS;
        $this->mask |= $mask;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($mask)
    {
        if (is_string($mask)) {
            $mask = $this->getMask('MASK_' . strtoupper($mask));
        } elseif (!is_int($mask)) {
            throw new \InvalidArgumentException('$mask must be a string or an integer.');
        }

        $this->mask &= ~$mask;
        $this->mask |= $this->getIdentity();

        return $this;
    }

    /**
     * Checks whether a permission with the given name is defined in this mask builder
     *
     * @param string $name
     * @return bool
     */
    public function hasMask($name)
    {
        return array_key_exists($name, $this->map);
    }

    /**
     * Gets permission value by its name
     *
     * @param string $name
     * @return int
     */
    public function getMask($name)
    {
        if (!array_key_exists($name, $this->map)) {
            throw new \InvalidArgumentException(sprintf('Undefined mask: %s.', $name));
        }

        return $this->map[$name];
    }
}
