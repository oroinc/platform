<?php

namespace Oro\Bundle\EntityConfigBundle\Provider;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;

/**
 * @deprecated since 1.9. Use {@see Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider} instead
 */
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
     * @param callable    $callback The callback function to use
     * @param string|null $className
     * @param bool        $withHidden
     *
     * @return array|\Oro\Bundle\EntityConfigBundle\Config\ConfigInterface[]
     */
    public function filter($callback, $className = null, $withHidden = false);

    /**
     * Gets configuration data for the given class or field.
     *
     * @param string      $className
     * @param string|null $fieldName
     * @return ConfigInterface
     */
    public function getConfig($className, $fieldName = null);

    /**
     * Determines if this provider has configuration data for the given class or field.
     *
     * @param string      $className
     * @param string|null $fieldName
     * @return bool
     */
    public function hasConfig($className, $fieldName = null);
}
