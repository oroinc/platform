<?php

namespace Oro\Bundle\EntityConfigBundle\Provider;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;

interface ConfigProviderInterface
{
    /**
     * Gets the name of the scope this provider works with.
     *
     * @return string
     */
    public function getScope();

    /**
     * @param ConfigIdInterface $configId
     * @return ConfigInterface
     */
    public function getConfigById(ConfigIdInterface $configId);

    /**
     * Filters configuration data of all classes (if $className is not specified)
     * or all fields of the given $className using the given callback function.
     *
     * @param callable $callback The callback function to use
     * @param string|null $className
     * @return array|ConfigInterface[]
     */
    public function filter($callback, $className = null);

    /**
     * Gets configuration data for the given class or field.
     *
     * @param string      $className
     * @param string|null $fieldName
     * @return ConfigInterface
     */
    public function getConfig($className, $fieldName = null);
}
