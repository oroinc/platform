<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

use Oro\Component\Config\CumulativeResourceInfo;
use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;

class OroApiExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('processors.normalize_value.yml');
        $loader->load('processors.collect_public_resources.yml');
        $loader->load('processors.get_config.yml');
        $loader->load('processors.get_metadata.yml');
        $loader->load('processors.get_list.yml');
        $loader->load('processors.get.yml');

        $this->loadApiConfiguration($container);
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function loadApiConfiguration(ContainerBuilder $container)
    {
        $configLoader = new CumulativeConfigLoader(
            'oro_api',
            new YamlCumulativeFileLoader('Resources/config/oro/api.yml')
        );
        $resources    = $configLoader->load($container);
        $apiConfig    = [];
        foreach ($resources as $resource) {
            $apiConfig = $this->mergeApiConfiguration($resource, $apiConfig);
        }

        $apiConfig = $this->normalizeApiConfiguration($apiConfig);

        $configBagDef = $container->getDefinition('oro_api.config_bag');
        $configBagDef->replaceArgument(0, $apiConfig['config']);

        $exclusionProviderDef = $container->getDefinition('oro_api.entity_exclusion_provider.config');
        $exclusionProviderDef->replaceArgument(1, $apiConfig['exclusions']);
    }

    /**
     * @todo: this merge should be replaced with Symfony configuration tree (see BAP-9757)
     *
     * @param CumulativeResourceInfo $resource
     * @param array                  $data
     *
     * @return array
     */
    protected function mergeApiConfiguration(CumulativeResourceInfo $resource, array $data)
    {
        if (!empty($resource->data['oro_api'])) {
            foreach (['entities', 'relations', 'metadata'] as $section) {
                if (!empty($resource->data['oro_api'][$section])) {
                    $merged         = $this->mergeApiConfigurationSection(
                        $data,
                        $resource->data['oro_api'],
                        [$section]
                    );
                    $data[$section] = $merged[$section];
                }
            }
            $section = 'exclusions';
            if (!empty($resource->data['oro_api'][$section])) {
                $data[$section] = array_merge(
                    $this->normalizeArray($data, $section),
                    $resource->data['oro_api'][$section]
                );
            }
        }

        return $data;
    }

    /**
     * @param array|null $data1
     * @param array|null $data2
     * @param string[]   $sections
     *
     * @return array
     */
    protected function mergeApiConfigurationSection($data1, $data2, array $sections)
    {
        $result = [];

        $data1 = $this->normalizeArray($data1);
        $data2 = $this->normalizeArray($data2);
        foreach ($sections as $section) {
            $array1 = $this->normalizeArray($data1, $section);
            $array2 = $this->normalizeArray($data2, $section);

            $sectionData = [];
            foreach ($array1 as $key => $val) {
                $val = $this->normalizeArray($val);
                if (array_key_exists($key, $array2)) {
                    if (!empty($array2[$key])) {
                        $sectionData[$key] = call_user_func(
                            $this->getMergeCallback($section, $key),
                            $val,
                            $array2[$key]
                        );
                    } else {
                        $sectionData[$key] = $val;
                    }
                    unset($array2[$key]);
                } else {
                    $sectionData[$key] = $val;
                }
            }
            foreach ($array2 as $key => $val) {
                $sectionData[$key] = $this->normalizeArray($val);
            }

            $result[$section] = $sectionData;

            unset($data1[$section]);
            unset($data2[$section]);
        }
        foreach ($data1 as $key => $val) {
            $result[$key] = $val;
        }
        foreach ($data2 as $key => $val) {
            $result[$key] = $val;
        }

        return $result;
    }

    /**
     * @param string $parentSection
     * @param string $section
     *
     * @return callable
     */
    protected function getMergeCallback($parentSection, $section)
    {
        if (in_array($parentSection, ['entities', 'relations', 'metadata'], true)) {
            return function (array $array1, array $array2) {
                $result = $this->mergeApiConfigurationSection(
                    $array1,
                    $array2,
                    ['definition']
                );
                if (empty($result['definition'])) {
                    unset($result['definition']);
                }

                return $result;
            };
        }
        if ('definition' === $parentSection) {
            if ('fields' === $section) {
                return function (array $array1, array $array2) {
                    $result = $this->mergeApiConfigurationSection(
                        ['fields' => $array1],
                        ['fields' => $array2],
                        ['fields']
                    );
                    if (!empty($result['fields'])) {
                        $result['fields'] = $this->normalizeFields($result['fields']);
                    }

                    return $result['fields'];
                };
            } elseif (in_array($section, ['filters', 'sorters'], true)) {
                return function (array $array1, array $array2) {
                    $result = $this->mergeApiConfigurationSection(
                        $array1,
                        $array2,
                        ['fields']
                    );
                    if (empty($result['fields'])) {
                        unset($result['fields']);
                    } else {
                        $result['fields'] = $this->normalizeFields($result['fields']);
                    }

                    return $result;
                };
            }
        }

        return 'array_merge';
    }

    /**
     * @param array|null $data
     * @param string     $section
     *
     * @return array
     */
    protected function normalizeArray($data, $section = null)
    {
        if (null === $section) {
            return null !== $data ? $data : [];
        }

        return !empty($data[$section]) ? $data[$section] : [];
    }

    /**
     * @param array $array
     *
     * @return array
     */
    protected function normalizeArrayValues(array $array)
    {
        return array_map(
            function ($item) {
                return null !== $item ? $item : [];
            },
            $array
        );
    }

    /**
     * @param array $array
     *
     * @return array
     */
    protected function normalizeFields(array $array)
    {
        return array_map(
            function ($item) {
                return !empty($item) ? $item : null;
            },
            $array
        );
    }

    /**
     * @param array $data
     *
     * @return array
     */
    protected function normalizeApiConfiguration(array $data)
    {
        $exclusions = [];
        if (array_key_exists('exclusions', $data)) {
            $exclusions = $data['exclusions'];
            unset($data['exclusions']);
        }

        if (!empty($data['entities'])) {
            foreach ($data['entities'] as $entityClass => &$entityConfig) {
                if (!empty($entityConfig) && array_key_exists('exclude', $entityConfig)) {
                    if ($entityConfig['exclude']) {
                        $exclusions[] = ['entity' => $entityClass];
                    }
                    unset($entityConfig['exclude']);
                }
            }
        }

        return [
            'exclusions' => $exclusions,
            'config'     => $data
        ];
    }
}
