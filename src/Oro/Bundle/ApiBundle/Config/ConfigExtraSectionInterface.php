<?php

namespace Oro\Bundle\ApiBundle\Config;

/**
 * This interface can be used to tell the Context that an additional data should be available
 * as additional type of configuration. So, "hasConfigOf", "getConfigOf" and "setConfigOf" methods
 * of the Context can be used to access these data.
 */
interface ConfigExtraSectionInterface
{
    /**
     * Gets a string that uniquely identifies an additional configuration section.
     *
     * @return string
     */
    public function getName();

    /**
     * Gets the configuration type that can be loaded into this section.
     *
     * @return string
     */
    public function getConfigType();
}
