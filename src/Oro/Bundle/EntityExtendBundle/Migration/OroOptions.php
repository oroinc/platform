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
     * @param string      $scope
     * @param string|null $code
     * @return bool
     */
    public function has($scope, $code = null)
    {
        if (isset($this->options[$scope])) {
            return
                null === $code ||
                isset($this->options[$scope][$code]) ||
                array_key_exists($code, $this->options[$scope]);
        }

        return false;
    }

    /**
     * Gets the value of the given attribute
     *
     * @param string $scope
     * @param string $code
     * @return mixed|null The attribute value or NULL if this class does not contain the attribute
     */
    public function get($scope, $code)
    {
        return isset($this->options[$scope][$code])
            ? $this->options[$scope][$code]
            : null;
    }

    /**
     * Sets the value of the given attribute
     *
     * This method replaces the previous value with the new one
     *
     * @param string $scope
     * @param string $code
     * @param mixed  $val
     */
    public function set($scope, $code, $val)
    {
        if (!isset($this->options)) {
            $this->options[$scope] = [];
        }
        $this->options[$scope][$code] = $val;
    }

    /**
     * Sets the value of auxiliary option
     *
     * @param string $name
     * @param string $val
     */
    public function setAuxiliary($name, $val)
    {
        $this->options[$name] = $val;
    }

    /**
     * Merges new value with old value. The attribute type should be an array
     *
     * @param string $scope
     * @param string $code
     * @param mixed  $val
     */
    public function append($scope, $code, $val)
    {
        if (!isset($this->options)) {
            $this->options[$scope] = [];
        }
        if ($this->has($scope, $code)) {
            $this->options[$scope][$code] = array_merge(
                $this->options[$scope][$code],
                is_array($val) ? $val : [$val]
            );
        } else {
            $this->options[$scope][$code] = is_array($val) ? $val : [$val];
        }
        $this->markAsAppended($scope, $code);
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
     * @param string $code
     */
    protected function markAsAppended($scope, $code)
    {
        if (!isset($this->options[self::APPEND_SECTION])) {
            $this->options[self::APPEND_SECTION] = [];
        }
        if (!isset($this->options[self::APPEND_SECTION][$scope])) {
            $this->options[self::APPEND_SECTION][$scope] = [];
        }
        if (!in_array($code, $this->options[self::APPEND_SECTION][$scope])) {
            $this->options[self::APPEND_SECTION][$scope][] = $code;
        }
    }
}
