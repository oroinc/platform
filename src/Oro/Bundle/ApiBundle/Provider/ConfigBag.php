<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\ApiBundle\Request\Version;

class ConfigBag
{
    /** @var array */
    protected $config;

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Gets a configuration for the given version of a class
     *
     * @param string $className The FQCN of an entity
     * @param string $version   The version of a config
     *
     * @return array|null
     */
    public function getConfig($className, $version)
    {
        return $this->findConfig('entities', $className, $version);
    }

    /**
     * Gets a relation configuration for the given version of a class
     *
     * @param string $className The FQCN of an entity
     * @param string $version   The version of a config
     *
     * @return array|null
     */
    public function getRelationConfig($className, $version)
    {
        return $this->findConfig('relations', $className, $version);
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
    protected function findConfig($section, $className, $version)
    {
        if (!isset($this->config[$section][$className])) {
            // no config for the requested class
            return null;
        }
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
            if (!$resultVersion || version_compare($configVersion, $resultVersion) > 0) {
                $resultVersion = $configVersion;
                $result        = $config;
            }
        }

        if (null !== $result && empty($result)) {
            $result = null;
        }

        return $result;
    }
}
