<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * Delegates building of virtual fields to child providers.
 */
class ChainVirtualFieldProvider implements VirtualFieldProviderInterface
{
    /** @var iterable|VirtualFieldProviderInterface[] */
    private $providers;

    /** @var ConfigProvider  */
    private $configProvider;

    /**
     * @param iterable|VirtualFieldProviderInterface[] $providers
     * @param ConfigProvider                           $configProvider
     */
    public function __construct(iterable $providers, ConfigProvider $configProvider)
    {
        $this->providers = $providers;
        $this->configProvider = $configProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function isVirtualField($className, $fieldName)
    {
        foreach ($this->providers as $provider) {
            if ($provider->isVirtualField($className, $fieldName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getVirtualFieldQuery($className, $fieldName)
    {
        foreach ($this->providers as $provider) {
            if ($provider->isVirtualField($className, $fieldName)) {
                return $provider->getVirtualFieldQuery($className, $fieldName);
            }
        }

        throw new \RuntimeException(sprintf(
            'A query for field "%s" in class "%s" was not found.',
            $fieldName,
            $className
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getVirtualFields($className)
    {
        if (!$this->isEntityAccessible($className)) {
            return [];
        }

        $result = [];
        foreach ($this->providers as $provider) {
            $virtualFields = $provider->getVirtualFields($className);
            if (!empty($virtualFields)) {
                foreach ($virtualFields as $fieldName) {
                    $result[$fieldName] = true;
                }
            }
        }

        return array_keys($result);
    }

    /**
     * @param string $className
     *
     * @return bool
     */
    private function isEntityAccessible(string $className): bool
    {
        return
            !$this->configProvider->hasConfig($className)
            || ExtendHelper::isEntityAccessible($this->configProvider->getConfig($className));
    }
}
