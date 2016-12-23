<?php
namespace Oro\Bundle\NavigationBundle\Title\TitleReader;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

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
     * Get Route/Title information from bundle configs
     *
     * @param  array $routes
     * @return array
     * @throws \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     */
    public function getData(array $routes)
    {
        $data = [];

        // TODO set const
        $titles = $this->configurationProvider->getConfiguration('oro_navigation_titles');

        foreach ($titles as $route => $title) {
            if (array_key_exists($route, $routes)) {
                $data[$route] = $title;
            } else {
                throw new InvalidConfigurationException(
                    sprintf('Title for route "%s" could not be saved. Route not found.', $route)
                );
            }
        }

        return $data;
    }
}
