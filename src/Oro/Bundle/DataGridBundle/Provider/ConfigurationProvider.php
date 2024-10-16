<?php

namespace Oro\Bundle\DataGridBundle\Provider;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Exception\RuntimeException;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;

/**
 * The provider for datagrids configuration
 * that is loaded from "Resources/config/oro/datagrids.yml" files.
 */
class ConfigurationProvider implements ConfigurationProviderInterface
{
    /** @var RawConfigurationProviderInterface */
    private $rawConfigurationProvider;

    /** @var SystemAwareResolver */
    private $resolver;

    /** @var array */
    private $processedConfiguration = [];

    public function __construct(
        RawConfigurationProviderInterface $rawConfigurationProvider,
        SystemAwareResolver $resolver
    ) {
        $this->rawConfigurationProvider = $rawConfigurationProvider;
        $this->resolver = $resolver;
    }

    #[\Override]
    public function isApplicable(string $gridName): bool
    {
        return null !== $this->rawConfigurationProvider->getRawConfiguration($gridName);
    }

    #[\Override]
    public function getConfiguration(string $gridName): DatagridConfiguration
    {
        if (!isset($this->processedConfiguration[$gridName])) {
            $rawConfig = $this->rawConfigurationProvider->getRawConfiguration($gridName);
            if (null === $rawConfig) {
                throw new RuntimeException(\sprintf(
                    'A configuration for "%s" datagrid was not found.',
                    $gridName
                ));
            }
            $this->processedConfiguration[$gridName] = $this->resolver->resolve($gridName, $rawConfig);
        }

        return DatagridConfiguration::createNamed(
            $gridName,
            $this->processedConfiguration[$gridName],
            PropertyAccess::createPropertyAccessorWithDotSyntax()
        );
    }

    #[\Override]
    public function isValidConfiguration(string $gridName): bool
    {
        try {
            $this->getConfiguration($gridName);
        } catch (\Throwable $e) {
            return false;
        }

        return true;
    }
}
