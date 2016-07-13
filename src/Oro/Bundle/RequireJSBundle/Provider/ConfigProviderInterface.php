<?php

namespace Oro\Bundle\RequireJSBundle\Provider;

use Oro\Bundle\RequireJSBundle\Config\Config as RequireJSConfig;

interface ConfigProviderInterface
{
    /**
     * Get current config for require.js
     *
     * @return RequireJSConfig
     */
    public function getConfig();

    /**
     * Collect configs for require.js
     *
     * @return RequireJSConfig[]
     */
    public function collectConfigs();
}
