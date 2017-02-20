<?php

namespace Oro\Bundle\SecurityBundle\Model;

use Doctrine\ORM\Mapping as ORM;

class ConfigurablePermission
{
    /** @var string */
    private $name;

    /** @var array[]|bool */
    private $entities;

    /** @var array */
    private $capabilities = [];

    /** @var array[]|bool */
    private $workflows;

    /** @var bool */
    private $default;

    /**
     * @param string $name
     * @param bool $default
     * @param array[]|bool $entities
     * @param array $capabilities
     * @param array[]|bool $workflows
     */
    public function __construct(
        $name,
        $default = true,
        $entities = [],
        array $capabilities = [],
        $workflows = []
    ) {
        $this->name = $name;
        $this->default = (bool)$default;
        $this->entities = $entities;
        $this->capabilities = $capabilities;
        $this->workflows = $workflows;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $entityClass
     * @param string $permission
     * @return bool
     */
    public function isEntityPermissionConfigurable($entityClass, $permission)
    {
        if (!isset($this->entities[$entityClass])) {
            return (bool) $this->default;
        }
        $permissions = $this->entities[$entityClass];
        // if boolean value - it using for all permissions
        if (is_bool($permissions)) {
            return $permissions;
        }

        return (bool) isset($permissions[$permission]) ? $permissions[$permission] : $this->default;
    }

    /**
     * @param string $capability
     * @return bool
     */
    public function isCapabilityConfigurable($capability)
    {
        $capabilities = $this->capabilities ?: [];

        return (bool) isset($capabilities[$capability]) ? $capabilities[$capability] : $this->default;
    }

    /**
     * @param string $identity
     * @param string $permission
     * @return bool
     */
    public function isWorkflowPermissionConfigurable($identity, $permission)
    {
        if (!isset($this->workflows[$identity])) {
            return (bool) $this->default;
        }
        $permissions = $this->workflows[$identity];
        // if boolean value - it using for all permissions
        if (is_bool($permissions)) {
            return $permissions;
        }

        return (bool) isset($permissions[$permission]) ? $permissions[$permission] : $this->default;
    }
}
