<?php

namespace Oro\Bundle\ActionBundle\Configuration;

use Oro\Component\Config\Cache\PhpArrayConfigProvider;
use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\CumulativeConfigProcessorUtil;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Oro\Component\Config\Merger\ConfigurationMerger;
use Oro\Component\Config\ResourcesContainerInterface;
use Symfony\Component\DependencyInjection\Container;

/**
 * The provider for actions configuration
 * that is loaded from "Resources/config/oro/actions.yml" files.
 */
class ConfigurationProvider extends PhpArrayConfigProvider implements ConfigurationProviderInterface
{
    private const CONFIG_FILE = 'Resources/config/oro/actions.yml';

    /** @var Container */
    private $container;

    /** @var string[] */
    private $bundles;

    /**
     * @param string    $cacheFile
     * @param bool      $debug
     * @param Container $container
     * @param string[]  $bundles
     */
    public function __construct(string $cacheFile, bool $debug, Container $container, array $bundles)
    {
        parent::__construct($cacheFile, $debug);
        $this->container = $container;
        $this->bundles = $bundles;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration(): array
    {
        return $this->doGetConfig();
    }

    /**
     * {@inheritdoc}
     */
    protected function doLoadConfig(ResourcesContainerInterface $resourcesContainer)
    {
        $mergedConfig = [];
        $rawConfigs = $this->getRawConfigs($resourcesContainer);
        foreach ($rawConfigs as $sectionName => $configs) {
            $merger = new ConfigurationMerger($this->bundles);
            $mergedConfig[$sectionName] = $merger->mergeConfiguration(
                $this->container->getParameterBag()->resolveValue($configs)
            );
        }
        $this->checkConfiguration($mergedConfig);

        if (empty($mergedConfig)) {
            return [];
        }

        return CumulativeConfigProcessorUtil::processConfiguration(
            self::CONFIG_FILE,
            new Configuration(),
            [$mergedConfig]
        );
    }

    /**
     * @param ResourcesContainerInterface $resourcesContainer
     *
     * @return array [section name => [bundle class => config, ...], ...]
     */
    private function getRawConfigs(ResourcesContainerInterface $resourcesContainer): array
    {
        $result = [];
        $configLoader = new CumulativeConfigLoader(
            'oro_action',
            new YamlCumulativeFileLoader(self::CONFIG_FILE)
        );
        $resources = $configLoader->load($resourcesContainer);
        foreach ($resources as $resource) {
            foreach ($resource->data as $sectionName => $config) {
                if (\is_array($config)) {
                    $result[$sectionName][$resource->bundleClass] = $config;
                }
            }
        }

        return $result;
    }

    /**
     * This function provides backward compatibility with original logic of Symfony`s PhpDumper.
     * After that function all strings that have escaped % like '%%' should be replaced by '%'.
     *
     * @param mixed $config
     */
    private function checkConfiguration(&$config): void
    {
        if (\is_array($config)) {
            $new = [];

            foreach ($config as $key => $value) {
                $this->checkConfiguration($key);
                $this->checkConfiguration($value);

                $new[$key] = $value;
            }

            $config = $new;
        } elseif (\is_string($config)) {
            $config = \str_replace('%%', '%', $config);
        }
    }
}
