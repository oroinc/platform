<?php

namespace Oro\Bundle\LocaleBundle\Configuration;

use Oro\Component\Config\Cache\PhpArrayConfigProvider;
use Oro\Component\Config\Loader\CumulativeConfigProcessorUtil;
use Oro\Component\Config\Loader\Factory\CumulativeConfigLoaderFactory;
use Oro\Component\Config\ResourcesContainerInterface;

/**
 * The provider for configuration that is loaded from "Resources/config/oro/name_format.yml" files.
 */
class NameFormatConfigurationProvider extends PhpArrayConfigProvider
{
    private const CONFIG_FILE = 'Resources/config/oro/name_format.yml';

    public function getConfiguration(): array
    {
        return $this->doGetConfig();
    }

    /**
     * {@inheritdoc}
     */
    protected function doLoadConfig(ResourcesContainerInterface $resourcesContainer)
    {
        $configs = [];
        $configLoader = CumulativeConfigLoaderFactory::create('oro_locale_name_format', self::CONFIG_FILE);
        $resources = $configLoader->load($resourcesContainer);
        foreach ($resources as $resource) {
            $configs[] = $resource->data;
        }

        return CumulativeConfigProcessorUtil::processConfiguration(
            self::CONFIG_FILE,
            new NameFormatConfiguration(),
            $configs
        );
    }
}
