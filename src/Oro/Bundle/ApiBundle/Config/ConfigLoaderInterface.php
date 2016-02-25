<?php

namespace Oro\Bundle\ApiBundle\Config;

interface ConfigLoaderInterface
{
    /**
     * Loads a configuration from an array.
     *
     * @param array $config
     *
     * @return mixed
     */
    public function load(array $config);
}
