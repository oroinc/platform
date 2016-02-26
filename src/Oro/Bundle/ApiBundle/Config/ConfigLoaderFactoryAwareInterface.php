<?php

namespace Oro\Bundle\ApiBundle\Config;

interface ConfigLoaderFactoryAwareInterface
{
    /**
     * @param ConfigLoaderFactory $factory
     */
    public function setConfigLoaderFactory(ConfigLoaderFactory $factory);
}
