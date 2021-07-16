<?php

namespace Oro\Bundle\ActionBundle\Configuration;

/**
 * The provider for a specific root section of configuration
 * that is loaded from "Resources/config/oro/actions.yml" files.
 */
class SectionConfigurationProvider implements ConfigurationProviderInterface
{
    /** @var ConfigurationProviderInterface */
    private $configurationProvider;

    /** @var string */
    private $sectionName;

    public function __construct(ConfigurationProviderInterface $configurationProvider, string $sectionName)
    {
        $this->configurationProvider = $configurationProvider;
        $this->sectionName = $sectionName;
    }

    public function getConfiguration(): array
    {
        $config = $this->configurationProvider->getConfiguration();

        return $config[$this->sectionName] ?? [];
    }
}
