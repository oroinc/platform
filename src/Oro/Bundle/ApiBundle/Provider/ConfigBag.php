<?php

namespace Oro\Bundle\ApiBundle\Provider;

/**
 * A storage for configuration of all registered Data API resources.
 */
class ConfigBag implements ConfigBagInterface
{
    private const ENTITIES  = 'entities';
    private const RELATIONS = 'relations';

    /** @var array */
    private $config;

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function getClassNames(string $version): array
    {
        return \array_keys($this->findConfigs(self::ENTITIES, $version));
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig(string $className, string $version): ?array
    {
        return $this->findConfig(self::ENTITIES, $className, $version);
    }

    /**
     * {@inheritdoc}
     */
    public function getRelationConfig(string $className, string $version): ?array
    {
        return $this->findConfig(self::RELATIONS, $className, $version);
    }

    /**
     * @param string $section
     * @param string $version
     *
     * @return array
     */
    private function findConfigs($section, $version)
    {
        if (!isset($this->config[$section])) {
            return [];
        }

        return $this->config[$section];
    }

    /**
     * @param string $section
     * @param string $className
     * @param string $version
     *
     * @return array|null
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function findConfig($section, $className, $version)
    {
        if (!isset($this->config[$section][$className])) {
            // no config for the requested class
            return null;
        }

        return $this->config[$section][$className];
    }
}
