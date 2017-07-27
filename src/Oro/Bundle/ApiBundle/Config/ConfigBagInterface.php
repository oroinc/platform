<?php

namespace Oro\Bundle\ApiBundle\Config;

interface ConfigBagInterface
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
     * Gets names of all configuration attributes.
     *
     * @return string[]
     */
    public function keys();
}
