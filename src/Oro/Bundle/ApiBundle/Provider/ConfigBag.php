<?php

namespace Oro\Bundle\ApiBundle\Provider;

class ConfigBag
{
    /** @var array */
    protected $entityConfig;

    /**
     * @param array $entityConfig
     */
    public function __construct(array $entityConfig)
    {
        $this->entityConfig = $entityConfig;
    }

    /**
     * Gets a config for the given class version
     *
     * @param string      $className The FQCN of an entity
     * @param string|null $version   The version of a config. NULL to get the latest version of config
     *
     * @return array|null
     */
    public function getConfig($className, $version = null)
    {
        if ($version && isset($this->entityConfig[$className][$version])) {
            return $this->entityConfig[$className][$version];
        }

        $result        = null;
        $resultVersion = null;
        if (isset($this->entityConfig[$className])) {
            foreach ($this->entityConfig[$className] as $configVersion => $config) {
                if ($version) {
                    // a particular version requested
                    $comparisonResult = version_compare($configVersion, $version);
                    if ($comparisonResult === 0) {
                        $result = $config;
                        break;
                    } elseif ($comparisonResult < 0
                        && (!$resultVersion || version_compare($configVersion, $resultVersion) > 0)
                    ) {
                        $resultVersion = $configVersion;
                        $result        = $config;
                    }
                } else {
                    // the latest version requested
                    if (!$resultVersion || version_compare($configVersion, $resultVersion) > 0) {
                        $resultVersion = $configVersion;
                        $result        = $config;
                    }
                }
            }
        }

        return $result;
    }
}
