<?php

namespace Oro\Bundle\ActionBundle\Configuration;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Collections\Collection;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Component\Config\Merger\ConfigurationMerger;
use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Oro\Bundle\CacheBundle\Provider\ConfigCacheWarmerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ConfigurationProvider implements ConfigurationProviderInterface, ConfigCacheWarmerInterface
{
    const CONFIG_FILE_PATH = 'Resources/config/oro/actions.yml';

    /** @var ConfigurationDefinitionInterface */
    protected $configurationDefinition;

    /** @var ConfigurationValidatorInterface */
    protected $validator;

    /** @var CacheProvider */
    protected $cache;

    /** @var array */
    protected $rawConfiguration;

    /** @var array */
    protected $kernelBundles;

    /** @var array */
    protected $processedConfigs = [];

    /** @var string */
    protected $rootNode;

    /** @var ParameterBagInterface */
    protected $parameterBag;

    /**
     * @param ConfigurationDefinitionInterface $configurationDefinition
     * @param ConfigurationValidatorInterface $validator
     * @param CacheProvider $cache
     * @param array $rawConfiguration
     * @param array $kernelBundles
     * @param string $rootNode
     */
    public function __construct(
        ConfigurationDefinitionInterface $configurationDefinition,
        ConfigurationValidatorInterface $validator,
        CacheProvider $cache,
        array $rawConfiguration,
        array $kernelBundles,
        $rootNode
    ) {
        $this->configurationDefinition = $configurationDefinition;
        $this->validator = $validator;
        $this->cache = $cache;
        $this->rawConfiguration = $rawConfiguration;
        $this->kernelBundles = array_values($kernelBundles);
        $this->rootNode = $rootNode;
        $this->parameterBag = new ParameterBag();
    }

    /**
     * @param ParameterBagInterface $parameterBag
     */
    public function setParameterBag(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUpCache()
    {
        $this->clearCache();
        $this->cache->save($this->rootNode, $this->resolveConfiguration());
    }

    /**
     * {@inheritdoc}
     */
    public function warmUpResourceCache(ContainerBuilder $containerBuilder)
    {
        $this->clearCache();
        $this->cache->save($this->rootNode, $this->resolveConfiguration(null, $containerBuilder));
    }

    /**
     * {@inheritdoc}
     */
    public function clearCache()
    {
        $this->cache->delete($this->rootNode);
    }

    /**
     * @param bool $ignoreCache
     * @param Collection $errors
     * @return array
     * @throws InvalidConfigurationException
     */
    public function getConfiguration($ignoreCache = false, Collection $errors = null)
    {
        if (!$ignoreCache && $this->cache->contains($this->rootNode)) {
            $configuration = $this->cache->fetch($this->rootNode);
        } else {
            $configuration = $this->resolveConfiguration($errors);

            if (!$ignoreCache) {
                $this->clearCache();
                $this->cache->save($this->rootNode, $configuration);
            }
        }

        return $configuration;
    }

    /**
     * @param Collection $errors
     * @param ContainerBuilder $containerBuilder
     * @return array
     * @throws InvalidConfigurationException
     */
    protected function resolveConfiguration(Collection $errors = null, ContainerBuilder $containerBuilder = null)
    {
        $rawConfiguration = array_merge($this->rawConfiguration, $this->loadConfiguration($containerBuilder));

        $merger = new ConfigurationMerger($this->kernelBundles);
        $configs = $merger->mergeConfiguration($rawConfiguration);
        $data = [];

        try {
            if (!empty($configs)) {
                $data = $this->configurationDefinition->processConfiguration($configs);

                $this->validator->validate($data, $errors);
            }
        } catch (InvalidConfigurationException $e) {
            throw new InvalidConfigurationException(sprintf('Can\'t parse configuration. %s', $e->getMessage()));
        }

        return $data;
    }

    /**
     * @param ContainerBuilder|null $containerBuilder
     * @return array
     */
    protected function loadConfiguration(ContainerBuilder $containerBuilder = null)
    {
        $nodeName = $this->rootNode;
        $configLoader = new CumulativeConfigLoader(
            'oro_action',
            new YamlCumulativeFileLoader(self::CONFIG_FILE_PATH)
        );

        $resources = $configLoader->load($containerBuilder);
        $configs = [];

        foreach ($resources as $resource) {
            if (array_key_exists($nodeName, (array)$resource->data) && is_array($resource->data[$nodeName])) {
                $configs[$resource->bundleClass] = $resource->data[$nodeName];
            }
        }

        return $this->parameterBag->resolveValue($configs);
    }
}
