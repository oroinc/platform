<?php

namespace Oro\Bundle\EntityConfigBundle\Config;

use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Exception\RuntimeException;

interface ConfigInterface extends \Serializable
{
    /**
     * Returns id of an object for which an instance of this class stores configuration data.
     *
     * @return ConfigIdInterface
     */
    public function getId();

    /**
     * Gets a value of a configuration attribute.
     *
     * @param string     $code    The code (name) a configuration attribute
     * @param bool       $strict  Set to true if this method must raise an exception
     *                            when the requested attribute does not exist
     * @param mixed|null $default Will return default value if code does not exist and $strict == false
     *
     * @return mixed|null The attribute value of null if the requested attribute does not exist and $strict = false
     * @throws RuntimeException When $strict = true and the requested attribute does not exist
     */
    public function get($code, $strict = false, $default = null);

    /**
     * Sets a value of the given configuration attribute.
     *
     * @param string $code
     * @param mixed  $value
     * @return $this
     */
    public function set($code, $value);

    /**
     * Removes the given configuration attribute.
     *
     * @param string $code
     * @return $this
     */
    public function remove($code);

    /**
     * Checks whether a configuration attribute with the given code exists on not.
     *
     * @param string $code
     * @return bool
     */
    public function has($code);

    /**
     * Checks id a value of a configuration attribute equals to $value.
     *
     * @param string $code
     * @param mixed  $value
     * @return bool
     */
    public function is($code, $value = true);

    /**
     * Checks if a config value exists in $values array
     *
     * @param string $code
     * @param array  $values
     * @param bool   $strict If this attribute is set to TRUE then this method also check the types of a config value
     *                       and items in $values array.
     * @return bool
     */
    public function in($code, array $values, $strict = false);

    /**
     * Returns configuration attributes is filtered using the given callback function.
     * Returns all configuration attributes if $filter argument is not specified.
     *
     * @param callable|null $filter The callback function to be used to filter attributes
     * @return array
     */
    public function all(\Closure $filter = null);

    /**
     * Returns all configuration attributes.
     *
     * @return array
     */
    public function getValues();

    /**
     * Replace all configuration attributes with attributes specified in $values argument.
     *
     * @param array $values
     * @return $this
     */
    public function setValues($values);
}
