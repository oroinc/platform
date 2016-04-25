<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

/**
 * This class is intended to help modification of entity configuration attributes.
 */
class OroOptions
{
    const KEY = 'oro_options';

    const APPEND_SECTION = '_append';

    /** @var array */
    private $options;

    /**
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    /**
     * Indicates whether this class contains data for the given scope or attribute
     *
     * @param string      $scope        The name of the scope
     * @param string|null $propertyPath The name or path of an attribute
     *                                  Names inside a path should be delimited by the dot character (.)
     *
     * @return bool
     */
    public function has($scope, $propertyPath = null)
    {
        if (isset($this->options[$scope])) {
            return
                null === $propertyPath ||
                isset($this->options[$scope][$propertyPath]) ||
                array_key_exists($propertyPath, $this->options[$scope]);
        }

        return false;
    }

    /**
     * Gets the value of the given attribute
     *
     * @param string $scope        The name of the scope
     * @param string $propertyPath The name or path of an attribute
     *                             Names inside a path should be delimited by the dot character (.)
     *
     * @return mixed|null The attribute value or NULL if this class does not contain the attribute
     */
    public function get($scope, $propertyPath)
    {
        return isset($this->options[$scope][$propertyPath])
            ? $this->options[$scope][$propertyPath]
            : null;
    }

    /**
     * Sets the value of the given attribute
     *
     * This method replaces the previous value with the new one
     *
     * @param string $scope        The name of the scope
     * @param string $propertyPath The name or path of an attribute
     *                             Names inside a path should be delimited by the dot character (.)
     * @param mixed  $val          A value to be set
     */
    public function set($scope, $propertyPath, $val)
    {
        if (!isset($this->options)) {
            $this->options[$scope] = [];
        }
        $this->options[$scope][$propertyPath] = $val;
    }

    /**
     * Sets the value of auxiliary option
     *
     * @param string $name The name of auxiliary option. Usually such names start with underscore (_).
     * @param string $val  A value of auxiliary option
     */
    public function setAuxiliary($name, $val)
    {
        $this->options[$name] = $val;
    }

    /**
     * Merges new value with old value. The attribute type should be an array
     *
     * @param string $scope        The name of the scope
     * @param string $propertyPath The name or path of an attribute
     *                             Names inside a path should be delimited by the dot character (.)
     * @param mixed  $val          A value to be added to already existing value
     */
    public function append($scope, $propertyPath, $val)
    {
        if (!isset($this->options)) {
            $this->options[$scope] = [];
        }
        if ($this->has($scope, $propertyPath)) {
            $this->options[$scope][$propertyPath] = array_merge(
                $this->options[$scope][$propertyPath],
                is_array($val) ? $val : [$val]
            );
        } else {
            $this->options[$scope][$propertyPath] = is_array($val) ? $val : [$val];
        }
        $this->markAsAppended($scope, $propertyPath);
    }

    /**
     * Converts this object to an array
     *
     * @return array
     */
    public function toArray()
    {
        return $this->options;
    }

    /**
     * @param string $scope
     * @param string $propertyPath
     */
    protected function markAsAppended($scope, $propertyPath)
    {
        if (!isset($this->options[self::APPEND_SECTION])) {
            $this->options[self::APPEND_SECTION] = [];
        }
        if (!isset($this->options[self::APPEND_SECTION][$scope])) {
            $this->options[self::APPEND_SECTION][$scope] = [];
        }
        if (!in_array($propertyPath, $this->options[self::APPEND_SECTION][$scope], true)) {
            $this->options[self::APPEND_SECTION][$scope][] = $propertyPath;
        }
    }
}
