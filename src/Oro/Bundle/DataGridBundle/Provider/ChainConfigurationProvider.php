<?php

namespace Oro\Bundle\DataGridBundle\Provider;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Exception\RuntimeException;

/**
 * Delegates the loading of datagrid configuration to child providers.
 */
class ChainConfigurationProvider implements ConfigurationProviderInterface
{
    /** @var ConfigurationProviderInterface[] */
    private $providers = [];

    /**
     * Registers the given provider in the chain
     *
     * @param ConfigurationProviderInterface $provider
     */
    public function addProvider(ConfigurationProviderInterface $provider)
    {
        $this->providers[] = $provider;
    }

    /**
     * {@inheritDoc}
     */
    public function isApplicable(string $gridName): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getConfiguration(string $gridName): DatagridConfiguration
    {
        $foundProvider = null;
        foreach ($this->providers as $provider) {
            if ($provider->isApplicable($gridName)) {
                $foundProvider = $provider;
                break;
            }
        }

        if ($foundProvider === null) {
            throw new RuntimeException(sprintf('A configuration for "%s" datagrid was not found.', $gridName));
        }

        return $foundProvider->getConfiguration($gridName);
    }

    /**
     * @return ConfigurationProviderInterface[]
     */
    public function getProviders()
    {
        return $this->providers;
    }
}
