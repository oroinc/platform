<?php

namespace Oro\Bundle\EntityConfigBundle\Layout\DataProvider;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider as Provider;

class ConfigProvider
{
    /** @var Provider */
    protected $configProvider;

    /**
     * @param Provider $configProvider
     */
    public function __construct(Provider $configProvider)
    {
        $this->configProvider = $configProvider;
    }

    public function getConfig($entity, $fieldName)
    {
        return $this->configProvider->getConfig($entity, $fieldName);
    }
}
