<?php

namespace Oro\Bundle\ActionBundle\Configuration;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Collections\Collection;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

use Oro\Component\Config\Merger\ConfigurationMerger;

class ConfigurationProvider implements ConfigurationProviderInterface
{
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
    }

    public function warmUpCache()
    {
        $this->clearCache();
        $this->cache->save($this->rootNode, $this->resolveConfiguration());
    }

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
     * @return array
     * @throws InvalidConfigurationException
     */
    protected function resolveConfiguration(Collection $errors = null)
    {
        $merger = new ConfigurationMerger($this->kernelBundles);
        $configs = $merger->mergeConfiguration($this->rawConfiguration);
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
}
