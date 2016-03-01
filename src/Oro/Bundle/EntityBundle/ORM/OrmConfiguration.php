<?php

namespace Oro\Bundle\EntityBundle\ORM;

use Doctrine\ORM\Configuration;

/**
 * The goal of this class id to allow to use any custom ORM configuration attribute
 * without extending the Configuration class each time when a new custom attribute is required.
 * This should improve horizontal extendability of ORO Platform.
 */
class OrmConfiguration extends Configuration
{
    /**
     * Gets a value of a configuration attribute
     *
     * @param string $name    The name of an configuration attribute
     * @param mixed  $default A value that should be returned if a requested attribute does not exist
     *
     * @return null
     */
    public function getAttribute($name, $default = null)
    {
        return isset($this->_attributes[$name])
            ? $this->_attributes[$name]
            : $default;
    }

    /**
     * Sets a value of a configuration attribute
     *
     * @param string $name  The name of an configuration attribute
     * @param mixed  $value The value of an configuration attribute
     *
     * @return null
     */
    public function setAttribute($name, $value)
    {
        $this->_attributes[$name] = $value;
    }
}
