<?php

namespace Oro\Bundle\ApiBundle\Config;

/**
 * Provides an interface for different kind requests for additional configuration sections.
 * This interface can be used to tell the Context that an additional data should be available
 * as additional configuration section. So, "hasConfigOf", "getConfigOf" and "setConfigOf" methods
 * of the Context can be used to access these data.
 */
interface ConfigExtraSectionInterface extends ConfigExtraInterface
{
    /**
     * Gets the configuration type that can be loaded into this section.
     *
     * @return string
     */
    public function getConfigType();
}
