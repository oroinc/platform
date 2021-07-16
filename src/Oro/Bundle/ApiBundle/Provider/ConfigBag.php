<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Symfony\Contracts\Service\ResetInterface;

/**
 * A storage for configuration of all registered API resources.
 */
class ConfigBag implements ConfigBagInterface, ResetInterface
{
    private const ENTITIES = 'entities';

    /** @var ConfigCache */
    private $configCache;

    /** @var string */
    private $configFile;

    /** @var array */
    private $config;

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
        $this->ensureInitialized();

        if (!isset($this->config[self::ENTITIES])) {
            return [];
        }

        return array_keys($this->config[self::ENTITIES]);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig(string $className, string $version): ?array
    {
        $this->ensureInitialized();

        return $this->config[self::ENTITIES][$className] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function reset()
    {
        $this->config = null;
    }

    private function ensureInitialized(): void
    {
        if (null === $this->config) {
            $this->config = $this->configCache->getConfig($this->configFile);
        }
    }
}
