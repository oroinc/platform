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
     * {@inheritDoc}
     */
    public function isVirtualField($className, $fieldName)
    {
        // TODO: Implement isVirtualField() method.
    }

    /**
     * {@inheritDoc}
     */
    public function getVirtualFieldQuery($className, $fieldName)
    {
        // TODO: Implement getVirtualFieldQuery() method.
    }

    /**
     * Returns virtual
     *
     * @return array
     */
    public function getVirtualFields()
    {
        // TODO: Implement getVirtualFields() method.
    }

    public function getConfiguration($gridName)
    {
        $foundProvider = null;
        foreach ($this->providers as $provider) {
            if ($provider->isApplicable($gridName)) {
                $foundProvider = $provider;
                break;
            }
        }

        if ($foundProvider === null) {
            throw new \RuntimeException(sprintf('A configuration for "%s" datagrid was not found.', $gridName));
        }

        return $foundProvider->getConfiguration($gridName);
    }
}
