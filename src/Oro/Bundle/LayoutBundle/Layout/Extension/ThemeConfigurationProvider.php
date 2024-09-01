<?php

namespace Oro\Bundle\LayoutBundle\Layout\Extension;

use Oro\Component\Config\Cache\PhpArrayConfigProvider;
use Oro\Component\Config\CumulativeResourceInfo;
use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\CumulativeConfigProcessorUtil;
use Oro\Component\Config\Loader\FolderingCumulativeFileLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Oro\Component\Config\ResourcesContainerInterface;
use Oro\Component\Layout\Extension\Theme\Model\ThemeDefinitionBagInterface;

/**
 * The provider for layout theme configuration that are loaded from the following files:
 * * Resources/views/layouts/{folder}/theme.yml
 * * Resources/views/layouts/{folder}/config/assets.yml
 * * Resources/views/layouts/{folder}/config/images.yml
 * * Resources/views/layouts/{folder}/config/page_templates.yml
 */
class ThemeConfigurationProvider extends PhpArrayConfigProvider implements ThemeDefinitionBagInterface
{
    private ThemeConfiguration $configuration;
    private string $folderPattern;

    public function __construct(
        string $cacheFile,
        bool $debug,
        ThemeConfiguration $configuration,
        string $folderPattern
    ) {
        parent::__construct($cacheFile, $debug);
        $this->configuration = $configuration;
        $this->folderPattern = $folderPattern;
    }

    /**
     * {@inheritDoc}
     */
    public function getThemeNames(): array
    {
        $config = $this->doGetConfig();

        return array_keys($config);
    }

    /**
     * {@inheritDoc}
     */
    public function getThemeDefinition(string $themeName): ?array
    {
        $config = $this->doGetConfig();

        return $config[$themeName] ?? null;
    }

    /**
     * {@inheritDoc}
     */
    protected function doLoadConfig(ResourcesContainerInterface $resourcesContainer)
    {
        $configs = [];
        $resources = array_merge($this->loadThemeResources($resourcesContainer));
        foreach ($resources as $resource) {
            $configs[] = $this->getThemeConfig($resource);
        }
        $resources = $this->loadAdditionalResources($resourcesContainer);
        foreach ($resources as $resource) {
            $configs[] = $this->getAdditionalConfig($resource);
        }

        return CumulativeConfigProcessorUtil::processConfiguration(
            'Resources/views/layouts/*/theme.yml',
            $this->configuration,
            $configs
        );
    }

    /**
     * @return CumulativeResourceInfo[]
     */
    private function loadThemeResources(ResourcesContainerInterface $resourcesContainer): array
    {
        $configLoader = new CumulativeConfigLoader(
            'oro_layout',
            [
                new FolderingCumulativeFileLoader(
                    '{folder}',
                    $this->folderPattern,
                    new YamlCumulativeFileLoader('Resources/views/layouts/{folder}/theme.yml')
                ),
                new FolderingCumulativeFileLoader(
                    '{folder}',
                    $this->folderPattern,
                    new YamlCumulativeFileLoader('../templates/layouts/{folder}/theme.yml')
                )
            ]
        );

        return $configLoader->load($resourcesContainer);
    }

    /**
     * @return CumulativeResourceInfo[]
     */
    private function loadAdditionalResources(ResourcesContainerInterface $resourcesContainer): array
    {
        $loaders = [];
        foreach ($this->configuration->getAdditionalConfigFileNames() as $fileName) {
            $loaders[] = new YamlCumulativeFileLoader('Resources/views/layouts/{folder}/config/' . $fileName);
            $loaders[] = new YamlCumulativeFileLoader('../templates/layouts/{folder}/config/' . $fileName);
        }
        $configLoader = new CumulativeConfigLoader(
            'oro_layout',
            new FolderingCumulativeFileLoader(
                '{folder}',
                $this->folderPattern,
                $loaders
            )
        );

        return $configLoader->load($resourcesContainer);
    }

    private function getThemeConfig(CumulativeResourceInfo $resource): array
    {
        $themeName = basename(\dirname($resource->path));

        return [
            $themeName => $resource->data
        ];
    }

    private function getAdditionalConfig(CumulativeResourceInfo $resource): array
    {
        $themeName = basename(\dirname($resource->path, 2));
        $section = basename($resource->path, '.yml');

        return [
            $themeName => [
                'config' => [
                    $section => $resource->data
                ]
            ]
        ];
    }
}
