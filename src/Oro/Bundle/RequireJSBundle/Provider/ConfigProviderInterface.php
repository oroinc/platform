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
     * Generate build config for require.js
     *
     * @return array
     */
    public function generateBuildConfigs();
}