<?php

namespace Oro\Bundle\NavigationBundle\Title\TitleReader;

use Oro\Bundle\NavigationBundle\Configuration\ConfigurationProvider;

/**
 * Reads page titles from "Resources/config/oro/navigation.yml" files.
 */
class ConfigReader implements ReaderInterface
{
    /** @var ConfigurationProvider */
    private $configurationProvider;

    public function __construct(ConfigurationProvider $configurationProvider)
    {
        $this->configurationProvider = $configurationProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getTitle($route)
    {
        return $this->configurationProvider->getTitle($route);
    }
}
