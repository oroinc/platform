<?php

namespace Oro\Bundle\RequireJSBundle\Config;

class Config
{
    /**
     * Piece of JS-code with require.js main config
     *
     * @var string
     */
    protected $mainConfig;

    /**
     * require.js build config
     *
     * @var array
     */
    protected $buildConfig;

    /**
     * Path to output file
     *
     * @var string
     */
    protected $outputFilePath;

    /**
     * Path to config file for require.js
     *
     * @var string
     */
    protected $configFilePath;

    /**
     * @return string
     */
    public function getMainConfig()
    {
        return $this->mainConfig;
    }

    /**
     * @param string config
     *
     * @return Config
     */
    public function setMainConfig($config)
    {
        $this->mainConfig = $config;

        return $this;
    }

    /**
     * @return array
     */
    public function getBuildConfig()
    {
        return $this->buildConfig;
    }

    /**
     * @param array $config
     *
     * @return Config
     */
    public function setBuildConfig(array $config)
    {
        $this->buildConfig = $config;

        return $this;
    }

    /**
     * @return string
     */
    public function getOutputFilePath()
    {
        return $this->outputFilePath;
    }

    /**
     * @param string $filePath
     *
     * @return Config
     */
    public function setOutputFilePath($filePath)
    {
        $this->outputFilePath = $filePath;

        return $this;
    }

    /**
     * @return string
     */
    public function getConfigFilePath()
    {
        return $this->configFilePath;
    }

    /**
     * @param string $filePath
     *
     * @return Config
     */
    public function setConfigFilePath($filePath)
    {
        $this->configFilePath = $filePath;

        return $this;
    }
}
