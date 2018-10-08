<?php

namespace Oro\Bundle\ActionBundle\Configuration;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CacheBundle\Loader\ConfigurationLoader;
use Oro\Bundle\CacheBundle\Provider\ConfigCacheWarmerInterface;
use Oro\Component\Config\Merger\ConfigurationMerger;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Provides an entry point for configuration of operations.
 */
class ConfigurationProvider implements ConfigurationProviderInterface, ConfigCacheWarmerInterface
{
    const CONFIG_FILE_PATH = 'Resources/config/oro/actions.yml';

    /** @var ConfigurationLoader */
    protected $configurationLoader;

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

    /**
     * @param ConfigurationLoader $configurationLoader
     * @param ConfigurationDefinitionInterface $configurationDefinition
     * @param ConfigurationValidatorInterface $validator
     * @param CacheProvider $cache
     * @param array $rawConfiguration
     * @param array $kernelBundles
     * @param string $rootNode
     */
    public function __construct(
        ConfigurationLoader $configurationLoader,
        ConfigurationDefinitionInterface $configurationDefinition,
        ConfigurationValidatorInterface $validator,
        CacheProvider $cache,
        array $rawConfiguration,
        array $kernelBundles,
        $rootNode
    ) {
        $this->configurationLoader = $configurationLoader;
        $this->configurationDefinition = $configurationDefinition;
        $this->validator = $validator;
        $this->cache = $cache;
        $this->rawConfiguration = $rawConfiguration;
        $this->kernelBundles = array_values($kernelBundles);
        $this->rootNode = $rootNode;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUpCache()
    {
        $this->cache->save($this->rootNode, $this->resolveConfiguration());
    }

    /**
     * {@inheritdoc}
     */
    public function warmUpResourceCache(ContainerBuilder $containerBuilder)
    {
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
        if ($ignoreCache) {
            $configuration = $this->resolveConfiguration($errors);
        } else {
            $configuration = $this->cache->fetch($this->rootNode);
            if (false === $configuration) {
                $configuration = $this->resolveConfiguration($errors);
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
        $rawConfiguration = $this->configurationLoader->loadConfiguration(
            self::CONFIG_FILE_PATH,
            'oro_action',
            $this->rootNode,
            $containerBuilder
        );
        $rawConfiguration = array_merge($this->rawConfiguration, $rawConfiguration);

        $merger = new ConfigurationMerger($this->kernelBundles);
        $configs = $merger->mergeConfiguration($rawConfiguration);
        $data = [];

        $this->checkConfiguration($configs);

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
     * This function provides backward compatibility with original logic of Symfony`s PhpDumper.
     * After that function all strings that have escaped % like '%%' should be replaced by '%'.
     *
     * @param mixed $config
     */
    private function checkConfiguration(&$config)
    {
        if (is_array($config)) {
            $new = [];

            foreach ($config as $key => $value) {
                $this->checkConfiguration($key);
                $this->checkConfiguration($value);

                $new[$key] = $value;
            }

            $config = $new;
        } elseif (is_string($config)) {
            $config = str_replace('%%', '%', $config);
        }
    }
}
