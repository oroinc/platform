<?php

namespace Oro\Bundle\SidebarBundle\Configuration;

use Oro\Component\Config\Cache\PhpArrayConfigProvider;
use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\CumulativeConfigProcessorUtil;
use Oro\Component\Config\Loader\FolderingCumulativeFileLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Oro\Component\Config\ResourcesContainerInterface;

/**
 * The provider for sidebar widget definitions
 * that are loaded from "Resources/public/sidebar_widgets/{folder}/widget.yml" files.
 */
class WidgetDefinitionProvider extends PhpArrayConfigProvider
{
    /**
     * Gets definitions for all sidebar widgets that should be shown at the given placement.
     *
     * @param string $placement
     *
     * @return array [widget name => widget definition, ...]
     */
    public function getWidgetDefinitionsByPlacement(string $placement): array
    {
        $result = [];
        $definitions = $this->doGetConfig();
        foreach ($definitions as $name => $definition) {
            if ($definition['placement'] === $placement || $definition['placement'] === 'both') {
                $result[$name] = $definition;
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function doLoadConfig(ResourcesContainerInterface $resourcesContainer)
    {
        $config = [];
        $configLoader = new CumulativeConfigLoader(
            'oro_sidebar',
            new FolderingCumulativeFileLoader(
                '{folder}',
                '\w+',
                new YamlCumulativeFileLoader('Resources/public/sidebar_widgets/{folder}/widget.yml')
            )
        );
        $resources = $configLoader->load($resourcesContainer);
        foreach ($resources as $resource) {
            $config[\basename(\dirname($resource->path))] = $resource->data;
        }

        return CumulativeConfigProcessorUtil::processConfiguration(
            'Resources/public/sidebar_widgets/*/widget.yml',
            new WidgetDefinitionConfiguration(),
            [$config]
        );
    }
}
