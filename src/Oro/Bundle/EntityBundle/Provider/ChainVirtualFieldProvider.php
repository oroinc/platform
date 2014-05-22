<?php

namespace Oro\Bundle\EntityBundle\Provider;

class ChainVirtualFieldProvider implements VirtualFieldProviderInterface
{
    /**
     * @var VirtualFieldProviderInterface[]
     */
    protected $providers = [];

    /**
     * Registers the given provider in the chain
     *
     * @param VirtualFieldProviderInterface $provider
     */
    public function addProvider(VirtualFieldProviderInterface $provider)
    {
        $this->providers[] = $provider;
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
        $foundProvider = null;
        foreach ($this->providers as $provider) {
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
}
