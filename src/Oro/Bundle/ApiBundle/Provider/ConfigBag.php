<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\ApiBundle\Request\Version;

/**
 * A storage for configuration of all registered Data API resources.
 */
class ConfigBag implements ConfigBagInterface
{
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
        return array_keys($this->findConfigs('entities', $version));
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig(string $className, string $version): ?array
    {
        return $this->findConfig('entities', $className, $version);
    }

    /**
     * {@inheritdoc}
     */
    public function getRelationConfig(string $className, string $version): ?array
    {
        return $this->findConfig('relations', $className, $version);
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
        $result = $this->config[$section];

        // @todo: API version is not supported for now. Implement filtering by the version here

        return $result;
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
        $result = $this->config[$section][$className];
        /* @todo: API version is not supported for now
        // normalize the version if needed
        if ($version === Version::LATEST) {
            $version = null;
        }
        if (null !== $version && isset($this->config[$section][$className][$version])) {
            // found config for exactly requested version
            return $this->config[$section][$className][$version];
        }

        $result        = null;
        $resultVersion = null;
        foreach ($this->config[$section][$className] as $configVersion => $config) {
            if (null !== $version && version_compare($configVersion, $version) > 0) {
                // skip current config because its version is greater that the requested version
                continue;
            }
            if (null === $resultVersion || version_compare($configVersion, $resultVersion) > 0) {
                $resultVersion = $configVersion;
                $result        = $config;
            }
        }

        if (null !== $result && empty($result)) {
            $result = null;
        }
        */

        return $result;
    }
}
