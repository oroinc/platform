<?php

namespace Oro\Bundle\ActionBundle\Configuration;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Collections\Collection;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

use Oro\Component\Config\Merger\ConfigurationMerger;

class ActionConfigurationProvider
{
    const ROOT_NODE_NAME = 'actions';

    /** @var OperationListConfiguration */
    protected $definitionConfiguration;

    /** @var ActionDefinitionConfigurationValidator */
    protected $definitionConfigurationValidator;

    /** @var CacheProvider */
    protected $cache;

    /** @var array */
    protected $rawConfiguration;

    /** @var array */
    protected $kernelBundles;

    /** @var array */
    protected $processedConfigs = [];

    /**
     * @param OperationListConfiguration $definitionConfiguration
     * @param ActionDefinitionConfigurationValidator $definitionConfigurationValidator
     * @param CacheProvider $cache
     * @param array $rawConfiguration
     * @param array $kernelBundles
     */
    public function __construct(
        OperationListConfiguration $definitionConfiguration,
        ActionDefinitionConfigurationValidator $definitionConfigurationValidator,
        CacheProvider $cache,
        array $rawConfiguration,
        array $kernelBundles
    ) {
        $this->definitionConfiguration = $definitionConfiguration;
        $this->definitionConfigurationValidator = $definitionConfigurationValidator;
        $this->cache = $cache;
        $this->rawConfiguration = $rawConfiguration;
        $this->kernelBundles = array_values($kernelBundles);
    }

    public function warmUpCache()
    {
        $this->clearCache();
        $this->cache->save(self::ROOT_NODE_NAME, $this->resolveConfiguration());
    }

    public function clearCache()
    {
        $this->cache->delete(self::ROOT_NODE_NAME);
    }

    /**
     * @param bool $ignoreCache
     * @param Collection $errors
     * @return array
     * @throws InvalidConfigurationException
     */
    public function getActionConfiguration($ignoreCache = false, Collection $errors = null)
    {
        if (!$ignoreCache && $this->cache->contains(self::ROOT_NODE_NAME)) {
            $configuration = $this->cache->fetch(self::ROOT_NODE_NAME);
        } else {
            $configuration = $this->resolveConfiguration($errors);

            if (!$ignoreCache) {
                $this->clearCache();
                $this->cache->save(self::ROOT_NODE_NAME, $configuration);
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
                $data = $this->definitionConfiguration->processConfiguration($configs);

                $this->definitionConfigurationValidator->validate($data, $errors);
            }
        } catch (InvalidConfigurationException $e) {
            throw new InvalidConfigurationException(sprintf('Can\'t parse action configuration. %s', $e->getMessage()));
        }

        return $data;
    }
}
