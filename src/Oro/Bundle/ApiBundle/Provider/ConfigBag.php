<?php

namespace Oro\Bundle\ApiBundle\Provider;

/**
 * A storage for configuration of all registered API resources.
 */
class ConfigBag implements ConfigBagInterface
{
    private const ENTITIES = 'entities';

    /** @var ConfigCache */
    private $configCache;

    /** @var string */
    private $configFile;

    /** @var array */
    private $config;

    /**
     * @param ConfigCache $configCache
     * @param string      $configFile
     */
    public function __construct(ConfigCache $configCache, string $configFile)
    {
        $this->configCache = $configCache;
        $this->configFile = $configFile;
    }

    /**
     * {@inheritdoc}
     */
    public function getClassNames(string $version): array
    {
        return \array_keys($this->findConfigs($version));
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig(string $className, string $version): ?array
    {
        return $this->findConfig($className, $version);
    }

    /**
     * @param string $version
     *
     * @return array
     */
    private function findConfigs($version)
    {
        $this->ensureInitialized();

        if (!isset($this->config[self::ENTITIES])) {
            return [];
        }

        return $this->config[self::ENTITIES];
    }

    /**
     * @param string $className
     * @param string $version
     *
     * @return array|null
     */
    private function findConfig($className, $version)
    {
        $this->ensureInitialized();

        if (!isset($this->config[self::ENTITIES][$className])) {
            // no config for the requested class
            return null;
        }

        return $this->config[self::ENTITIES][$className];
    }

    private function ensureInitialized()
    {
        if (null === $this->config) {
            $this->config = $this->configCache->getConfig($this->configFile);
        }
    }
}
