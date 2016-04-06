<?php

namespace Oro\Bundle\SearchBundle\DependencyInjection;

use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Oro\Component\PhpUtils\ArrayUtil;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroSearchExtension extends Extension
{
    /**
     * Merge strategy.
     */
    const STRATEGY_APPEND  = 'append';
    const STRATEGY_REPLACE = 'replace';

    /**
     * Default merge strategy params
     *
     * @var array $optionsToMerge
     */
    protected $mergeOptions = [
        'title_fields'  => self::STRATEGY_REPLACE,
        'fields'        => self::STRATEGY_APPEND
    ];

    /**
     * @param  array            $configs
     * @param  ContainerBuilder $container
     * @throws InvalidConfigurationException
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        // load entity search configuration from search.yml files
        $configPart          = [];
        $ymlLoader           = new YamlCumulativeFileLoader('Resources/config/search.yml');
        $configurationLoader = new CumulativeConfigLoader('oro_search', $ymlLoader);
        $engineResources     = $configurationLoader->load($container);

        foreach ($engineResources as $resource) {
            foreach ($resource->data as $key => $value) {
                if (isset($configPart[$key])) {
                    $firstConfig = $configPart[$key];
                    $configPart[$key] = $this->mergeConfig($firstConfig, $value);
                } else {
                    $configPart[$key] = $value;
                }
            }
        }

        // merge entity configuration with main configuration
        if (isset($configs[0]['entities_config'])) {
            $configs[0]['entities_config'] = array_merge($configPart, $configs[0]['entities_config']);
        } else {
            $configs[0]['entities_config'] = $configPart;
        }

        // parse and validate configuration
        $config = $this->processConfiguration(new Configuration(), $configs);

        // set configuration parameters to container
        $container->setParameter('oro_search.engine', $config['engine']);
        $container->setParameter('oro_search.engine_parameters', $config['engine_parameters']);
        $container->setParameter('oro_search.log_queries', $config['log_queries']);
        $container->setParameter('oro_search.realtime_update', $config['realtime_update']);
        $this->setEntitiesConfigParameter($container, $config['entities_config']);
        $container->setParameter('oro_search.twig.item_container_template', $config['item_container_template']);

        // load engine specific and general search services
        $serviceLoader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $serviceLoader->load('services.yml');

        $ymlLoader = new YamlCumulativeFileLoader('Resources/config/oro/search_engine/' . $config['engine'] . '.yml');
        $engineLoader    = new CumulativeConfigLoader('oro_search', $ymlLoader);
        $engineResources = $engineLoader->load($container);

        if (!empty($engineResources)) {
            $resource = end($engineResources);
            $serviceLoader->load($resource->path);
        }
    }

    /**
     * Merge configs data
     * By default used replace strategy:
     * - fields data from first config will replaced with data from second config
     * - new fields will be added
     * Complex config fields provided as array will be merged using replace or append strategy
     * according to merge options
     *
     * @param array $firstConfig
     * @param array $secondConfig
     * @return array
     * @deprecated Since 1.9, will be removed after 1.11.
     *
     * @todo: it is a temporary workaround to add ability to merge configs until improvement BAP-10010 is implemented
     */
    public function mergeConfig(array $firstConfig, array $secondConfig)
    {
        foreach ($secondConfig as $nodeName => $value) {
            if (!array_key_exists($nodeName, $firstConfig)) {
                $firstConfig[$nodeName] = $value;
            } else {
                if (is_array($value)) {
                    if ($this->getStrategy($nodeName) === self::STRATEGY_APPEND) {
                        $mergedArray = ArrayUtil::arrayMergeRecursiveDistinct($firstConfig[$nodeName], $value);
                        $firstConfig[$nodeName] = array_unique($mergedArray, SORT_REGULAR);
                    } else {
                        $firstConfig[$nodeName] = $value;
                    }
                } else {
                    $firstConfig[$nodeName] = $value;
                }
            }
        }

        return $firstConfig;
    }

    /**
     * Merge strategy getter.
     *
     * @param string $fieldName
     * @return string
     */
    public function getStrategy($fieldName)
    {
        if (array_key_exists($fieldName, $this->mergeOptions)) {
            return $this->mergeOptions[$fieldName];
        }
        return $this->getDefaultStrategy();
    }

    /**
     * Default merge Strategy getter.
     *
     * @return string
     */
    public function getDefaultStrategy()
    {
        return self::STRATEGY_REPLACE;
    }

    /**
     * Get alias
     *
     * @return string
     */
    public function getAlias()
    {
        return 'oro_search';
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $config
     * @deprecated since 1.9, will be removed after 1.11
     * Please use oro_search.provider.search_mapping service for mapping config
     */
    protected function setEntitiesConfigParameter(ContainerBuilder $container, array $config)
    {
        $container->setParameter('oro_search.entities_config', $config);
    }
}
