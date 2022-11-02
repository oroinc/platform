<?php

namespace Oro\Bundle\EntityBundle\Fallback\Provider;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

/**
 * Abstract class for the provider which provides entity fallback.
 */
abstract class AbstractEntityFallbackProvider implements EntityFallbackProviderInterface
{
    /** @var ConfigProvider */
    protected $configProvider;

    /** @var array */
    protected $entityConfigs = [];

    /**
     * {@inheritdoc}
     */
    public function isFallbackSupported($object, $objectFieldName)
    {
        return true;
    }

    public function setConfigProvider(ConfigProvider $provider)
    {
        $this->configProvider = $provider;
    }

    /**
     * @param object $object
     * @param string $objectFieldName
     * @return array
     */
    public function getEntityConfig($object, $objectFieldName)
    {
        $class = get_class($object);
        if (!isset($this->entityConfigs[$class][$objectFieldName])) {
            $this->entityConfigs[$class][$objectFieldName] = $this->configProvider
                ->getConfig($class, $objectFieldName)
                ->getValues();
        }

        return $this->entityConfigs[$class][$objectFieldName];
    }
}
