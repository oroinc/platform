<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * Delegates building of virtual fields to child providers.
 */
class ChainVirtualFieldProvider extends AbstractChainProvider implements VirtualFieldProviderInterface
{
    /** @var ConfigProvider  */
    private $configProvider;

    /**
     * @param ConfigProvider $configProvider
     */
    public function __construct(ConfigProvider $configProvider)
    {
        $this->configProvider = $configProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function isVirtualField($className, $fieldName)
    {
        /** @var VirtualFieldProviderInterface[] $providers */
        $providers = $this->getProviders();
        foreach ($providers as $provider) {
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
        $foundProvider = null;
        /** @var VirtualFieldProviderInterface[] $providers */
        $providers = $this->getProviders();
        foreach ($providers as $provider) {
            if ($provider->isVirtualField($className, $fieldName)) {
                $foundProvider = $provider;
                break;
            }
        }

        if ($foundProvider === null) {
            throw new \RuntimeException(
                sprintf(
                    'A query for field "%s" in class "%s" was not found.',
                    $fieldName,
                    $className
                )
            );
        }

        return $foundProvider->getVirtualFieldQuery($className, $fieldName);
    }

    /**
     * {@inheritdoc}
     */
    public function getVirtualFields($className)
    {
        if (!$this->isEntityAccessible($className)) {
            return [];
        }

        /** @var VirtualFieldProviderInterface[] $providers */
        $providers = $this->getProviders();
        $result    = array();

        foreach ($providers as $provider) {
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
