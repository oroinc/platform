<?php

namespace Oro\Bundle\ApiBundle\Config;

/**
 * Provides an interface for different kind requests for additional configuration sections.
 * This interface can be used to tell the context that an additional data should be available
 * as additional configuration section. So, "hasConfigOf", "getConfigOf" and "setConfigOf" methods
 * of the context can be used to access these data.
 */
interface ConfigExtraSectionInterface extends ConfigExtraInterface
{
    /**
     * Returns the configuration type that should be loaded into this section.
     * This string is used by ConfigLoaderFactory to find the appropriate loader.
     * @see \Oro\Bundle\ApiBundle\Config\ConfigLoaderFactory
     *
     * @return string
     */
    public function getConfigType();
}
