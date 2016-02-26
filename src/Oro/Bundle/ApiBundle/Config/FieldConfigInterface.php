<?php

namespace Oro\Bundle\ApiBundle\Config;

interface FieldConfigInterface
{
    /**
     * Checks whether the configuration attribute exists.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has($key);

    /**
     * Gets the configuration value.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get($key);

    /**
     * Sets the configuration value.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function set($key, $value);

    /**
     * Removes the configuration value.
     *
     * @param string $key
     */
    public function remove($key);

    /**
     * Indicates whether the exclusion flag is set explicitly.
     *
     * @return bool
     */
    public function hasExcluded();

    /**
     * Indicates whether the field should be excluded.
     *
     * @return bool
     */
    public function isExcluded();

    /**
     * Sets a flag indicates whether the field should be excluded.
     *
     * @param bool $exclude
     */
    public function setExcluded($exclude = true);

    /**
     * Indicates whether the path of the field value exists.
     *
     * @return string
     */
    public function hasPropertyPath();

    /**
     * Gets the path of the field value.
     *
     * @return string|null
     */
    public function getPropertyPath();

    /**
     * Sets the path of the field value.
     *
     * @param string|null $propertyPath
     */
    public function setPropertyPath($propertyPath = null);
}
