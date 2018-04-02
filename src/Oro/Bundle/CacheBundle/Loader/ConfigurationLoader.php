<?php

namespace Oro\Bundle\CacheBundle\Loader;

use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ConfigurationLoader
{
    /** @var ParameterBag */
    protected $parameterBag;

    /** @var array */
    protected $resources;

    public function __construct()
    {
        $this->parameterBag = new ParameterBag();
    }

    /**
     * @param ParameterBagInterface $parameterBag
     */
    public function setParameterBag(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }

    /**
     * @param string $filePath
     * @param string $nodeName
     * @param string $resourceName
     * @param ContainerBuilder|null $containerBuilder
     *
     * @return array
     */
    public function loadConfiguration($filePath, $resourceName, $nodeName, ContainerBuilder $containerBuilder = null)
    {
        $hash = $this->generateHash($filePath, $resourceName);

        if (!isset($this->resources[$hash])) {
            $configLoader = new CumulativeConfigLoader(
                $resourceName,
                new YamlCumulativeFileLoader($filePath)
            );
            $this->resources[$hash] = $configLoader->load($containerBuilder);
        }

        $configs = [];
        $resources = $this->resources[$hash];
        foreach ($resources as $resource) {
            if (array_key_exists($nodeName, (array)$resource->data) && is_array($resource->data[$nodeName])) {
                $configs[$resource->bundleClass] = $resource->data[$nodeName];
            }
        }

        return $this->parameterBag->resolveValue($configs);
    }

    /**
     * @param string $filePath
     * @param string $resourceName
     *
     * @return string
     */
    protected function generateHash($filePath, $resourceName)
    {
        return md5(sprintf('%s:%s', $filePath, $resourceName));
    }
}
