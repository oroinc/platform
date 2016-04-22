<?php

namespace Oro\Bundle\DataGridBundle\Provider;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Exception\RuntimeException;

class ConfigurationProvider implements ConfigurationProviderInterface
{
    /** @var array */
    protected $rawConfiguration;

    /** @var SystemAwareResolver */
    protected $resolver;

    /** @var array */
    protected $processedConfiguration = [];

    /**
     * Constructor
     *
     * @param array               $rawConfiguration
     * @param SystemAwareResolver $resolver
     */
    public function __construct(array $rawConfiguration, SystemAwareResolver $resolver)
    {
        $this->rawConfiguration = $rawConfiguration;
        $this->resolver         = $resolver;
    }

    /**
     * {@inheritDoc}
     */
    public function isApplicable($gridName)
    {
        return isset($this->rawConfiguration[$gridName]);
    }

    /**
     * {@inheritDoc}
     */
    public function getConfiguration($gridName)
    {
        $rawConfiguration = $this->getRawConfiguration($gridName);
        if (!isset($this->processedConfiguration[$gridName])) {
            $config = $this->resolver->resolve($gridName, $rawConfiguration);

            $this->processedConfiguration[$gridName] = $config;
        }

        return DatagridConfiguration::createNamed($gridName, $this->processedConfiguration[$gridName]);
    }

    /**
     * @param string $gridName
     *
     * @return array
     */
    public function getRawConfiguration($gridName)
    {
        if (!isset($this->rawConfiguration[$gridName])) {
            throw new RuntimeException(sprintf('A configuration for "%s" datagrid was not found.', $gridName));
        }

        return $this->rawConfiguration[$gridName];
    }
}
