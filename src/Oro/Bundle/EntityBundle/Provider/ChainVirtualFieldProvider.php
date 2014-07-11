<?php

namespace Oro\Bundle\EntityBundle\Provider;

class ChainVirtualFieldProvider implements VirtualFieldProviderInterface
{
    /**
     * @var array[]
     */
    protected $providers = [];

    /**
     * @var VirtualFieldProviderInterface[]
     */
    protected $sorted;

    /**
     * Registers the given provider in the chain
     *
     * @param VirtualFieldProviderInterface $provider
     * @param integer                       $priority
     */
    public function addProvider(VirtualFieldProviderInterface $provider, $priority = 0)
    {
        $this->providers[$priority][] = $provider;
        $this->sorted                 = null;
    }

    /**
     * {@inheritdoc}
     */
    public function isVirtualField($className, $fieldName)
    {
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
        $providers = $this->getProviders();
        $result    = array();

        foreach ($providers as $provider) {
            $virtualFields = $provider->getVirtualFields($className);

            if (!empty($virtualFields)) {
                foreach ($virtualFields as $fieldName) {
                    $result[] = $fieldName;
                }
            }
        }

        return $result;
    }

    /**
     * Sorts the internal list of providers by priority.
     *
     * @return VirtualFieldProviderInterface[]
     */
    protected function getProviders()
    {
        if (null === $this->sorted) {
            ksort($this->providers);
            $this->sorted = !empty($this->providers)
                ? call_user_func_array('array_merge', $this->providers)
                : [];
        }

        return $this->sorted;
    }
}
