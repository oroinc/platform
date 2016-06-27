<?php

namespace Oro\Bundle\RequireJSBundle\Provider;

interface ConfigProviderInterface
{
    /**
     * Fetches piece of JS-code with require.js main config from cache
     * or if it was not there - generates and put into a cache
     *
     * @return string
     */
    public function getMainConfig();

    /**
     * Get path to config file for require.js
     *
     * @param $config
     * @return string
     */
    public function getConfigFilePath($config);

    /**
     * Get path to output file
     *
     * @param $config
     * @return string
     */
    public function getOutputFilePath($config);

    /**
     * Generate build config for require.js
     *
     * @return array
     */
    public function collectAllConfigs();

    /**
     * @param $key
     * @return array
     */
    public function collectConfigs($key);
}
