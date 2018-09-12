<?php

namespace Oro\Bundle\ApiBundle\Provider;

/**
 * A cache for Data API configuration.
 */
class ConfigCache
{
    /** @var string */
    private $configKey;

    /** @var ConfigCacheFactory */
    private $configCacheFactory;

    /** @var ConfigCacheWarmer */
    private $configCacheWarmer;

    /** @var array|null */
    private $data;

    /**
     * @param string             $configKey
     * @param ConfigCacheFactory $configCacheFactory
     * @param ConfigCacheWarmer  $configCacheWarmer
     */
    public function __construct(
        string $configKey,
        ConfigCacheFactory $configCacheFactory,
        ConfigCacheWarmer $configCacheWarmer
    ) {
        $this->configKey = $configKey;
        $this->configCacheFactory = $configCacheFactory;
        $this->configCacheWarmer = $configCacheWarmer;
    }

    /**
     * @param string $configFile
     *
     * @return array
     */
    public function getConfig(string $configFile): array
    {
        $configs = $this->getSection(ConfigCacheWarmer::CONFIG);
        if (!isset($configs[$configFile])) {
            throw new \InvalidArgumentException(sprintf('Unknown config "%s".', $configFile));
        }

        return $configs[$configFile];
    }

    /**
     * @return array
     */
    public function getAliases(): array
    {
        return $this->getSection(ConfigCacheWarmer::ALIASES);
    }

    /**
     * @return string[]
     */
    public function getExcludedEntities(): array
    {
        return $this->getSection(ConfigCacheWarmer::EXCLUDED_ENTITIES);
    }

    /**
     * @return array [class name => substitute class name, ...]
     */
    public function getSubstitutions(): array
    {
        return $this->getSection(ConfigCacheWarmer::SUBSTITUTIONS);
    }

    /**
     * @return array
     */
    public function getExclusions(): array
    {
        return $this->getSection(ConfigCacheWarmer::EXCLUSIONS);
    }

    /**
     * @return array
     */
    public function getInclusions(): array
    {
        return $this->getSection(ConfigCacheWarmer::INCLUSIONS);
    }

    /**
     * @param string $section
     *
     * @return mixed
     */
    private function getSection(string $section)
    {
        $data = $this->getData();

        return $data[$section];
    }

    /**
     * @return array
     */
    private function getData(): array
    {
        if (null === $this->data) {
            $cache = $this->configCacheFactory->getCache($this->configKey);
            if (!$cache->isFresh()) {
                $this->configCacheWarmer->warmUp($this->configKey);
            }

            $data = require $cache->getPath();
            if (!is_array($data)) {
                throw new \LogicException(sprintf('The "%s" must return an array.', $cache->getPath()));
            }
            $this->data = $data;
        }

        return $this->data;
    }
}
