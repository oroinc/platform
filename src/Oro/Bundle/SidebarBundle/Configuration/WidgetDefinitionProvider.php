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
    private const RELATIVE_PATH = 'Resources/public/sidebar_widgets/{folder}/widget.yml';
    private const APP_RELATIVE_PATH = '../public/sidebar_widgets/{folder}/widget.yml';

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
            [
                $this->getFolderYmlCumulativeFileLoader(self::RELATIVE_PATH),
                $this->getFolderYmlCumulativeFileLoader(self::APP_RELATIVE_PATH),
            ],
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

    private function getFolderYmlCumulativeFileLoader(string $relativeFilePath): FolderingCumulativeFileLoader
    {
        return new FolderingCumulativeFileLoader(
            '{folder}',
            '\w+',
            new YamlCumulativeFileLoader($relativeFilePath)
        );
    }
}
