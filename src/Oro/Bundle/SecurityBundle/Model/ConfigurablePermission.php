<?php

namespace Oro\Bundle\SecurityBundle\Model;

use Doctrine\ORM\Mapping as ORM;

class ConfigurablePermission
{
    /** @var string */
    private $name;

    /** @var array[] */
    private $entities = [];

    /** @var array */
    private $capabilities = [];

    /** @var array */
    private $workflows = [];

    /** @var bool */
    private $default;

    /**
     * @param string $name
     * @param bool $default
     * @param array $entities
     * @param array $capabilities
     * @param array $workflows
     */
    public function __construct(
        $name,
        $default = false,
        array $entities = [],
        array $capabilities = [],
        array $workflows = []
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
        $permissions = isset($this->entities[$entityClass]) ? $this->entities[$entityClass] : [];

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
        $permissions = isset($this->workflows[$identity]) ? $this->workflows[$identity] : [];

        return (bool) isset($permissions[$permission]) ? $permissions[$permission] : $this->default;
    }
}
