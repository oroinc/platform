<?php

namespace Oro\Bundle\RequireJSBundle\Provider;

interface ConfigProviderInterface
{
    /**
     * Fetches piece of JS-code with require.js main config from cache
     * or if it was not there - generates and put into a cache
     *
     * @param $key
     *
     * @return string
     */
    public function getMainConfig($key);

    /**
     * Get path to config file for require.js
     *
     * @return string
     */
    public function getConfigFilePath();

    /**
     * Get path to output file
     *
     * @return string
     */
    public function getOutputFilePath();

    /**
     * Collect build and main configs for require.js
     *
     * @return array
     */
    public function collectAllConfigs();

    /**
     * Collect basic configs for require.js
     *
     * @return array
     */
    public function collectConfigs();
}
