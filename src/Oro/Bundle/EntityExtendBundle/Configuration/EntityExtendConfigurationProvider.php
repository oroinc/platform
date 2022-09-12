<?php

namespace Oro\Bundle\EntityExtendBundle\Configuration;

use Oro\Component\Config\Cache\PhpArrayConfigProvider;
use Oro\Component\Config\Loader\CumulativeConfigProcessorUtil;
use Oro\Component\Config\Loader\Factory\CumulativeConfigLoaderFactory;
use Oro\Component\Config\ResourcesContainerInterface;

/**
 * The provider for configuration that is loaded from "Resources/config/oro/entity_extend.yml" files.
 */
class EntityExtendConfigurationProvider extends PhpArrayConfigProvider
{
    private const CONFIG_FILE = 'Resources/config/oro/entity_extend.yml';

    /**
     * Gets configuration of underlying types.
     *
     * @return array [data type => underlying data type, ...]
     */
    public function getUnderlyingTypes(): array
    {
        $config = $this->doGetConfig();

        return $config['underlying_types'] ?? [];
    }

    /**
     * {@inheritdoc}
     */
    protected function doLoadConfig(ResourcesContainerInterface $resourcesContainer)
    {
        $configs = [];
        $configLoader = CumulativeConfigLoaderFactory::create('oro_entity_extend', self::CONFIG_FILE);
        $resources = $configLoader->load($resourcesContainer);
        foreach ($resources as $resource) {
            if (!empty($resource->data[EntityExtendConfiguration::ROOT_NODE])) {
                $configs[] = $resource->data[EntityExtendConfiguration::ROOT_NODE];
            }
        }

        return CumulativeConfigProcessorUtil::processConfiguration(
            self::CONFIG_FILE,
            new EntityExtendConfiguration(),
            $configs
        );
    }
}
