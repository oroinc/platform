<?php

namespace Oro\Bundle\ApiBundle\Config;

/**
 * An interface for configuration sections that can have custom options (options without own getter and setter).
 */
interface ConfigBagInterface
{
    /**
     * Indicates whether the configuration attribute exists.
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
     * @param mixed  $defaultValue
     *
     * @return mixed
     */
    public function get($key, $defaultValue = null);

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
     * Gets names of all configuration attributes.
     *
     * @return string[]
     */
    public function keys();
}
