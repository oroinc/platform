<?php

namespace Oro\Bundle\EntityBundle\Fallback\Provider;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

abstract class AbstractEntityFallbackProvider implements EntityFallbackProviderInterface
{
    /** @var ConfigProvider */
    protected $configProvider;

    /**
     * {@inheritdoc}
     */
    public function isFallbackSupported($object, $objectFieldName)
    {
        return true;
    }

    /**
     * @param ConfigProvider $provider
     */
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
        return $this->configProvider
            ->getConfig(get_class($object), $objectFieldName)
            ->getValues();
    }
}
