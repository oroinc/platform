<?php

namespace Oro\Bundle\EntityBundle\Provider;

class ChainVirtualFieldProvider extends AbstractChainProvider implements VirtualFieldProviderInterface
{
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
}
