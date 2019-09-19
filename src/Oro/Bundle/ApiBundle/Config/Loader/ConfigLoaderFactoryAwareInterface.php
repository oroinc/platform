<?php

namespace Oro\Bundle\ApiBundle\Config\Loader;

/**
 * This interface can be implemented by API configuration loaders if they need an the loader factory.
 */
interface ConfigLoaderFactoryAwareInterface
{
    /**
     * @param ConfigLoaderFactory $factory
     */
    public function setConfigLoaderFactory(ConfigLoaderFactory $factory);
}
