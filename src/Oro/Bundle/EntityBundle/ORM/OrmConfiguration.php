<?php

namespace Oro\Bundle\EntityBundle\ORM;

use Doctrine\ORM\Configuration;

/**
 * The goal of this class is to provide a possibility to use any custom ORM configuration attributes
 * without extending the Configuration class each time when a new custom attribute is required.
 */
class OrmConfiguration extends Configuration
{
    /**
     * Gets a value of a configuration attribute.
     *
     * @param string $name    The name of an configuration attribute
     * @param mixed  $default A value that should be returned if a requested attribute does not exist
     *
     * @return mixed
     */
    public function getAttribute($name, $default = null)
    {
        return $this->_attributes[$name] ?? $default;
    }

    /**
     * Sets a value of a configuration attribute.
     *
     * @param string $name  The name of an configuration attribute
     * @param mixed  $value The value of an configuration attribute
     */
    public function setAttribute($name, $value)
    {
        $this->_attributes[$name] = $value;
    }
}
