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
    /** @var ThemeConfiguration */
    private $configuration;

    /** @var string */
    private $folderPattern;

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
     * {@inheritdoc}
     */
    public function getThemeNames(): array
    {
        $config = $this->doGetConfig();

        return array_keys($config);
    }

    /**
     * {@inheritdoc}
     */
    public function getThemeDefinition(string $themeName): ?array
    {
        $config = $this->doGetConfig();

        return $config[$themeName] ?? null;
    }

    /**
     * {@inheritdoc}
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
     * @param ResourcesContainerInterface $resourcesContainer
     *
     * @return CumulativeResourceInfo[]
     */
    private function loadThemeResources(ResourcesContainerInterface $resourcesContainer)
    {
        $configLoader = new CumulativeConfigLoader(
            'oro_layout',
            [
                $this->getFolderingCumulativeFileLoaderForPath('Resources/views/layouts/{folder}/theme.yml'),
                $this->getFolderingCumulativeFileLoaderForPath('../templates/layouts/{folder}/theme.yml')
            ]
        );

        return $configLoader->load($resourcesContainer);
    }

    /**
     * @param string $path
     * @param string $folderPlaceholder
     *
     * @return FolderingCumulativeFileLoader
     */
    private function getFolderingCumulativeFileLoaderForPath($path, $folderPlaceholder = '{folder}')
    {
        return new FolderingCumulativeFileLoader(
            $folderPlaceholder,
            $this->folderPattern,
            new YamlCumulativeFileLoader($path)
        );
    }

    /**
     * @param ResourcesContainerInterface $resourcesContainer
     *
     * @return CumulativeResourceInfo[]
     */
    private function loadAdditionalResources(ResourcesContainerInterface $resourcesContainer)
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

    /**
     * @param CumulativeResourceInfo $resource
     * @return array
     */
    private function getThemeConfig(CumulativeResourceInfo $resource)
    {
        $themeName = basename(dirname($resource->path));

        return [
            $themeName => $resource->data
        ];
    }

    /**
     * @param CumulativeResourceInfo $resource
     * @return array
     */
    private function getAdditionalConfig(CumulativeResourceInfo $resource)
    {
        $themeName = basename(dirname($resource->path, 2));
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
