<?php

namespace Oro\Bundle\ConfigBundle\Config;

/**
 * The aim of this class is to share values cached in a memory between different instances of config managers
 */
class ConfigValueBag
{
    /** @var array */
    protected $values = [];

    /**
     * @param string $scope
     * @param string $scopeId
     * @param string $name
     *
     * @return bool
     */
    public function hasValue($scope, $scopeId, $name)
    {
        return isset($this->values[$scope][$scopeId][$name]);
    }

    /**
     * @param string $scope
     * @param string $scopeId
     * @param string $name
     *
     * @return mixed
     */
    public function getValue($scope, $scopeId, $name)
    {
        return $this->values[$scope][$scopeId][$name];
    }

    /**
     * @param string $scope
     * @param string $scopeId
     * @param string $name
     * @param mixed  $value
     */
    public function setValue($scope, $scopeId, $name, $value)
    {
        $this->values[$scope][$scopeId][$name] = $value;
    }

    /**
     * @param string $scope
     * @param string $scopeId
     * @param string $name
     */
    public function removeValue($scope, $scopeId, $name)
    {
        unset($this->values[$scope][$scopeId][$name]);
    }

    /**
     * Removes all values in all scopes
     */
    public function clear()
    {
        $this->values = [];
    }
}
