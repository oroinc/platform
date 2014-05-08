<?php

namespace Oro\Bundle\ChartBundle\Model;

use Oro\Bundle\ChartBundle\Exception\InvalidConfigurationException;

class ConfigProvider
{
    /**
     * @var array
     */
    protected $configs;

    /**
     * @param array $configs
     */
    public function __construct(array $configs)
    {
        $this->configs = $configs;
    }

    /**
     * @return array
     */
    public function getConfigs()
    {
        return $this->configs;
    }

    /**
     * @param string $chartName
     * @throws \Oro\Bundle\ChartBundle\Exception\InvalidConfigurationException
     * @return array
     */
    public function getChartConfig($chartName)
    {
        if (!$this->hasChartConfig($chartName)) {
            throw new InvalidConfigurationException($chartName);
        }

        return $this->configs[$chartName];
    }

    /**
     * @return array
     */
    public function getChartConfigs()
    {
        return $this->configs;
    }

    /**
     * @param string $chartName
     * @return bool
     */
    public function hasChartConfig($chartName)
    {
        return isset($this->configs[$chartName]);
    }
}
