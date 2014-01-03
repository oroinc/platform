<?php
namespace Oro\Bundle\NavigationBundle\Title\TitleReader;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class ConfigReader implements ReaderInterface
{
    /**
     * @var array
     */
    private $configData = array();

    /**
     * @param array $configData
     */
    public function setConfigData(array $configData)
    {
        $this->configData = $configData;
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
        $data = array();

        foreach ($this->configData as $route => $title) {
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
