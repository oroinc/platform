<?php

namespace Oro\Bundle\EntityBundle\Configuration;

use Oro\Component\Config\Cache\PhpArrayConfigProvider;
use Oro\Component\Config\Loader\CumulativeConfigProcessorUtil;
use Oro\Component\Config\Loader\Factory\CumulativeConfigLoaderFactory;
use Oro\Component\Config\ResourcesContainerInterface;

/**
 * The provider for configuration that is loaded from "Resources/config/oro/entity.yml" files.
 */
class EntityConfigurationProvider extends PhpArrayConfigProvider
{
    private const CONFIG_FILE = 'Resources/config/oro/entity.yml';

    /**
     * Gets configuration of the given section.
     *
     * @param string $sectionName See constants in
     *                            {@see \Oro\Bundle\EntityBundle\DependencyInjection\EntityConfiguration}
     *
     * @return array
     */
    public function getConfiguration(string $sectionName): array
    {
        $config = $this->doGetConfig();

        return $config[$sectionName] ?? [];
    }

    /**
     * {@inheritdoc}
     */
    protected function doLoadConfig(ResourcesContainerInterface $resourcesContainer)
    {
        $configs = [];
        $configLoader = CumulativeConfigLoaderFactory::create('oro_entity', self::CONFIG_FILE);
        $resources = $configLoader->load($resourcesContainer);
        foreach ($resources as $resource) {
            if (!empty($resource->data[EntityConfiguration::ROOT_NODE])) {
                $configs[] = $resource->data[EntityConfiguration::ROOT_NODE];
            }
        }

        return CumulativeConfigProcessorUtil::processConfiguration(
            self::CONFIG_FILE,
            new EntityConfiguration(),
            $configs
        );
    }
}
