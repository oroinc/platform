<?php

namespace Oro\Bundle\EntityConfigBundle\Layout\DataProvider;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider as Provider;

/**
 * The configuration provider can be used to get configuration data inside particular configuration scope.
 */
class ConfigProvider
{
    /** @var Provider */
    protected $configProvider;

    public function __construct(Provider $configProvider)
    {
        $this->configProvider = $configProvider;
    }

    /**
     * Gets configuration data for the given entity or field.
     *
     * @param string      $className
     * @param string|null $fieldName
     * @return ConfigInterface
     */
    public function getConfig($className, $fieldName = null): ConfigInterface
    {
        return $this->configProvider->getConfig($className, $fieldName);
    }
}
