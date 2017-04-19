<?php

namespace Oro\Bundle\NavigationBundle\Title\TitleReader;

use Oro\Bundle\NavigationBundle\Provider\ConfigurationProvider;

class ConfigReader implements ReaderInterface
{
    /** @var ConfigurationProvider */
    private $configurationProvider;

    /**
     * @param ConfigurationProvider $configurationProvider
     */
    public function __construct(ConfigurationProvider $configurationProvider)
    {
        $this->configurationProvider = $configurationProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getTitle($route)
    {
        $titles = $this->configurationProvider->getConfiguration(ConfigurationProvider::TITLES_KEY);
        if (array_key_exists($route, $titles)) {
            return $titles[$route];
        }

        return null;
    }
}
